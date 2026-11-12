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
use LongitudeOne\SpatialValidator\Internal\ArcIntersections;
use LongitudeOne\SpatialValidator\Internal\CircularArcs;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Explicitly check actual circular-arc simplicity.
 *
 * @phpstan-import-type Arc from CircularArcs
 * @phpstan-import-type Vector from CircularArcs
 */
final class SimpleCircularStringValidator extends ConstraintValidator
{
    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleCircularString) {
            throw new UnexpectedTypeException($constraint, SimpleCircularString::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof CircularStringInterface || GeometryTypeEnum::CIRCULARSTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, CircularStringInterface::class);
        }
        $before = \count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new CircularString(groups: $constraint->groups, payload: $constraint->payload),
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
        $this->arcs(CircularArcs::build($positions), $constraint);
    }

    /**
     * @param array                $arcs       arcs
     * @param SimpleCircularString $constraint applied validation constraint
     *
     * @phpstan-param list<Arc> $arcs
     */
    private function arcs(array $arcs, SimpleCircularString $constraint): void
    {
        $this->repeatedEndpoints($arcs);
        $count = \count($arcs);
        for ($first = 0; $first < $count; ++$first) {
            for ($second = $first + 1; $second < $count; ++$second) {
                $permitted = $this->permitted($arcs, $first, $second);
                $conflict = ArcIntersections::conflict($arcs[$first], $arcs[$second], $permitted);
                if (null === $conflict) {
                    $this->unavailable($constraint);
                } elseif ($conflict) {
                    $this->context->buildViolation('Circular arcs have a forbidden intersection or overlap.')
                        ->atPath('arcs['.$second.']')->setParameter('{{ first }}', (string) $first)
                        ->setCode(SimpleCircularString::INVALID_ERROR)->addViolation()
                    ;
                }
            }
        }
    }

    /**
     * @param array $arcs   arcs
     * @param int   $first  first operand
     * @param int   $second second operand
     *
     * @phpstan-param list<Arc> $arcs
     *
     * @return list<Vector>
     */
    private function permitted(array $arcs, int $first, int $second): array
    {
        $permitted = [];
        if ($second === $first + 1) {
            $permitted[] = $arcs[$first]['end'];
        }
        if (
            0 === $first && $second === \count($arcs) - 1
            && $arcs[$first]['start'] === $arcs[$second]['end']
        ) {
            $permitted[] = $arcs[$first]['start'];
        }

        return $permitted;
    }

    /**
     * @param array $arcs arcs
     *
     * @phpstan-param list<Arc> $arcs
     */
    private function repeatedEndpoints(array $arcs): void
    {
        $vertices = [$arcs[0]['start']];
        foreach ($arcs as $arc) {
            $vertices[] = $arc['end'];
        }
        $last = \count($vertices) - 1;
        foreach ($vertices as $second => $vertex) {
            for ($first = 0; $first < $second; ++$first) {
                if ($vertices[$first] !== $vertex || (0 === $first && $second === $last)) {
                    continue;
                }
                $this->context->buildViolation('A curve position is repeated outside its permitted global closure.')
                    ->atPath('arcs['.($second - 1).']')
                    ->setCode(SimpleCircularString::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }

    /**
     * Unavailable.
     *
     * @param SimpleCircularString $constraint applied validation constraint
     */
    private function unavailable(SimpleCircularString $constraint): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Circular simplicity is unavailable for this spatial model or arc plane.')
                ->setCode(SimpleCircularString::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
