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

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geography\Point as GeographicPoint;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\Point as ThreeDimensionalPoint;
use LongitudeOne\SpatialValidator\Validator\Constraints\FirstPointEqualsLastPoint;
use LongitudeOne\SpatialValidator\Validator\Constraints\MinimumPointCount;
use LongitudeOne\SpatialValidator\Validator\Constraints\NoConsecutiveDuplicatePoints;
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameDimension;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameFamily;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameSpatialReference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @covers \LongitudeOne\SpatialValidator\Validator\Constraints\RingValidator
 *
 * @internal
 */
class RingValidatorTest extends TestCase
{
    /** Test that the constraint accepts a valid point sequence. */
    public function testNoConsecutiveDuplicatePointsAcceptsDistinctAdjacentPoints(): void
    {
        $violations = Validation::createValidator()->validate(
            new LineString([[0, 0], [1, 0], [0, 1], [0, 0]]),
            new NoConsecutiveDuplicatePoints()
        );

        static::assertCount(0, $violations);
    }

    /** Test that four closed points form a valid ring. */
    public function testRingAcceptsFourClosedPoints(): void
    {
        $violations = Validation::createValidator()->validate(
            new LineString([[0, 0], [1, 0], [0, 1], [0, 0]]),
            new Ring()
        );

        static::assertCount(0, $violations);
    }

    /** Test that a ring must have equal first and last points. */
    public function testRingReportsTheClosureConstraint(): void
    {
        $violations = Validation::createValidator()->validate(
            new LineString([[0, 0], [1, 0], [0, 1], [2, 2]]),
            new Ring()
        );

        static::assertCount(1, $violations);
        static::assertSame('A linear ring must have equal first and last points.', $violations->get(0)->getMessage());
    }

    /** Test that a ring needs at least four points. */
    public function testRingReportsTheMinimumPointConstraint(): void
    {
        $violations = Validation::createValidator()->validate(
            new LineString([[0, 0], [1, 0], [0, 0]]),
            new Ring()
        );

        static::assertCount(1, $violations);
        static::assertSame('A linear ring must contain at least 4 points.', $violations->get(0)->getMessage());
    }

    /** Test that the required coordinate dimension is validated independently. */
    public function testSameDimensionDetectsAnIncompatibleDimension(): void
    {
        $validator = Validation::createValidator();

        static::assertCount(0, $validator->validate(new Point(1, 2), new SameDimension(CoordinateDimensionEnum::XY)));
        static::assertCount(
            1,
            $validator->validate(
                new ThreeDimensionalPoint(
                    1,
                    2,
                    3
                ),
                new SameDimension(CoordinateDimensionEnum::XY)
            )
        );
    }

    /** Test that the required spatial family is validated independently. */
    public function testSameFamilyDetectsAnIncompatibleFamily(): void
    {
        $validator = Validation::createValidator();

        static::assertCount(0, $validator->validate(new Point(1, 2), new SameFamily(SpatialModelEnum::GEOMETRY)));
        static::assertCount(
            1,
            $validator->validate(
                new GeographicPoint(
                    1,
                    2
                ),
                new SameFamily(SpatialModelEnum::GEOMETRY)
            )
        );
    }

    /** Test that the required spatial reference is validated independently. */
    public function testSameSpatialReferenceDetectsAnIncompatibleReference(): void
    {
        $validator = Validation::createValidator();

        static::assertCount(
            0,
            $validator->validate(
                new Point(
                    1,
                    2,
                    4326
                ),
                new SameSpatialReference(SpatialReference::fromSrid(4326))
            )
        );
        static::assertCount(
            1,
            $validator->validate(
                new Point(
                    1,
                    2
                ),
                new SameSpatialReference(SpatialReference::fromSrid(4326))
            )
        );
    }

    /** Test that each structural constraint is usable independently. */
    public function testStructuralConstraintsCanBeUsedIndependently(): void
    {
        $lineString = new LineString([[0, 0], [1, 0], [0, 1], [2, 2]]);
        $validator = Validation::createValidator();

        static::assertCount(0, $validator->validate($lineString, new MinimumPointCount()));
        static::assertCount(1, $validator->validate($lineString, new FirstPointEqualsLastPoint()));
    }
}
