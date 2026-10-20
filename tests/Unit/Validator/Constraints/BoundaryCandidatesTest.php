<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Unit\Validator\Constraints;

use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Tests\Support\SpatialCandidates;
use LongitudeOne\SpatialValidator\Validator\Constraints\PolyhedralSurface;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleThreeDimensionalLineString;
use LongitudeOne\SpatialValidator\Validator\Internal\PlanarPatch;
use LongitudeOne\SpatialValidator\Validator\Internal\PolyhedralSurfaceTopology;
use Symfony\Component\Validator\Validation;

/** @internal */
final class BoundaryCandidatesTest extends SpatialCandidates
{
    public function testSurfaceWithoutZHasOriginalViolation(): void
    {
        $surface = $this->createStub(PolyhedralSurfaceInterface::class);
        $surface->method('hasZ')->willReturn(false);
        $constraint = new PolyhedralSurface();
        $violations = Validation::createValidator()->validate($surface, $constraint);
        static::assertCount(1, $violations);
        static::assertSame($constraint->message, $violations->get(0)->getMessage());
        static::assertNull($violations->get(0)->getCode());
    }

    public function testEmptyAndShortPatchHaveNoPlane(): void
    {
        static::assertFalse(PlanarPatch::isValid($this->polygonCandidate([])));
        static::assertFalse(PlanarPatch::isValid($this->polygonCandidate([[[0, 0, 0], [1, 0, 0], [0, 0, 0]]])));
    }

    public function testNonFinitePositionHasNoPlane(): void
    {
        $point = $this->createStub(PointInterface::class);
        $point->method('getX')->willReturn(INF);
        $point->method('getY')->willReturn(0);
        $point->method('getZ')->willReturn(0);
        $line = $this->createStub(LineStringInterface::class);
        $line->method('getPoints')->willReturn([$point]);
        $patch = $this->createStub(PolygonInterface::class);
        $patch->method('getRings')->willReturn([$line]);
        static::assertFalse(PlanarPatch::isValid($patch));
    }

    public function testZeroLengthEdgesKeepExistingTopologyBehavior(): void
    {
        $patch = $this->polygonCandidate([[[0, 0, 0], [0, 0, 0]]]);
        static::assertTrue(PolyhedralSurfaceTopology::isValid([$patch]));
        $line = $this->lineCandidate([[0, 0, 0], [0, 0, 0], [1, 0, 0]]);
        static::assertCount(0, Validation::createValidator()->validate($line, new SimpleThreeDimensionalLineString()));
    }
}
