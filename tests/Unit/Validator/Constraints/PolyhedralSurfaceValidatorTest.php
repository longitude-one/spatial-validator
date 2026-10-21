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

use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\PolyhedralSurface;
use LongitudeOne\SpatialTypes\Types\Dimension4zm\Geometry\PolyhedralSurface as PolyhedralSurface4zm;
use LongitudeOne\SpatialValidator\Tests\Support\SpatialCandidates;
use LongitudeOne\SpatialValidator\Validator\Constraints\PolyhedralSurface as SurfaceConstraint;
use LongitudeOne\SpatialValidator\Validator\Constraints\PolyhedralSurfaceValidator;
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @covers \LongitudeOne\SpatialValidator\Validator\Constraints\PolyhedralSurfaceValidator
 * @covers \LongitudeOne\SpatialValidator\Validator\Internal\PlanarPatch
 * @covers \LongitudeOne\SpatialValidator\Validator\Internal\PolyhedralSurfaceTopology
 */
class PolyhedralSurfaceValidatorTest extends SpatialCandidates
{
    /**
     * @param array $patches Coordinates
     * @param bool  $valid   Expected validity
     *
     * @phpstan-param list<list<list<array{int, int, int}>>> $patches
     */
    #[DataProvider('boundaries')]
    public function testBoundaryTopology(array $patches, bool $valid): void
    {
        $surface = $this->surfaceCandidate($patches);
        $violations = Validation::createValidator()->validate($surface, new SurfaceConstraint());
        static::assertSame($valid, 0 === count($violations));
        if (!$valid) {
            static::assertNull($violations->get(0)->getCode());
        }
    }

    /** @return iterable<string, array{list<list<list<array{int, int, int}>>>, bool}> */
    public static function boundaries(): iterable
    {
        $first = [[[0, 0, 0], [2, 0, 0], [0, 2, 0], [0, 0, 0]]];
        $second = [[[2, 0, 0], [0, 0, 0], [0, 0, 2], [2, 0, 0]]];

        yield 'empty surface' => [[], true];

        yield 'single open face' => [[$first], true];

        yield 'folded open surface' => [[$first, $second], true];

        yield 'coplanar open surface' => [[$first, [[[2, 0, 0], [0, 0, 0], [0, -2, 0], [2, 0, 0]]]], true];

        yield 'closed tetrahedron' => [[
            $first, $second,
            [[[0, 2, 0], [2, 0, 0], [0, 0, 2], [0, 2, 0]]],
            [[[0, 0, 0], [0, 2, 0], [0, 0, 2], [0, 0, 0]]],
        ], true];

        yield 'split common edge' => [[$first, [[[2, 0, 0], [1, 0, 0], [0, 0, 0], [0, 0, 2], [2, 0, 0]]]], true];

        yield 'partial common edge' => [[$first, [[[1, 0, 0], [0, 0, 0], [0, 0, 2], [1, 0, 0]]]], true];

        yield 'third face along edge' => [[$first, $second, [[[0, 0, 0], [2, 0, 0], [0, -2, 0], [0, 0, 0]]]], false];

        yield 'same orientation' => [[$first, [array_reverse($second[0])]], false];

        yield 'vertex contact only' => [[$first, [[[0, 0, 0], [-2, 0, 0], [0, 0, 2], [0, 0, 0]]]], false];

        yield 'empty patch' => [[[]], false];

        yield 'non planar patch' => [[[[[0, 0, 0], [2, 0, 0], [2, 2, 1], [0, 2, 0], [0, 0, 0]]]], false];

        yield 'collinear patch' => [[[[[0, 0, 0], [1, 0, 0], [2, 0, 0], [0, 0, 0]]]], false];

        yield 'self crossing ring' => [[[[[0, 0, 0], [2, 2, 0], [0, 2, 0], [2, 0, 0], [0, 0, 0]]]], false];

        yield 'valid hole' => [[[
            [[0, 0, 0], [4, 0, 0], [4, 4, 0], [0, 4, 0], [0, 0, 0]],
            [[1, 1, 0], [1, 2, 0], [2, 2, 0], [2, 1, 0], [1, 1, 0]],
        ]], true];
        $farFirst = $first;
        $farSecond = $second;
        foreach ($farFirst[0] as &$point) {
            $point[0] += 10;
        }
        unset($point);
        foreach ($farSecond[0] as &$point) {
            $point[0] += 10;
        }

        yield 'two disconnected components with neighbours' => [[$first, $second, $farFirst, $farSecond], false];
    }

    /** Measures are not spatial coordinates of shared edges. */
    public function testDifferentMeasuresDoNotChangeAdjacency(): void
    {
        $surface = new PolyhedralSurface4zm([
            [[[0, 0, 0, 1], [2, 0, 0, 1], [0, 2, 0, 1], [0, 0, 0, 1]]],
            [[[2, 0, 0, 8], [0, 0, 0, 8], [0, 0, 2, 8], [2, 0, 0, 8]]],
        ]);
        static::assertCount(0, Validation::createValidator()->validate($surface, new SurfaceConstraint()));
    }

    /** Numeric zero signs must not disconnect a common edge. */
    public function testSignedZeroDoesNotChangeAdjacency(): void
    {
        $surface = new PolyhedralSurface([
            [[[0, 0, 0], [2, 0, 0], [0, 2, 0], [0, 0, 0]]],
            [[[2, -0.0, 0], [-0.0, 0, 0], [0, 0, 2], [2, -0.0, 0]]],
        ]);
        static::assertCount(0, Validation::createValidator()->validate($surface, new SurfaceConstraint()));
    }

    /** Reject a different Symfony constraint. */
    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        (new PolyhedralSurfaceValidator())->validate(new PolyhedralSurface(), new Ring());
    }

    /** Reject a non-surface value. */
    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new PolyhedralSurfaceValidator())->validate(42, new SurfaceConstraint());
    }
}
