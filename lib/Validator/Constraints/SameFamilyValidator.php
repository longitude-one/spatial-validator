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

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validates a spatial value against a required spatial family. */
final class SameFamilyValidator extends ConstraintValidator
{
    /**
     * Validate the family of a spatial value.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SameFamily) {
            throw new UnexpectedTypeException($constraint, SameFamily::class);
        }

        if (!$value instanceof SpatialInterface) {
            throw new UnexpectedValueException($value, SpatialInterface::class);
        }

        if ($constraint->family !== $value->getFamily()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
