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
        $last = PointCoordinates::position($points[count($points) - 1] ?? null);
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

    private function notClosed(): void
    {
        $this->context->buildViolation('A closed line requires finite non-empty endpoints at the same position.')
            ->setCode(FirstPointEqualsLastPoint::INVALID_ERROR)->addViolation()
        ;
    }

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
