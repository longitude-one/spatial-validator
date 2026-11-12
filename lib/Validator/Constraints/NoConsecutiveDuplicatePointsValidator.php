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
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Reject spatially equal consecutive positions, ignoring M. */
final class NoConsecutiveDuplicatePointsValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
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
                    ->atPath('points['.$index.']')->setInvalidValue($point)
                    ->setCode(NoConsecutiveDuplicatePoints::INPUT_ERROR)->addViolation()
                ;
            }
            if (null !== $position && null !== $previous && PointCoordinates::same($previous, $position)) {
                $this->context->buildViolation('Consecutive points must have different spatial positions.')
                    ->atPath('points['.$index.']')->setInvalidValue($point)
                    ->setCode(NoConsecutiveDuplicatePoints::INVALID_ERROR)->addViolation()
                ;
            }
            $previous = $position;
        }
    }
}
