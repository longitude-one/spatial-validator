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
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Check source-defined arc point restrictions without requiring three distinct positions. */
final class CircularArcPointsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CircularArcPoints) {
            throw new UnexpectedTypeException($constraint, CircularArcPoints::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof CircularStringInterface || GeometryTypeEnum::CIRCULARSTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, CircularStringInterface::class);
        }
        $points = array_values($value->getPoints());
        $this->nonEmpty($points);
        $this->intermediates($points);
    }

    /** @param list<PointInterface> $points */
    private function intermediates(array $points): void
    {
        $count = count($points);
        for ($index = 0; $index + 2 < $count; $index += 2) {
            $start = PointCoordinates::position($points[$index]);
            $middle = PointCoordinates::position($points[$index + 1]);
            $end = PointCoordinates::position($points[$index + 2]);
            if (null === $start || null === $middle || null === $end) {
                continue;
            }
            if (PointCoordinates::same($start, $middle) || PointCoordinates::same($middle, $end)) {
                $this->context->buildViolation('The intermediate arc position must differ from both endpoints.')
                    ->atPath('arcs[' . intdiv($index, 2) . ']')->setInvalidValue($points[$index + 1])
                    ->setCode(CircularArcPoints::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }

    /** @param list<PointInterface> $points */
    private function nonEmpty(array $points): void
    {
        foreach ($points as $index => $point) {
            if (PointCoordinates::isEmptyPoint($point)) {
                $this->context->buildViolation('A circular arc must not contain an EMPTY Point.')
                    ->atPath('points[' . $index . ']')->setInvalidValue($point)
                    ->setCode(CircularArcPoints::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }
}
