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
use LongitudeOne\SpatialTypes\Interfaces\CompoundCurveInterface;
use LongitudeOne\SpatialValidator\Internal\ArcIntersections;
use LongitudeOne\SpatialValidator\Internal\CircularArcs;
use LongitudeOne\SpatialValidator\Internal\CompoundCurveArcs;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Explicitly check whole compound-curve simplicity.
 *
 * @phpstan-import-type Arc from CircularArcs
 * @phpstan-import-type Vector from CircularArcs
 */
final class SimpleCompoundCurveValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleCompoundCurve) {
            throw new UnexpectedTypeException($constraint, SimpleCompoundCurve::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof CompoundCurveInterface || GeometryTypeEnum::COMPOUNDCURVE !== $value->getType()) {
            throw new UnexpectedValueException($value, CompoundCurveInterface::class);
        }
        $before = count($this->context->getViolations());
        $this->context->getValidator()->inContext($this->context)->validate(
            $value,
            new CompoundCurve(groups: $constraint->groups, payload: $constraint->payload),
            $this->context->getGroup()
        );
        if ($this->invalidSince($before) || [] === $value->getCurves()) {
            return;
        }
        if (SpatialModelEnum::GEOGRAPHY === $value->getFamily()) {
            $this->unavailable($constraint);

            return;
        }
        [$arcs, $paths] = CompoundCurveArcs::build($value);
        $this->arcs($arcs, $paths, $constraint);
    }

    /**
     * @param list<Arc>    $arcs
     * @param list<string> $paths
     */
    private function arcs(array $arcs, array $paths, SimpleCompoundCurve $constraint): void
    {
        $this->repeatedEndpoints($arcs, $paths);
        $count = count($arcs);
        for ($first = 0; $first < $count; ++$first) {
            for ($second = $first + 1; $second < $count; ++$second) {
                $permitted = $this->permitted($arcs, $first, $second);
                $conflict = ArcIntersections::conflict($arcs[$first], $arcs[$second], $permitted);
                if (null === $conflict) {
                    $this->unavailable($constraint, $paths[$second]);
                } elseif ($conflict) {
                    $this->context->buildViolation('Compound-curve parts have a forbidden intersection or overlap.')
                        ->atPath($paths[$second])->setParameter('{{ first }}', (string) $first)
                        ->setCode(SimpleCompoundCurve::INVALID_ERROR)->addViolation()
                    ;
                }
            }
        }
    }

    private function invalidSince(int $before): bool
    {
        foreach ($this->context->getViolations() as $index => $violation) {
            if ($index >= $before && CompoundCurve::UNAVAILABLE_ERROR !== $violation->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<Arc> $arcs
     *
     * @return list<Vector>
     */
    private function permitted(array $arcs, int $first, int $second): array
    {
        $permitted = [];
        if ($second === $first + 1 && $arcs[$first]['end'] === $arcs[$second]['start']) {
            $permitted[] = $arcs[$first]['end'];
        }
        if (
            0 === $first && $second === count($arcs) - 1
            && $arcs[$first]['start'] === $arcs[$second]['end']
        ) {
            $permitted[] = $arcs[$first]['start'];
        }

        return $permitted;
    }

    /**
     * @param list<Arc>    $arcs
     * @param list<string> $paths
     */
    private function repeatedEndpoints(array $arcs, array $paths): void
    {
        $vertices = [$arcs[0]['start']];
        foreach ($arcs as $arc) {
            $vertices[] = $arc['end'];
        }
        $last = count($vertices) - 1;
        foreach ($vertices as $second => $vertex) {
            for ($first = 0; $first < $second; ++$first) {
                if ($vertices[$first] !== $vertex || (0 === $first && $second === $last)) {
                    continue;
                }
                $this->context->buildViolation('A curve position is repeated outside its permitted global closure.')
                    ->atPath($paths[$second - 1])
                    ->setCode(SimpleCompoundCurve::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }

    private function unavailable(SimpleCompoundCurve $constraint, string $path = ''): void
    {
        $warning = clone $constraint;
        $warning->payload = $constraint->payload ?? ['severity' => 'warning'];
        $this->context->setConstraint($warning);

        try {
            $this->context->buildViolation('Compound-curve simplicity is unavailable for this model or arc plane.')
                ->atPath($path)->setCode(SimpleCompoundCurve::UNAVAILABLE_ERROR)->addViolation()
            ;
        } finally {
            $this->context->setConstraint($constraint);
        }
    }
}
