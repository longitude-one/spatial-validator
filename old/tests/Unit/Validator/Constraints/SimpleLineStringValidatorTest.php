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
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\Point as ThreeDimensionalPoint;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleLineString;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @covers \LongitudeOne\SpatialValidator\Validator\Constraints\SimpleLineStringValidator
 *
 * @internal
 */
class SimpleLineStringValidatorTest extends TestCase
{
    /**
     * Verify the segment pairs that are permitted by two-dimensional simplicity.
     *
     * @param array<array{0: float|int, 1: float|int}> $coordinates Coordinates to validate
     */
    #[DataProvider('provideSimpleTwoDimensionalSegmentations')]
    public function testAcceptsPermittedTwoDimensionalSegmentations(array $coordinates): void
    {
        static::assertCount(0, $this->validate($coordinates));
    }

    /**
     * @return \Generator<string, array{0: array<array{0: float|int, 1: float|int}>}, null, void>
     */
    public static function provideSimpleTwoDimensionalSegmentations(): \Generator
    {
        yield 'single segment' => [[[0, 0], [2, 0]]];

        yield 'adjacent non-collinear segments share their endpoint' => [[[0, 0], [2, 0], [2, 2]]];

        yield 'adjacent collinear segments continue in the same direction' => [[[0, 0], [1, 0], [2, 0]]];

        yield 'first and last segments of a closed line share the closing endpoint' => [
            [[0, 0], [2, 0], [2, 2], [0, 2], [0, 0]],
        ];
    }

    /** Test that a simple closed line string is simple. */
    public function testAcceptsSimpleClosedLineString(): void
    {
        $violations = $this->validate([[0, 0], [2, 0], [2, 2], [0, 2], [0, 0]]);

        static::assertCount(0, $violations);
    }

    /** Test that an open line without repeated locations is simple. */
    public function testAcceptsSimpleOpenLineString(): void
    {
        $violations = $this->validate([[0, 0], [2, 0], [2, 2]]);

        static::assertCount(0, $violations);
    }

    /** Test that simplicity ignores Z ordinates. */
    public function testIgnoresZordinates(): void
    {
        $lineString = static::createStub(LineStringInterface::class);
        $lineString->method('getPoints')->willReturn([
            new ThreeDimensionalPoint(0, 0, 0),
            new ThreeDimensionalPoint(2, 2, 0),
            new ThreeDimensionalPoint(0, 2, 1),
            new ThreeDimensionalPoint(2, 0, 1),
        ]);
        $violations = Validation::createValidator()->validate($lineString, new SimpleLineString());

        static::assertCount(1, $violations);
    }

    /** Test that adjacent segments cannot retrace a common interval. */
    public function testRejectsAnAdjacentSegmentOverlap(): void
    {
        $violations = $this->validate([[0, 0], [2, 0], [1, 0]]);

        static::assertCount(1, $violations);
    }

    /**
     * Verify that every prohibited two-dimensional segment intersection is rejected.
     *
     * @param array<array{0: float|int, 1: float|int}> $coordinates Coordinates to validate
     */
    #[DataProvider('provideNonSimpleTwoDimensionalSegmentations')]
    public function testRejectsProhibitedTwoDimensionalSegmentIntersections(array $coordinates): void
    {
        static::assertCount(1, $this->validate($coordinates));
    }

    /**
     * @return \Generator<string, array{0: array<array{0: float|int, 1: float|int}>}, null, void>
     */
    public static function provideNonSimpleTwoDimensionalSegmentations(): \Generator
    {
        yield 'non-neighbouring segments cross' => [[[0, 0], [2, 2], [0, 2], [2, 0]]];

        yield 'non-neighbouring segment touches an interior point' => [[[0, 0], [2, 0], [2, 2], [1, 0]]];

        yield 'adjacent segments retrace a common interval' => [[[0, 0], [2, 0], [1, 0]]];

        yield 'non-neighbouring collinear segments overlap' => [[[0, 0], [4, 0], [4, 2], [1, 0], [3, 0]]];

        yield 'closed line self-intersects away from its closing endpoint' => [
            [[0, 0], [2, 2], [0, 2], [2, 0], [0, 0]],
        ];
    }

    /** Test that a proper crossing makes a line string non-simple. */
    public function testRejectsSelfIntersection(): void
    {
        $violations = $this->validate([[0, 0], [2, 2], [0, 2], [2, 0]]);

        static::assertCount(1, $violations);
    }

    /** Test that a non-neighbouring contact makes a line string non-simple. */
    public function testRejectsSelfTangency(): void
    {
        $violations = $this->validate([[0, 0], [2, 0], [2, 2], [1, 0]]);

        static::assertCount(1, $violations);
    }

    /**
     * Validate a two-dimensional geometric line string.
     *
     * @param array<array{0: float|int, 1: float|int}> $coordinates Coordinates to validate
     */
    private function validate(array $coordinates): ConstraintViolationListInterface
    {
        $lineString = static::createStub(LineStringInterface::class);
        $lineString->method('getPoints')->willReturn(array_map(
            static fn (array $coordinate): Point => new Point($coordinate[0], $coordinate[1]),
            $coordinates
        ));

        return Validation::createValidator()->validate($lineString, new SimpleLineString());
    }
}
