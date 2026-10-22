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

use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validates the closure of a line string. */
final class FirstPointEqualsLastPointValidator extends ConstraintValidator
{
    /**
     * Validate that a line string is closed.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FirstPointEqualsLastPoint) {
            throw new UnexpectedTypeException($constraint, FirstPointEqualsLastPoint::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof LineStringInterface) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }

        $points = $value->getPoints();
        if ([] === $points || !$points[0]->equalsTo($points[array_key_last($points)])) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
