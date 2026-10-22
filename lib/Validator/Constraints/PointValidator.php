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
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose the applicable Point coordinate rules. */
final class PointValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Point) {
            throw new UnexpectedTypeException($constraint, Point::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof PointInterface) {
            throw new UnexpectedValueException($value, PointInterface::class);
        }

        if (GeometryTypeEnum::POINT !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::POINT->value);
        }

        $this->context->getValidator()->inContext($this->context)->validate($value, [
            new CoordinateLayout(groups: $constraint->groups, payload: $constraint->payload),
            new FiniteCoordinates(groups: $constraint->groups, payload: $constraint->payload),
            new GeographyBounds(groups: $constraint->groups, payload: $constraint->payload),
        ], $this->context->getGroup());
    }
}
