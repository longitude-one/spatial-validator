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
use LongitudeOne\SpatialTypes\Interfaces\MultiLineStringInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Explicit aggregate property, independent from ordinary validity. */
final class ClosedMultiLineStringValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ClosedMultiLineString) {
            throw new UnexpectedTypeException($constraint, ClosedMultiLineString::class);
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
        $members = array_values($value->getElements());
        $nonEmpty = false;
        foreach ($members as $line) {
            $nonEmpty = $nonEmpty || [] !== $line->getPoints();
        }
        if (!$nonEmpty) {
            $this->context->buildViolation('An EMPTY MultiLineString is not closed.')
                ->setCode(ClosedMultiLineString::INVALID_ERROR)->addViolation()
            ;

            return;
        }
        foreach ($members as $index => $line) {
            $this->context->getValidator()->inContext($this->context)->atPath('lineStrings[' . $index . ']')
                ->validate(
                    $line,
                    new FirstPointEqualsLastPoint(groups: $constraint->groups, payload: $constraint->payload),
                    $this->context->getGroup()
                )
            ;
        }
    }
}
