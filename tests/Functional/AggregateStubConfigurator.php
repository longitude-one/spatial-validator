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

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use Closure;
use LogicException as UnsupportedAggregate;
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\CollectionInterface;
use LongitudeOne\SpatialTypes\Interfaces\CompoundCurveInterface;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\MultiPointInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Configure component accessors on specialized aggregate stubs.
 *
 * @internal
 */
final class AggregateStubConfigurator
{
    /**
     * Configure eager members or a callback used by propagation tests.
     *
     * @param SpatialInterface&Stub $value   aggregate stub to configure
     * @param array|\Closure        $members members or a deferred accessor
     *
     * @phpstan-param array<mixed>|Closure(): array<mixed> $members
     */
    public static function configure(SpatialInterface&Stub $value, array|\Closure $members): void
    {
        $getter = match (true) {
            $value instanceof PolygonInterface => $value->method('getRings'),
            $value instanceof CompoundCurveInterface => $value->method('getCurves'),
            $value instanceof PolyhedralSurfaceInterface => $value->method('getPatches'),
            $value instanceof LineStringInterface,
            $value instanceof CircularStringInterface,
            $value instanceof MultiPointInterface => $value->method('getPoints'),
            $value instanceof CollectionInterface => $value->method('getElements'),
            default => throw new UnsupportedAggregate('Unsupported aggregate stub interface.'),
        };
        if ($members instanceof \Closure) {
            $getter->willReturnCallback($members);

            return;
        }
        $getter->willReturn($members);
    }
}
