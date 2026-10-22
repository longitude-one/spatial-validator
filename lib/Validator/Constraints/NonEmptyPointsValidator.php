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
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Reject EMPTY Point members of a line. */
final class NonEmptyPointsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NonEmptyPoints) {
            throw new UnexpectedTypeException($constraint, NonEmptyPoints::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        foreach (array_values($value->getPoints()) as $index => $point) {
            if (PointCoordinates::isEmptyPoint($point)) {
                $this->context->buildViolation('A line must not contain an EMPTY Point.')
                    ->atPath('points[' . $index . ']')->setInvalidValue($point)
                    ->setCode(NonEmptyPoints::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }
}
