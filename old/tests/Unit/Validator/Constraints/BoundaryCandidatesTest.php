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

/**
 * @internal
 *
 * @coversNothing
 */
final class BoundaryCandidatesTest extends SpatialCandidates
{
    public function testEmptyAndShortPatchHaveNoPlane(): void
    {
        self::assertFalse(PlanarPatch::isValid($this->polygonCandidate([])));
        self::assertFalse(PlanarPatch::isValid($this->polygonCandidate([[[0, 0, 0], [1, 0, 0], [0, 0, 0]]])));
    }

    public function testNonFinitePositionHasNoPlane(): void
    {
        $point = self::createStub(PointInterface::class);
        $point->method('getX')->willReturn(INF);
        $point->method('getY')->willReturn(0);
        $point->method('getZ')->willReturn(0);
        $line = self::createStub(LineStringInterface::class);
        $line->method('getPoints')->willReturn([$point]);
        $patch = self::createStub(PolygonInterface::class);
        $patch->method('getRings')->willReturn([$line]);
        self::assertFalse(PlanarPatch::isValid($patch));
    }

    public function testSurfaceWithoutZHasOriginalViolation(): void
    {
        $surface = self::createStub(PolyhedralSurfaceInterface::class);
        $surface->method('hasZ')->willReturn(false);
        $constraint = new PolyhedralSurface();
        $violations = Validation::createValidator()->validate($surface, $constraint);
        self::assertCount(1, $violations);
        self::assertSame($constraint->message, $violations->get(0)->getMessage());
        self::assertNull($violations->get(0)->getCode());
    }

    public function testZeroLengthEdgesKeepExistingTopologyBehavior(): void
    {
        $patch = $this->polygonCandidate([[[0, 0, 0], [0, 0, 0]]]);
        self::assertTrue(PolyhedralSurfaceTopology::isValid([$patch]));
        $line = $this->lineCandidate([[0, 0, 0], [0, 0, 0], [1, 0, 0]]);
        self::assertCount(0, Validation::createValidator()->validate($line, new SimpleThreeDimensionalLineString()));
    }
}
