<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Unit\Validator;

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Exception\InvalidDimensionException;
use LongitudeOne\SpatialTypes\Exception\InvalidFamilyException;
use LongitudeOne\SpatialTypes\Exception\InvalidSridException;
use LongitudeOne\SpatialTypes\Exception\InvalidValueException;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialValidator\Tests\Support\SpatialCandidates;
use LongitudeOne\SpatialValidator\Validator\DimensionValidation;
use LongitudeOne\SpatialValidator\Validator\FamilyValidation;
use LongitudeOne\SpatialValidator\Validator\LineStringValidation;
use LongitudeOne\SpatialValidator\Validator\PolyhedralSurfaceValidation;
use LongitudeOne\SpatialValidator\Validator\RingValidation;
use LongitudeOne\SpatialValidator\Validator\SpatialReferenceValidation;
use LongitudeOne\SpatialValidator\Validator\TriangleValidation;

/** @internal */
final class AssertionHelpersTest extends SpatialCandidates
{
    public function testDimensionSuccess(): void
    {
        DimensionValidation::assertSame(CoordinateDimensionEnum::XY, new Point(1, 2), 'dimension');
        $this->addToAssertionCount(1);
    }

    public function testDimensionException(): void
    {
        $this->expectException(InvalidDimensionException::class);
        $this->expectExceptionMessage('dimension');
        DimensionValidation::assertSame(CoordinateDimensionEnum::XYZ, new Point(1, 2), 'dimension');
    }

    public function testFamilySuccess(): void
    {
        FamilyValidation::assertSame(SpatialModelEnum::GEOMETRY, new Point(1, 2), 'family');
        $this->addToAssertionCount(1);
    }

    public function testFamilyException(): void
    {
        $this->expectException(InvalidFamilyException::class);
        $this->expectExceptionMessage('family');
        FamilyValidation::assertSame(SpatialModelEnum::GEOGRAPHY, new Point(1, 2), 'family');
    }

    public function testReferenceSuccess(): void
    {
        SpatialReferenceValidation::assertSame(SpatialReference::fromSrid(4326), new Point(1, 2, 4326), 'point');
        $this->addToAssertionCount(1);
    }

    public function testReferenceException(): void
    {
        $this->expectException(InvalidSridException::class);
        $this->expectExceptionMessage('The point spatial reference is not compatible with the spatial reference '
            . 'of this spatial value.');
        SpatialReferenceValidation::assertSame(SpatialReference::fromSrid(4326), new Point(1, 2), 'point');
    }

    public function testLineSuccess(): void
    {
        LineStringValidation::assertNoConsecutiveDuplicatePoints($this->lineCandidate([[0, 0, 0], [1, 0, 0]]));
        $this->addToAssertionCount(1);
    }

    public function testLineException(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('A line string must not contain consecutive duplicate points.');
        LineStringValidation::assertNoConsecutiveDuplicatePoints($this->lineCandidate([[0, 0, 0], [0, 0, 0]]));
    }

    public function testRingSuccess(): void
    {
        RingValidation::assertValid($this->lineCandidate([[0, 0, 0], [1, 0, 0], [0, 1, 0], [0, 0, 0]]));
        $this->addToAssertionCount(1);
    }

    public function testRingException(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('A linear ring must contain at least 4 points.');
        RingValidation::assertValid($this->lineCandidate([]));
    }

    public function testTriangleSuccess(): void
    {
        TriangleValidation::assertValid($this->polygonCandidate([]));
        $this->addToAssertionCount(1);
    }

    public function testTriangleException(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('A triangle exterior ring must contain exactly four points.');
        TriangleValidation::assertValid($this->polygonCandidate([[[0, 0, 0], [1, 0, 0], [0, 0, 0]]]));
    }

    public function testSurfaceSuccess(): void
    {
        PolyhedralSurfaceValidation::assertValid($this->surfaceCandidate([]));
        $this->addToAssertionCount(1);
    }

    public function testSurfaceException(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('A polyhedral surface must have connected, consistently oriented 3D patches '
            . 'with at most two faces along each edge.');
        PolyhedralSurfaceValidation::assertValid($this->surfaceCandidate([[]]));
    }
}
