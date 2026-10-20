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

use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validate SQL/MM triangle structure independently of the concrete polygon type. */
final class TriangleValidator extends ConstraintValidator
{
    /**
     * Validate the boundary, composing the existing ring constraint.
     *
     * @param mixed      $value      Polygon to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Triangle) {
            throw new UnexpectedTypeException($constraint, Triangle::class);
        }

        if (!$value instanceof PolygonInterface) {
            throw new UnexpectedValueException($value, PolygonInterface::class);
        }

        $rings = $value->getRings();
        if ([] === $rings) {
            return;
        }

        if (1 < count($rings)) {
            $this->context->buildViolation($constraint->interiorRingsMessage)->addViolation();
        }

        $exterior = $value->getRing(0);
        if (4 !== count($exterior->getPoints())) {
            $this->context->buildViolation($constraint->pointCountMessage)->addViolation();
        }

        $this->context->getValidator()
            ->inContext($this->context)
            ->validate($exterior, new Ring(), $this->context->getGroup())
        ;
    }
}
