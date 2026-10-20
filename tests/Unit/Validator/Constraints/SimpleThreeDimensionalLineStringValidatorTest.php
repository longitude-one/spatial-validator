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

use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\LineString;
use LongitudeOne\SpatialTypes\Types\Dimension4zm\Geometry\LineString as FourDimensionalLineString;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleThreeDimensionalLineString;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @covers \LongitudeOne\SpatialValidator\Validator\Constraints\SimpleThreeDimensionalLineStringValidator
 *
 * @internal
 */
class SimpleThreeDimensionalLineStringValidatorTest extends TestCase
{
    /** Test that crossing only in the XY projection remains simple in XYZ space. */
    public function testAcceptsSegmentsAtDifferentElevations(): void
    {
        $lineString = new LineString([[0, 0, 0], [2, 2, 0], [0, 2, 1], [2, 0, 1]]);

        static::assertCount(
            0,
            Validation::createValidator()->validate(
                $lineString,
                new SimpleThreeDimensionalLineString()
            )
        );
    }

    /** Test that M does not separate an XYZ intersection in an XYZM line string. */
    public function testIgnoresMeasureInFourDimensions(): void
    {
        $lineString = new FourDimensionalLineString([[0, 0, 0, 1], [2, 2, 2, 1], [0, 2, 2, 2], [2, 0, 0, 2]]);

        static::assertCount(
            1,
            Validation::createValidator()->validate(
                $lineString,
                new SimpleThreeDimensionalLineString()
            )
        );
    }

    /** Test that a spatial intersection makes an XYZ line string non-simple. */
    public function testRejectsAnXyzIntersection(): void
    {
        $lineString = new LineString([[0, 0, 0], [2, 2, 2], [0, 2, 2], [2, 0, 0]]);

        static::assertCount(
            1,
            Validation::createValidator()->validate(
                $lineString,
                new SimpleThreeDimensionalLineString()
            )
        );
    }
}
