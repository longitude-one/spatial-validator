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
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Check circular defining-point cardinality independently of point validity. */
final class CircularPointCountValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CircularPointCount) {
            throw new UnexpectedTypeException($constraint, CircularPointCount::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof CircularStringInterface || GeometryTypeEnum::CIRCULARSTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, CircularStringInterface::class);
        }
        $count = \count($value->getPoints());
        if (0 !== $count && (3 > $count || 0 === $count % 2)) {
            $this->context->buildViolation('A non-empty CircularString needs an odd number of at least three points.')
                ->setCode(CircularPointCount::INVALID_ERROR)->addViolation()
            ;
        }
    }
}
