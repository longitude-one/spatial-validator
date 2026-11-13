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

/** Require a minimum point count, with a separate EMPTY allowance. */
final class MinimumPointCountValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinimumPointCount) {
            throw new UnexpectedTypeException($constraint, MinimumPointCount::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        $count = \count($value->getPoints());
        if (0 === $count) {
            return;
        }
        if ($count < $constraint->limit) {
            $this->context->buildViolation('A line must contain at least {{ limit }} points unless EMPTY is allowed.')
                ->setParameter('{{ limit }}', (string) $constraint->limit)->setCode(MinimumPointCount::INVALID_ERROR)
                ->addViolation()
            ;
        }
    }
}
