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
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\CompoundCurveInterface;
use LongitudeOne\SpatialTypes\Interfaces\CurveInterface;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Compose member validity before inspecting contributing endpoints. */
final class CompoundCurveValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CompoundCurve) {
            throw new UnexpectedTypeException($constraint, CompoundCurve::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof CompoundCurveInterface) {
            throw new UnexpectedValueException($value, CompoundCurveInterface::class);
        }
        if (GeometryTypeEnum::COMPOUNDCURVE !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::COMPOUNDCURVE->value);
        }
        $before = \count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new Components(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($before !== \count($this->context->getViolations())) {
            return;
        }
        $this->joins($value, $constraint);
    }

    /**
     * @param array                  $previous   previous
     * @param array                  $start      start
     * @param CompoundCurveInterface $value      spatial value to validate
     * @param CompoundCurve          $constraint applied validation constraint
     * @param string                 $path       property path for the diagnostic
     *
     * @phpstan-param list<float> $previous
     * @phpstan-param list<float> $start
     */
    private function join(
        array $previous,
        array $start,
        CompoundCurveInterface $value,
        CompoundCurve $constraint,
        string $path
    ): void {
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $warning = clone $constraint;
            $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
            $this->context->setConstraint($warning);

            try {
                $this->context->buildViolation('Geography compound-curve continuity is unavailable.')
                    ->atPath($path)->setCode(CompoundCurve::UNAVAILABLE_ERROR)->addViolation()
                ;
            } finally {
                $this->context->setConstraint($constraint);
            }

            return;
        }
        if (!PointCoordinates::same($previous, $start)) {
            $this->context->buildViolation('The component must start at the preceding component endpoint.')
                ->atPath($path)->setCode(CompoundCurve::JOIN_ERROR)->addViolation()
            ;
        }
    }

    /**
     * Joins.
     *
     * @param CompoundCurveInterface $value      spatial value to validate
     * @param CompoundCurve          $constraint applied validation constraint
     */
    private function joins(CompoundCurveInterface $value, CompoundCurve $constraint): void
    {
        $previous = null;
        foreach (array_values($value->getCurves()) as $index => $curve) {
            $points = $this->positions($curve);
            $path = 'curves['.$index.']';
            if ([] === $points) {
                $this->context->buildViolation('A contributing curve must not be EMPTY.')
                    ->atPath($path)->setCode(CompoundCurve::EMPTY_ERROR)->addViolation()
                ;
                $previous = null;

                continue;
            }
            if (null !== $previous) {
                $this->join($previous, $points[0], $value, $constraint, $path);
            }
            $previous = $points[\count($points) - 1];
        }
    }

    /**
     * @param CurveInterface $curve curve
     *
     * @return list<list<float>>
     */
    private function positions(CurveInterface $curve): array
    {
        $points = $curve instanceof LineStringInterface || $curve instanceof CircularStringInterface
                ? $curve->getPoints() : [];

        return array_values(array_filter(array_map(PointCoordinates::position(...), $points)));
    }
}
