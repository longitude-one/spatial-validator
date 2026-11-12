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
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compare Geometry endpoints without interpreting coordinates as a geodetic calculation. */
final class FirstPointEqualsLastPointValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FirstPointEqualsLastPoint) {
            throw new UnexpectedTypeException($constraint, FirstPointEqualsLastPoint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        $points = array_values($value->getPoints());
        $first = PointCoordinates::position($points[0] ?? null);
        $last = PointCoordinates::position($points[\count($points) - 1] ?? null);
        if (null === $first || null === $last) {
            $this->notClosed();

            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        if (!PointCoordinates::same($first, $last)) {
            $this->notClosed();
        }
    }

    /**
     * Not closed.
     */
    private function notClosed(): void
    {
        $this->context->buildViolation('A closed line requires finite non-empty endpoints at the same position.')
            ->setCode(FirstPointEqualsLastPoint::INVALID_ERROR)->addViolation()
        ;
    }

    /**
     * Unavailable.
     *
     * @param FirstPointEqualsLastPoint $constraint applied validation constraint
     */
    private function unavailable(FirstPointEqualsLastPoint $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Geography line closure is not implemented yet.')
                ->setCode(FirstPointEqualsLastPoint::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
