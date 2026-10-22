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
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Report unavailable validation without claiming geometric validity. */
final class CircularStringValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CircularString) {
            throw new UnexpectedTypeException($constraint, CircularString::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof CircularStringInterface) {
            throw new UnexpectedValueException($value, CircularStringInterface::class);
        }

        if (GeometryTypeEnum::CIRCULARSTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::CIRCULARSTRING->value);
        }

        $this->context->getValidator()->inContext($this->context)->validate($value, [
            new Components(groups: $constraint->groups, payload: $constraint->payload),
            new CircularPointCount(groups: $constraint->groups, payload: $constraint->payload),
            new CircularArcPoints(groups: $constraint->groups, payload: $constraint->payload),
        ], $this->context->getGroup());
    }
}
