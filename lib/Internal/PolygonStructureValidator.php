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

namespace LongitudeOne\SpatialValidator\Internal;

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** @internal Compose complete linear boundaries and one supporting plane, leaving inter-ring topology to K. */
final class PolygonStructureValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PolygonStructure) {
            throw new UnexpectedTypeException($constraint, PolygonStructure::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof PolygonInterface || GeometryTypeEnum::POLYGON !== $value->getType()) {
            throw new UnexpectedValueException($value, PolygonInterface::class);
        }
        $rings = array_values($value->getRings());
        if ([] === $rings) {
            if (!$value->isEmpty()) {
                $this->error('A non-empty Polygon requires an exterior ring.', PolygonStructure::EMPTY_ERROR);
            }

            return;
        }
        if ($value->isEmpty()) {
            $this->error('An EMPTY Polygon must not contain boundary rings.', PolygonStructure::EMPTY_ERROR);
        }
        $positions = $this->boundaries($value, $constraint);
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        $this->plane($positions);
    }

    /** @return array<int, list<list<float>>> */
    private function boundaries(PolygonInterface $polygon, PolygonStructure $constraint): array
    {
        $positions = [];
        foreach (array_values($polygon->getRings()) as $index => $ring) {
            $path = 'rings[' . $index . ']';
            foreach (ComponentMetadata::errors($polygon, $ring) as $code => $message) {
                $this->error($message, $code, $path);
            }
            if (GeometryTypeEnum::LINESTRING !== $ring->getType()) {
                $this->error('The boundary must declare LineString type.', ComponentMetadata::TYPE_ERROR, $path);

                continue;
            }
            $this->context->getValidator()->inContext($this->context)->atPath($path)->validate(
                $ring,
                new Ring(groups: $constraint->groups, payload: $constraint->payload),
                $this->context->getGroup()
            );
            $positions[$index] = $this->positions($polygon, $ring);
        }

        return $positions;
    }

    private function error(string $message, string $code, string $path = ''): void
    {
        $this->context->buildViolation($message)->setCode($code)->atPath($path)->addViolation();
    }

    /** @param array<int, list<list<float>>> $rings */
    private function plane(array $rings): void
    {
        $exterior = $rings[0] ?? [];
        if ([] === $exterior) {
            return;
        }
        $basis = PolygonPlane::basis($exterior);
        if (null === $basis) {
            $this->error(
                'The exterior must determine a non-degenerate supporting plane.',
                PolygonStructure::DEGENERATE_ERROR,
                'rings[0]'
            );

            return;
        }
        foreach ($rings as $index => $positions) {
            foreach ($positions as $point => $position) {
                if (!PolygonPlane::contains($basis, $position)) {
                    $this->error(
                        'Every boundary position must lie in the exterior supporting plane.',
                        PolygonStructure::PLANE_ERROR,
                        'rings[' . $index . '].points[' . $point . ']'
                    );
                }
            }
        }
    }

    /** @return list<list<float>> */
    private function positions(PolygonInterface $polygon, LineStringInterface $ring): array
    {
        if ([] !== ComponentMetadata::errors($polygon, $ring)) {
            return [];
        }
        $positions = [];
        foreach ($ring->getPoints() as $point) {
            $position = PointCoordinates::position($point);
            if (null === $position || $point->getDimension() !== $polygon->getDimension()) {
                return [];
            }
            $positions[] = $position;
        }

        return $positions;
    }

    private function unavailable(PolygonStructure $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->error(
                'Geography Polygon planarity and inter-ring topology are not implemented yet.',
                PolygonStructure::UNAVAILABLE_ERROR
            );
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
