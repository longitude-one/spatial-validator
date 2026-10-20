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
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validates that adjacent points of a line string differ. */
final class NoConsecutiveDuplicatePointsValidator extends ConstraintValidator
{
    /**
     * Validate that no adjacent points are equal.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoConsecutiveDuplicatePoints) {
            throw new UnexpectedTypeException($constraint, NoConsecutiveDuplicatePoints::class);
        }

        if (!$value instanceof LineStringInterface) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }

        $previousPoint = null;
        foreach ($value->getPoints() as $point) {
            if ($previousPoint instanceof PointInterface && $previousPoint->equalsTo($point)) {
                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }

            $previousPoint = $point;
        }
    }
}
