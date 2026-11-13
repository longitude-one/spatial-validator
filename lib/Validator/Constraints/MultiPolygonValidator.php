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
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\MultiPolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialValidator\Internal\MultiPolygonTopology;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose complete member validation and exact aggregate surface relationships. */
final class MultiPolygonValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MultiPolygon) {
            throw new UnexpectedTypeException($constraint, MultiPolygon::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof MultiPolygonInterface) {
            throw new UnexpectedValueException($value, MultiPolygonInterface::class);
        }

        if (GeometryTypeEnum::MULTIPOLYGON !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::MULTIPOLYGON->value);
        }

        $before = \count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)
            ->validate($value, new Components(groups: $constraint->groups), $this->context->getGroup())
        ;

        /** @var callable(): array<mixed> $getter */
        $getter = [$value, 'getElements'];
        $members = array_values($getter());
        $eligible = [];
        foreach ($members as $index => $member) {
            $geography = SpatialModelEnum::GEOGRAPHY === $value->getFamily();
            if ($this->isPolygon($member) && [] !== $member->getRings()
                && ($geography || $this->usable($index, $before))
            ) {
                $eligible[$index] = $member;
            }
        }
        $this->relationships($eligible, $constraint, SpatialModelEnum::GEOGRAPHY === $value->getFamily());
    }

    /**
     * Diagnostic.
     *
     * @param MultiPolygon $constraint applied validation constraint
     * @param string       $code       violation code
     * @param int          $first      first operand
     * @param int          $second     second operand
     */
    private function diagnostic(MultiPolygon $constraint, string $code, int $first, int $second): void
    {
        $associated = $constraint;
        $message = 'Polygon members {{ first }} and {{ second }} must have disjoint interiors and only isolated boundary contacts.';
        if (MultiPolygon::UNAVAILABLE_ERROR === $code) {
            $associated = clone $constraint;
            $associated->payload = $constraint->payload ?? ['severity' => 'warning'];
            $message = 'Geography MultiPolygon interactions are not implemented yet.';
        }
        $this->context->setConstraint($associated);

        try {
            $this->context->buildViolation($message)->setCode($code)
                ->setParameter('{{ first }}', (string) $first)->setParameter('{{ second }}', (string) $second)
                ->atPath('polygons['.$second.']')->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }

    /**
     * @param mixed $member member
     *
     * @phpstan-assert-if-true PolygonInterface $member
     */
    private function isPolygon(mixed $member): bool
    {
        return $member instanceof PolygonInterface;
    }

    /**
     * @param PolygonInterface $polygon polygon whose ring positions are extracted
     *
     * @return list<list<list<float>>>
     */
    private function positions(PolygonInterface $polygon): array
    {
        $rings = [];
        foreach ($polygon->getRings() as $ring) {
            $points = [];
            foreach ($ring->getPoints() as $point) {
                $position = PointCoordinates::position($point);
                if (null !== $position) {
                    $points[] = 2 === \count($position) ? [...$position, 0.0] : $position;
                }
            }
            $rings[] = $points;
        }

        return $rings;
    }

    /**
     * @param array        $members    spatial members to inspect
     * @param MultiPolygon $constraint applied validation constraint
     * @param bool         $geography  whether the value uses the Geography model
     *
     * @phpstan-param array<int, PolygonInterface> $members
     */
    private function relationships(array $members, MultiPolygon $constraint, bool $geography): void
    {
        foreach ($members as $second => $member) {
            foreach ($members as $first => $other) {
                if ($first >= $second) {
                    break;
                }
                if ($geography) {
                    $this->diagnostic($constraint, MultiPolygon::UNAVAILABLE_ERROR, $first, $second);

                    continue;
                }
                if (MultiPolygonTopology::intersects($this->positions($other), $this->positions($member))) {
                    $this->diagnostic($constraint, MultiPolygon::INTERSECTION_ERROR, $first, $second);
                }
            }
        }
    }

    /**
     * Usable.
     *
     * @param int $index  component index
     * @param int $before number of violations before member validation
     */
    private function usable(int $index, int $before): bool
    {
        foreach ($this->context->getViolations() as $offset => $violation) {
            if ($offset < $before || Polygon::ORIENTATION_ERROR === $violation->getCode()) {
                continue;
            }
            $path = $this->context->getPropertyPath('polygons['.$index.']');
            $memberPath = $violation->getPropertyPath();
            if ($path === $memberPath || str_starts_with($memberPath, $path.'.')) {
                return false;
            }
        }

        return true;
    }
}
