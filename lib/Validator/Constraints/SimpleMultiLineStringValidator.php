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
use LongitudeOne\SpatialTypes\Interfaces\MultiLineStringInterface;
use LongitudeOne\SpatialValidator\Internal\MultiLineContacts;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Explicit aggregate property, independent from ordinary validity. */
final class SimpleMultiLineStringValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleMultiLineString) {
            throw new UnexpectedTypeException($constraint, SimpleMultiLineString::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof MultiLineStringInterface || GeometryTypeEnum::MULTILINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, MultiLineStringInterface::class);
        }
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new MultiLineString(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($before !== count($this->context->getViolations())) {
            return;
        }
        $lines = $this->positions($value, $constraint);
        if ([] === $lines) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        if ($before !== count($this->context->getViolations())) {
            return;
        }
        $this->compare($value, $lines);
    }

    /** @param array<int, list<list<float>>> $lines */
    private function compare(MultiLineStringInterface $value, array $lines): void
    {
        foreach ($lines as $index => $positions) {
            foreach ($lines as $previous => $other) {
                if ($previous < $index && !MultiLineContacts::permitted($positions, $other)) {
                    $this->context->buildViolation('The lines intersect outside their shared boundaries or overlap.')
                        ->atPath('lineStrings[' . $index . ']')
                        ->setInvalidValue(array_values($value->getElements())[$index])
                        ->setParameter('{{ other }}', 'lineStrings[' . $previous . ']')
                        ->setCode(SimpleMultiLineString::INVALID_ERROR)->addViolation()
                    ;
                }
            }
        }
    }

    /** @return array<int, list<list<float>>> */
    private function positions(MultiLineStringInterface $value, SimpleMultiLineString $constraint): array
    {
        $lines = [];
        foreach (array_values($value->getElements()) as $index => $line) {
            $this->context->getValidator()->inContext($this->context)->atPath('lineStrings[' . $index . ']')
                ->validate(
                    $line,
                    new SimpleLineString(groups: $constraint->groups, payload: $constraint->payload),
                    $this->context->getGroup()
                )
            ;
            $positions = array_values(array_filter(array_map(PointCoordinates::position(...), $line->getPoints())));
            if ([] !== $positions) {
                $lines[$index] = $positions;
            }
        }

        return $lines;
    }

    private function unavailable(SimpleMultiLineString $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Geography SimpleMultiLineString validation is not implemented yet.')
                ->setCode(SimpleMultiLineString::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
