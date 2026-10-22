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

/** Reject spatially equal consecutive positions, ignoring M. */
final class NoConsecutiveDuplicatePointsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoConsecutiveDuplicatePoints) {
            throw new UnexpectedTypeException($constraint, NoConsecutiveDuplicatePoints::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        $previous = null;
        foreach (array_values($value->getPoints()) as $index => $point) {
            $position = PointCoordinates::position($point);
            if (null === $position) {
                $this->context->buildViolation('Finite, non-empty Point coordinates are required for comparison.')
                    ->atPath('points[' . $index . ']')->setInvalidValue($point)
                    ->setCode(NoConsecutiveDuplicatePoints::INPUT_ERROR)->addViolation()
                ;
            }
            if (null !== $position && null !== $previous && PointCoordinates::same($previous, $position)) {
                $this->context->buildViolation('Consecutive points must have different spatial positions.')
                    ->atPath('points[' . $index . ']')->setInvalidValue($point)
                    ->setCode(NoConsecutiveDuplicatePoints::INVALID_ERROR)->addViolation()
                ;
            }
            $previous = $position;
        }
    }
}
