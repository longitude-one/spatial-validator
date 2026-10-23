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
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Internal\PolyhedralTopology;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose complete member validation and exact aggregate surface relationships. */
final class PolyhedralSurfaceValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PolyhedralSurface) {
            throw new UnexpectedTypeException($constraint, PolyhedralSurface::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof PolyhedralSurfaceInterface) {
            throw new UnexpectedValueException($value, PolyhedralSurfaceInterface::class);
        }

        if (GeometryTypeEnum::POLYHEDRALSURFACE !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::POLYHEDRALSURFACE->value);
        }

        $this->surface($value, $constraint);
    }

    private function diagnostic(PolyhedralSurface $constraint, string $code, string $path = ''): void
    {
        $associated = $constraint;
        $message = match ($code) {
            PolyhedralSurface::DIMENSION_ERROR => 'A PolyhedralSurface requires Z coordinates.',
            PolyhedralSurface::EMPTY_PATCH_ERROR => 'A non-empty PolyhedralSurface cannot contain an EMPTY patch.',
            PolyhedralSurface::INCIDENCE_ERROR => 'A boundary segment must have at most two incident patches.',
            PolyhedralSurface::ORIENTATION_ERROR => 'Shared patch boundaries must have opposite directions.',
            PolyhedralSurface::CONNECTED_ERROR => 'Patches must form an edge-connected surface.',
            PolyhedralSurface::INTERSECTION_ERROR => 'Patch interiors must not intersect other patches.',
            PolyhedralSurface::MANIFOLD_ERROR => 'Each surface vertex must have one connected local face fan.',
            default => 'Geography PolyhedralSurface topology is not implemented yet.',
        };
        if (PolyhedralSurface::UNAVAILABLE_ERROR === $code) {
            $associated = clone $constraint;
            $associated->payload = $constraint->payload ?? ['severity' => 'warning'];
        }
        $this->context->setConstraint($associated);

        try {
            $this->context->buildViolation($message)->setCode($code)->atPath($path)->addViolation();
        } finally {
            $this->context->setConstraint($constraint);
        }
    }

    /** @param array<mixed> $members
     * @return list<list<list<list<float>>>>
     */
    private function eligible(array $members, PolyhedralSurface $constraint, int $before): array
    {
        $eligible = [];
        foreach ($members as $index => $member) {
            if ($this->isPolygon($member) && [] === $member->getRings()) {
                $this->diagnostic($constraint, PolyhedralSurface::EMPTY_PATCH_ERROR, 'patches[' . $index . ']');
            }
            if ($this->isPolygon($member) && [] !== $member->getRings() && $this->usable($index, $before)) {
                $eligible[] = $this->positions($member);
            }
        }

        return $eligible;
    }

    /** @phpstan-assert-if-true PolygonInterface $member */
    private function isPolygon(mixed $member): bool
    {
        return $member instanceof PolygonInterface;
    }

    /** @return list<list<list<float>>> */
    private function positions(PolygonInterface $polygon): array
    {
        $rings = [];
        foreach ($polygon->getRings() as $ring) {
            $points = array_values(array_filter(array_map(PointCoordinates::position(...), $ring->getPoints())));
            $rings[] = $points;
        }

        return $rings;
    }

    private function surface(PolyhedralSurfaceInterface $value, PolyhedralSurface $constraint): void
    {
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)
            ->validate($value, new Components(groups: $constraint->groups), $this->context->getGroup())
        ;

        /** @var callable(): array<mixed> $getter */
        $getter = [$value, 'getPatches'];
        $members = array_values($getter());
        if (!$value->getDimension()->hasZ()) {
            $this->diagnostic($constraint, PolyhedralSurface::DIMENSION_ERROR);

            return;
        }
        $eligible = $this->eligible($members, $constraint, $before);
        if ([] === $members) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->diagnostic($constraint, PolyhedralSurface::UNAVAILABLE_ERROR);

            return;
        }
        if (count($eligible) !== count($members)) {
            return;
        }
        foreach (PolyhedralTopology::errors($eligible) as $error) {
            $this->diagnostic($constraint, 'polyhedralsurface-' . $error);
        }
    }

    private function usable(int $index, int $before): bool
    {
        foreach ($this->context->getViolations() as $offset => $violation) {
            if ($offset < $before || Polygon::ORIENTATION_ERROR === $violation->getCode()) {
                continue;
            }
            $path = $this->context->getPropertyPath('patches[' . $index . ']');
            $memberPath = $violation->getPropertyPath();
            if ($path === $memberPath || str_starts_with($memberPath, $path . '.')) {
                return false;
            }
        }

        return true;
    }
}
