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
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Internal\PolyhedralTopology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Add closure and enclosed nonzero volume to complete general surface validity. */
final class ClosedPolyhedralSurfaceValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ClosedPolyhedralSurface) {
            throw new UnexpectedTypeException($constraint, ClosedPolyhedralSurface::class);
        }
        if (null === $value) {
            return;
        }
        if (
            !$value instanceof PolyhedralSurfaceInterface
            || GeometryTypeEnum::POLYHEDRALSURFACE !== $value->getType()
        ) {
            throw new UnexpectedValueException($value, PolyhedralSurfaceInterface::class);
        }
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new PolyhedralSurface(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($this->hasErrors($before)) {
            return;
        }
        $patches = $this->positions($value);
        if (!PolyhedralTopology::closed($patches)) {
            $this->context->buildViolation(
                'A closed PolyhedralSurface requires a non-empty envelope enclosing a volume.'
            )
                ->setCode(ClosedPolyhedralSurface::INVALID_ERROR)->addViolation()
            ;
        }
    }

    private function hasErrors(int $before): bool
    {
        foreach ($this->context->getViolations() as $index => $violation) {
            if ($index >= $before && Polygon::ORIENTATION_ERROR !== $violation->getCode()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<list<list<list<float>>>> */
    private function positions(PolyhedralSurfaceInterface $value): array
    {
        $patches = [];
        foreach ($value->getPatches() as $patch) {
            $rings = [];
            foreach ($patch->getRings() as $ring) {
                $rings[] = array_values(array_filter(array_map(PointCoordinates::position(...), $ring->getPoints())));
            }
            $patches[] = $rings;
        }

        return $patches;
    }
}
