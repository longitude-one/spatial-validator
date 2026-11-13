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

use InvalidArgumentException as UnsupportedSpatialTypeException;
use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Delegate spatial validation to the constraint selected by the declared type.
 *
 * The exhaustive dispatcher intentionally references every specialized constraint.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class SpatialValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Spatial) {
            throw new UnexpectedTypeException($constraint, Spatial::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof SpatialInterface) {
            throw new UnexpectedValueException($value, SpatialInterface::class);
        }

        $constraintClass = $this->constraintClass($value->getType()->value);
        $specialized = new $constraintClass(groups: $constraint->groups);

        $this->context->getValidator()
            ->inContext($this->context)
            ->validate($value, $specialized, $this->context->getGroup())
        ;
    }

    /**
     * Select the specialized constraint for the declared geometry type.
     *
     * @param string $type declared geometry type
     *
     * @return class-string<Constraint>
     */
    private function collectionConstraintClass(string $type): string
    {
        return match ($type) {
            GeometryTypeEnum::GEOMETRYCOLLECTION->value => GeometryCollection::class,
            GeometryTypeEnum::MULTICURVE->value => MultiCurve::class,
            GeometryTypeEnum::MULTILINESTRING->value => MultiLineString::class,
            GeometryTypeEnum::MULTIPOINT->value => MultiPoint::class,
            GeometryTypeEnum::MULTIPOLYGON->value => MultiPolygon::class,
            GeometryTypeEnum::MULTISURFACE->value => MultiSurface::class,
            default => $this->curveConstraintClass($type),
        };
    }

    /**
     * Select the specialized constraint for the declared geometry type.
     *
     * @param string $type declared geometry type
     *
     * @return class-string<Constraint>
     */
    private function constraintClass(string $type): string
    {
        return match ($type) {
            GeometryTypeEnum::BREPSOLID->value => BRepSolid::class,
            GeometryTypeEnum::CURVE->value => Curve::class,
            GeometryTypeEnum::GEOMETRY->value => Geometry::class,
            GeometryTypeEnum::LINESTRING->value => LineString::class,
            GeometryTypeEnum::POINT->value => Point::class,
            GeometryTypeEnum::SOLID->value => Solid::class,
            default => $this->collectionConstraintClass($type),
        };
    }

    /**
     * Select the specialized constraint for the declared geometry type.
     *
     * @param string $type declared geometry type
     *
     * @return class-string<Constraint>
     */
    private function curveConstraintClass(string $type): string
    {
        return match ($type) {
            GeometryTypeEnum::CIRCLE->value => Circle::class,
            GeometryTypeEnum::CIRCULARSTRING->value => CircularString::class,
            GeometryTypeEnum::CLOTHOID->value => Clothoid::class,
            GeometryTypeEnum::COMPOUNDCURVE->value => CompoundCurve::class,
            GeometryTypeEnum::ELLIPTICALCURVE->value => EllipticalCurve::class,
            GeometryTypeEnum::GEODESICSTRING->value => GeodesicString::class,
            GeometryTypeEnum::NURBSCURVE->value => NURBSCurve::class,
            GeometryTypeEnum::SPIRALCURVE->value => SpiralCurve::class,
            default => $this->surfaceConstraintClass($type),
        };
    }

    /**
     * Select the specialized constraint for the declared geometry type.
     *
     * @param string $type declared geometry type
     *
     * @return class-string<Constraint>
     */
    private function surfaceConstraintClass(string $type): string
    {
        return match ($type) {
            GeometryTypeEnum::COMPOUNDSURFACE->value => CompoundSurface::class,
            GeometryTypeEnum::CURVEPOLYGON->value => CurvePolygon::class,
            GeometryTypeEnum::POLYGON->value => Polygon::class,
            GeometryTypeEnum::POLYHEDRALSURFACE->value => PolyhedralSurface::class,
            GeometryTypeEnum::SURFACE->value => Surface::class,
            GeometryTypeEnum::TIN->value => TIN::class,
            GeometryTypeEnum::TRIANGLE->value => Triangle::class,
            default => throw new UnsupportedSpatialTypeException('Unsupported spatial type: '.$type),
        };
    }
}
