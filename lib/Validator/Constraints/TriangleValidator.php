<?php

/**
 * This file is part of the spatial project.
 *
 * PHP 8.4 | 8.5
 *
 * Copyright Alexandre Tranchant <alexandre.tranchant@gmail.com> 2024-2026
 * Copyright Longitude One 2024-2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\TriangleInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Internal\PolygonStructure;
use LongitudeOne\SpatialValidator\Internal\PolygonTopology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose shared Polygon rules without widening the Triangle input contract. */
final class TriangleValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Triangle) {
            throw new UnexpectedTypeException($constraint, Triangle::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof TriangleInterface) {
            throw new UnexpectedValueException($value, TriangleInterface::class);
        }

        if (GeometryTypeEnum::TRIANGLE !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::TRIANGLE->value);
        }

        $this->triangle($value, $constraint);
    }

    private function distinct(TriangleInterface $triangle): void
    {
        $rings = array_values($triangle->getRings());
        $points = array_values($rings[0]->getPoints());
        if (4 !== count($points)) {
            return;
        }
        $positions = array_map(PointCoordinates::position(...), array_slice($points, 0, 3));
        for ($second = 1; $second < 3; ++$second) {
            for ($first = 0; $first < $second; ++$first) {
                if (null === $positions[$first] || null === $positions[$second]) {
                    continue;
                }
                if (PointCoordinates::same($positions[$first], $positions[$second])) {
                    $this->error(
                        'A Triangle requires three distinct spatial vertices.',
                        Triangle::DISTINCT_ERROR,
                        'rings[0].points[' . $second . ']'
                    );
                }
            }
        }
    }

    private function error(string $message, string $code, string $path): void
    {
        $this->context->buildViolation($message)->setCode($code)->atPath($path)->addViolation();
    }

    private function triangle(TriangleInterface $value, Triangle $constraint): void
    {
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new PolygonStructure(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        $rings = array_values($value->getRings());
        if ([] === $rings) {
            return;
        }
        if (1 < count($rings)) {
            $this->error('A Triangle must not have interior rings.', Triangle::INTERIOR_ERROR, 'rings[1]');
        }
        if (4 !== count($rings[0]->getPoints())) {
            $this->error(
                'A non-empty Triangle requires exactly four exterior positions.',
                Triangle::POSITION_COUNT_ERROR,
                'rings[0]'
            );
        }
        $this->distinct($value);
        if ($before !== count($this->context->getViolations()) || SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            return;
        }
        // Even Q02-equal closure positions retain their exact supplied coordinates.
        foreach (PolygonTopology::errors(PolygonTopology::project($value)) as [$index]) {
            $this->error(
                'Triangle boundaries must not cross or share an interval.',
                Triangle::TOPOLOGY_ERROR,
                'rings[' . $index . ']'
            );
        }
    }
}
