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
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialValidator\Internal\PolygonStructure;
use LongitudeOne\SpatialValidator\Internal\PolygonTopology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose Polygon boundaries, planarity, inter-ring topology and orientation diagnostics. */
final class PolygonValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Polygon) {
            throw new UnexpectedTypeException($constraint, Polygon::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof PolygonInterface) {
            throw new UnexpectedValueException($value, PolygonInterface::class);
        }

        $this->polygon($value, $constraint);
    }

    private function diagnostic(string $code, Polygon $constraint, string $path = ''): void
    {
        $messages = [
            Polygon::INTERSECTION_ERROR => 'Polygon boundaries must not cross or share an interval.',
            Polygon::OUTSIDE_ERROR => 'Every hole must lie inside the exterior boundary.',
            Polygon::NESTED_ERROR => 'Polygon holes must not overlap or contain another hole.',
            Polygon::DISCONNECTED_ERROR => 'Boundary contacts must not disconnect the Polygon interior.',
            Polygon::ORIENTATION_ERROR => 'A hole has the same orientation as the exterior boundary.',
            Polygon::UNAVAILABLE_ERROR => 'Geography Polygon topology and orientation are not implemented yet.',
        ];
        $associated = $constraint;
        if (in_array($code, [Polygon::ORIENTATION_ERROR, Polygon::UNAVAILABLE_ERROR], true)) {
            $associated = clone $constraint;
            $associated->payload = $constraint->payload ?? ['severity' => 'warning'];
        }
        $this->context->setConstraint($associated);

        try {
            $this->context->buildViolation($messages[$code])->atPath($path)->setCode($code)->addViolation();
        } finally {
            $this->context->setConstraint($constraint);
        }
    }

    private function polygon(PolygonInterface $value, Polygon $constraint): void
    {
        if (GeometryTypeEnum::POLYGON !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::POLYGON->value);
        }
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new PolygonStructure(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ([] === $value->getRings()) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->diagnostic(Polygon::UNAVAILABLE_ERROR, $constraint);

            return;
        }
        if ($before !== count($this->context->getViolations())) {
            return;
        }
        foreach (PolygonTopology::errors(PolygonTopology::project($value)) as [$index, $code]) {
            $this->diagnostic($code, $constraint, 'rings[' . $index . ']');
        }
    }
}
