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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose line validity, minimum cardinality, closure and simplicity for a linear ring. */
final class RingValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Ring) {
            throw new UnexpectedTypeException($constraint, Ring::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        if ([] === $value->getPoints()) {
            $this->context->buildViolation('An EMPTY LineString is not a linear ring.')
                ->setCode(Ring::EMPTY_ERROR)->addViolation()
            ;

            return;
        }
        $this->context->getValidator()->inContext($this->context)->validate($value, [
            new MinimumPointCount(limit: 4, groups: $constraint->groups, payload: $constraint->payload),
            new FirstPointEqualsLastPoint(groups: $constraint->groups, payload: $constraint->payload),
            new SimpleLineString(groups: $constraint->groups, payload: $constraint->payload),
        ], $this->context->getGroup());
    }
}
