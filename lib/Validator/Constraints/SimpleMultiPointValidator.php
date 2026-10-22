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
use LongitudeOne\SpatialTypes\Interfaces\MultiPointInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Explicit aggregate property, independent from ordinary validity. */
final class SimpleMultiPointValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleMultiPoint) {
            throw new UnexpectedTypeException($constraint, SimpleMultiPoint::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof MultiPointInterface || GeometryTypeEnum::MULTIPOINT !== $value->getType()) {
            throw new UnexpectedValueException($value, MultiPointInterface::class);
        }
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new MultiPoint(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($before !== count($this->context->getViolations())) {
            return;
        }
        $positions = $this->positions($value);
        if ([] === $positions) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        $this->compare($value, $positions);
    }

    /** @param array<int, list<float>> $positions */
    private function compare(MultiPointInterface $value, array $positions): void
    {
        foreach ($positions as $index => $position) {
            foreach ($positions as $previous => $other) {
                if ($previous < $index && PointCoordinates::same($position, $other)) {
                    $this->context->buildViolation('The point repeats a spatial position.')
                        ->atPath('points[' . $index . ']')->setInvalidValue(array_values($value->getPoints())[$index])
                        ->setParameter('{{ other }}', 'points[' . $previous . ']')
                        ->setCode(SimpleMultiPoint::INVALID_ERROR)->addViolation()
                    ;
                }
            }
        }
    }

    /** @return array<int, list<float>> */
    private function positions(MultiPointInterface $value): array
    {
        $positions = [];
        foreach (array_values($value->getPoints()) as $index => $point) {
            $position = PointCoordinates::position($point);
            if (null !== $position) {
                $positions[$index] = $position;
            }
        }

        return $positions;
    }

    private function unavailable(SimpleMultiPoint $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Geography SimpleMultiPoint validation is not implemented yet.')
                ->setCode(SimpleMultiPoint::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
