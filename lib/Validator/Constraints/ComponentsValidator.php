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

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialValidator\Internal\ComponentMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Traverse components with an ancestor stack, never a global visited set. */
final class ComponentsValidator extends ConstraintValidator
{
    /** @var array<int, true> */
    private array $ancestors = [];

    /**
     * Validate the supplied spatial value.
     *
     * @param mixed      $value      spatial value to validate
     * @param Constraint $constraint applied validation constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Components) {
            throw new UnexpectedTypeException($constraint, Components::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof SpatialInterface) {
            throw new UnexpectedValueException($value, SpatialInterface::class);
        }

        $identity = spl_object_id($value);
        $this->ancestors[$identity] = true;

        try {
            $this->traverse($value, $constraint);
        } finally {
            unset($this->ancestors[$identity]);
        }
    }

    /**
     * Compatibility.
     *
     * @param SpatialInterface $parent parent
     * @param SpatialInterface $member member
     * @param string           $path   property path for the diagnostic
     */
    private function compatibility(SpatialInterface $parent, SpatialInterface $member, string $path): void
    {
        foreach (ComponentMetadata::errors($parent, $member) as $code => $message) {
            $this->violation($message, $code, $path, $member);
        }
    }

    /**
     * @param mixed $member member
     * @param array $types  types
     *
     * @phpstan-assert-if-true SpatialInterface $member
     *
     * @phpstan-param list<string> $types
     */
    private function isPermitted(mixed $member, array $types): bool
    {
        if (!$member instanceof SpatialInterface) {
            return false;
        }
        if ([] === $types) {
            return true;
        }
        $type = $member->getType()->value;
        $interface = 'LongitudeOne\SpatialTypes\Interfaces\\'.$type.'Interface';

        return \in_array($type, $types, true) && $member instanceof $interface;
    }

    /**
     * @param string $type type
     *
     * @return array{string, string, list<string>}|null
     */
    private function plan(string $type): ?array
    {
        return match ($type) {
            'LineString', 'CircularString', 'MultiPoint' => ['getPoints', 'points', ['Point']],
            'Polygon', 'Triangle' => ['getRings', 'rings', ['LineString']],
            'GeometryCollection' => ['getElements', 'geometries', []],
            'MultiLineString' => ['getElements', 'lineStrings', ['LineString']],
            'MultiPolygon' => ['getElements', 'polygons', ['Polygon', 'Triangle']],
            'CompoundCurve' => ['getCurves', 'curves', ['LineString', 'CircularString']],
            'PolyhedralSurface' => ['getPatches', 'patches', ['Polygon', 'Triangle']],
            default => null,
        };
    }

    /**
     * Report cycle.
     *
     * @param mixed  $member member
     * @param string $path   property path for the diagnostic
     */
    private function reportCycle(mixed $member, string $path): bool
    {
        if (!$member instanceof SpatialInterface || !isset($this->ancestors[spl_object_id($member)])) {
            return false;
        }
        $this->violation(
            'A spatial component introduces a reference cycle.',
            Components::CYCLE_ERROR,
            $path,
            $member,
        );

        return true;
    }

    /**
     * Traverse.
     *
     * @param SpatialInterface $parent     parent
     * @param Components       $constraint applied validation constraint
     */
    private function traverse(SpatialInterface $parent, Components $constraint): void
    {
        $plan = $this->plan($parent->getType()->value);
        if (null === $plan) {
            return;
        }
        [$method, $segment, $types] = $plan;
        $type = 'GeometryCollection' === $parent->getType()->value ? 'Collection' : $parent->getType()->value;
        $interface = 'LongitudeOne\SpatialTypes\Interfaces\\'.$type.'Interface';
        if (!$parent instanceof $interface) {
            throw new UnexpectedValueException($parent, $interface);
        }

        /** @var callable(): array<mixed> $getter */
        $getter = [$parent, $method];
        $members = $getter();
        foreach (array_values($members) as $index => $member) {
            $path = $segment.'['.$index.']';
            if ($member instanceof SpatialInterface) {
                $this->compatibility($parent, $member, $path);
            }
            $cyclic = $this->reportCycle($member, $path);
            if (!$this->isPermitted($member, $types)) {
                $this->violation(
                    'The component does not implement its permitted spatial type.',
                    Components::TYPE_ERROR,
                    $path,
                    $member,
                );

                continue;
            }
            if ($cyclic) {
                continue;
            }
            $this->context->getValidator()->inContext($this->context)->atPath($path)
                ->validate($member, new Spatial(groups: $constraint->groups), $this->context->getGroup())
            ;
        }
    }

    /**
     * Violation.
     *
     * @param string $message message
     * @param string $code    violation code
     * @param string $path    property path for the diagnostic
     * @param mixed  $value   spatial value to validate
     */
    private function violation(string $message, string $code, string $path, mixed $value): void
    {
        $this->context->buildViolation($message)->atPath($path)->setInvalidValue($value)
            ->setCode($code)->addViolation()
        ;
    }
}
