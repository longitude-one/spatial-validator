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

use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Check finiteness of every declared ordinate, including M. */
final class FiniteCoordinatesValidator extends ConstraintValidator
{
    /**
     * Validate a point independently of model constructor guarantees.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FiniteCoordinates) {
            throw new UnexpectedTypeException($constraint, FiniteCoordinates::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof PointInterface) {
            throw new UnexpectedValueException($value, PointInterface::class);
        }
        foreach (PointCoordinates::ordinates($value) as $axis => $ordinate) {
            if (null !== $ordinate && !is_finite((float) $ordinate)) {
                $this->context->buildViolation('The {{ axis }} ordinate must be finite.')
                    ->setParameter('{{ axis }}', $axis)->setCode(FiniteCoordinates::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }
}
