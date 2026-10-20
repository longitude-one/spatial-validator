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

use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Validator\Internal\PlanarPatch;
use LongitudeOne\SpatialValidator\Validator\Internal\PolyhedralSurfaceTopology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validate surface boundaries without requiring them to enclose a solid. */
final class PolyhedralSurfaceValidator extends ConstraintValidator
{
    /**
     * Validate patches and their oriented edge connections.
     *
     * @param mixed      $value      Surface to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PolyhedralSurface) {
            throw new UnexpectedTypeException($constraint, PolyhedralSurface::class);
        }
        if (!$value instanceof PolyhedralSurfaceInterface) {
            throw new UnexpectedValueException($value, PolyhedralSurfaceInterface::class);
        }
        if (!$value->hasZ()) {
            $this->context->buildViolation($constraint->message)->addViolation();

            return;
        }
        foreach ($value->getPatches() as $patch) {
            if ($patch->isEmpty() || !$patch->hasZ() || !PlanarPatch::isValid($patch)) {
                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }
            foreach ($patch->getRings() as $ring) {
                $this->context->getValidator()->inContext($this->context)->validate(
                    $ring,
                    [new Ring(), new SimpleThreeDimensionalLineString()],
                    $this->context->getGroup()
                );
            }
        }
        if (!PolyhedralSurfaceTopology::isValid($value->getPatches())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
