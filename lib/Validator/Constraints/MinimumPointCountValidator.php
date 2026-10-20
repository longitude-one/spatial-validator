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

/** Validates the minimum number of points in a line string. */
final class MinimumPointCountValidator extends ConstraintValidator
{
    /**
     * Validate the number of points in a line string.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinimumPointCount) {
            throw new UnexpectedTypeException($constraint, MinimumPointCount::class);
        }

        if (!$value instanceof LineStringInterface) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }

        if (count($value->getPoints()) < $constraint->limit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', (string) $constraint->limit)
                ->addViolation()
            ;
        }
    }
}
