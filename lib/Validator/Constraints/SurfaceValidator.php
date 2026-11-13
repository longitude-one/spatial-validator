<?php

/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Report unavailable validation without claiming geometric validity. */
final class SurfaceValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Surface) {
            throw new UnexpectedTypeException($constraint, Surface::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof SpatialInterface) {
            throw new UnexpectedValueException($value, SpatialInterface::class);
        }

        if (GeometryTypeEnum::SURFACE !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::SURFACE->value);
        }

        $this->context->buildViolation('Surface validation is not implemented yet.')->addViolation();
    }
}
