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

use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Check declared ordinates and EMPTY consistency. */
final class CoordinateLayoutValidator extends ConstraintValidator
{
    /** Validate a point independently of model constructor guarantees. */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CoordinateLayout) {
            throw new UnexpectedTypeException($constraint, CoordinateLayout::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof PointInterface) {
            throw new UnexpectedValueException($value, PointInterface::class);
        }
        $ordinates = PointCoordinates::ordinates($value);
        $nulls = count(array_filter($ordinates, static fn ($ordinate): bool => null === $ordinate));
        $empty = count($ordinates) === $nulls;
        if ((0 !== $nulls && !$empty) || $empty !== $value->isEmpty()) {
            $this->context->buildViolation('The declared coordinate layout and EMPTY state must be consistent.')
                ->setCode(CoordinateLayout::INVALID_ERROR)->addViolation()
            ;
        }
    }
}
