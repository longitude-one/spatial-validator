<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Support;

use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\Point;
use PHPUnit\Framework\TestCase;

/** Build typed candidates without invoking the model's historical functional checks. */
abstract class SpatialCandidates extends TestCase
{
    /** @param list<array{int|float, int|float, int|float}> $coordinates */
    protected function lineCandidate(array $coordinates): LineStringInterface
    {
        $line = $this->createStub(LineStringInterface::class);
        $line->method('getPoints')->willReturn(array_map(
            static fn (array $position): Point => new Point(...$position),
            $coordinates
        ));
        $line->method('hasZ')->willReturn(true);

        return $line;
    }

    /** @param list<list<array{int|float, int|float, int|float}>> $coordinates */
    protected function polygonCandidate(array $coordinates): PolygonInterface
    {
        $rings = array_map($this->lineCandidate(...), $coordinates);
        $polygon = $this->createStub(PolygonInterface::class);
        $polygon->method('getRings')->willReturn($rings);
        $polygon->method('isEmpty')->willReturn([] === $rings);
        $polygon->method('hasZ')->willReturn(true);
        $polygon->method('getRing')->willReturnCallback(static fn (int $index): LineStringInterface => $rings[$index]);

        return $polygon;
    }

    /** @param list<list<list<array{int|float, int|float, int|float}>>> $coordinates */
    protected function surfaceCandidate(array $coordinates): PolyhedralSurfaceInterface
    {
        $surface = $this->createStub(PolyhedralSurfaceInterface::class);
        $surface->method('getPatches')->willReturn(array_map($this->polygonCandidate(...), $coordinates));
        $surface->method('hasZ')->willReturn(true);

        return $surface;
    }
}
