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

use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Polygon;
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;
use LongitudeOne\SpatialValidator\Validator\Constraints\Triangle;
use LongitudeOne\SpatialValidator\Validator\Constraints\TriangleValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @covers \LongitudeOne\SpatialValidator\Validator\Constraints\TriangleValidator
 */
class TriangleValidatorTest extends TestCase
{
    /** Accept an empty polygon and a triangular polygon without requiring TriangleInterface. */
    public function testAcceptsPolygonCandidates(): void
    {
        $validator = Validation::createValidator();
        static::assertCount(0, $validator->validate(new Polygon([]), new Triangle()));
        static::assertCount(0, $validator->validate(new Polygon([[[0, 0], [4, 0], [0, 4], [0, 0]]]), new Triangle()));
    }

    /** Validate a third-party polygon implementation with an open boundary through Ring. */
    public function testDelegatesClosureToRing(): void
    {
        $ring = new LineString([[0, 0], [4, 0], [0, 4], [1, 1]]);
        $polygon = static::createStub(PolygonInterface::class);
        $polygon->method('getRings')->willReturn([$ring]);
        $polygon->method('getRing')->willReturn($ring);
        $violations = Validation::createValidator()->validate($polygon, new Triangle());

        static::assertCount(1, $violations);
        static::assertSame('A linear ring must have equal first and last points.', $violations->get(0)->getMessage());
    }

    /** A valid quadrilateral ring is not a triangular boundary. */
    public function testRejectsFourVertices(): void
    {
        $polygon = new Polygon([[[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]]]);
        $violations = Validation::createValidator()->validate($polygon, new Triangle());

        static::assertCount(1, $violations);
        static::assertSame(
            'A triangle exterior ring must contain exactly four points.',
            $violations->get(0)->getMessage()
        );
    }

    /** Report interior rings using the configurable constraint message. */
    public function testRejectsHoles(): void
    {
        $polygon = new Polygon([
            [[0, 0], [10, 0], [0, 10], [0, 0]],
            [[1, 1], [2, 1], [1, 2], [1, 1]],
        ]);
        $constraint = new Triangle();
        $constraint->interiorRingsMessage = 'No holes allowed.';
        $violations = Validation::createValidator()->validate($polygon, $constraint);

        static::assertCount(1, $violations);
        static::assertSame('No holes allowed.', $violations->get(0)->getMessage());
    }

    /** Symfony validators reject constraints belonging to another validator. */
    public function testRejectsUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        (new TriangleValidator())->validate(new Polygon([]), new Ring());
    }

    /** A line string cannot be validated as a polygon. */
    public function testRejectsUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new TriangleValidator())->validate(new LineString([]), new Triangle());
    }
}
