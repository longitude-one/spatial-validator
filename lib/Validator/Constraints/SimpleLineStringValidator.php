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
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Internal\SegmentIntersections;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Explicitly check line simplicity in XY or XYZ according to the declared variant. */
final class SimpleLineStringValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleLineString) {
            throw new UnexpectedTypeException($constraint, SimpleLineString::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof LineStringInterface || GeometryTypeEnum::LINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }
        $before = \count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new LineString(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($before !== \count($this->context->getViolations()) || [] === $value->getPoints()) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        $positions = array_values(array_filter(array_map(PointCoordinates::position(...), $value->getPoints())));
        if (!SegmentIntersections::simple($positions)) {
            $this->context->buildViolation('The line has a forbidden segment intersection or overlap.')
                ->setCode(SimpleLineString::INVALID_ERROR)->addViolation()
            ;
        }
    }

    /**
     * Unavailable.
     *
     * @param SimpleLineString $constraint applied validation constraint
     */
    private function unavailable(SimpleLineString $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Geography LineString simplicity is not implemented yet.')
                ->setCode(SimpleLineString::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
