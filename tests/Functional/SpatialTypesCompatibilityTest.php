<?php

/**
 * This file is part of the spatial project.
 *
 * PHP 8.4 | 8.5
 *
 * Copyright Alexandre Tranchant <alexandre.tranchant@gmail.com> 2026
 * Copyright Longitude One 2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2 as MutableTypes;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry as ImmutableTypes;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpatialTypesCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{SpatialInterface, SpatialInterface}> */
    public static function geometries(): iterable
    {
        $ring = [[0, 0], [4, 0], [0, 4], [0, 0]];
        yield 'MutableTypes LineString' => [
            new MutableTypes\LineString([[0, 0], [1, 1]]),
            new MutableTypes\LineString([[0, 0]]),
        ];

        yield 'ImmutableTypes LineString' => [
            new ImmutableTypes\LineStringImmutable([[0, 0], [1, 1]]),
            new ImmutableTypes\LineStringImmutable([[0, 0]]),
        ];

        yield 'MutableTypes CircularString' => [
            new MutableTypes\CircularString([[-1, 0], [0, 1], [1, 0]]),
            new MutableTypes\CircularString([[0, 0], [1, 1]]),
        ];

        yield 'ImmutableTypes CircularString' => [
            new ImmutableTypes\CircularStringImmutable([[-1, 0], [0, 1], [1, 0]]),
            new ImmutableTypes\CircularStringImmutable([[0, 0], [1, 1]]),
        ];

        yield 'MutableTypes Polygon' => [
            new MutableTypes\Polygon([$ring]),
            new MutableTypes\Polygon([[]]),
        ];

        yield 'ImmutableTypes Polygon' => [
            new ImmutableTypes\PolygonImmutable([$ring]),
            new ImmutableTypes\PolygonImmutable([[]]),
        ];

        yield 'MutableTypes Triangle' => [
            new MutableTypes\Triangle([$ring]),
            new MutableTypes\Triangle([[[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]]]),
        ];

        yield 'ImmutableTypes Triangle' => [
            new ImmutableTypes\TriangleImmutable([$ring]),
            new ImmutableTypes\TriangleImmutable([[[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]]]),
        ];

        yield 'MutableTypes MultiPoint' => [
            new MutableTypes\MultiPoint([[0, 0], [1, 1]]),
            new MutableTypes\MultiPoint([[1, null]]),
        ];

        yield 'ImmutableTypes MultiPoint' => [
            new ImmutableTypes\MultiPointImmutable([[0, 0], [1, 1]]),
            new ImmutableTypes\MultiPointImmutable([[1, null]]),
        ];

        yield 'MutableTypes MultiLineString' => [
            new MutableTypes\MultiLineString([[[0, 0], [1, 1]]]),
            new MutableTypes\MultiLineString([[[0, 0]]]),
        ];

        yield 'ImmutableTypes MultiLineString' => [
            new ImmutableTypes\MultiLineStringImmutable([[[0, 0], [1, 1]]]),
            new ImmutableTypes\MultiLineStringImmutable([[[0, 0]]]),
        ];

        yield 'MutableTypes MultiPolygon' => [
            new MutableTypes\MultiPolygon([[$ring]]),
            new MutableTypes\MultiPolygon([[[]]]),
        ];

        yield 'ImmutableTypes MultiPolygon' => [
            new ImmutableTypes\MultiPolygonImmutable([[$ring]]),
            new ImmutableTypes\MultiPolygonImmutable([[[]]]),
        ];

        yield 'mutable CompoundCurve' => [
            new MutableTypes\CompoundCurve([new MutableTypes\LineString([[0, 0], [1, 1]])]),
            new MutableTypes\CompoundCurve([new MutableTypes\LineString([])]),
        ];

        yield 'immutable CompoundCurve' => [
            new ImmutableTypes\CompoundCurveImmutable([new ImmutableTypes\LineStringImmutable([[0, 0], [1, 1]])]),
            new ImmutableTypes\CompoundCurveImmutable([new ImmutableTypes\LineStringImmutable([])]),
        ];

        yield 'mutable SpatialCollection' => [
            new MutableTypes\SpatialCollection([new MutableTypes\Point(1, 2)]),
            new MutableTypes\SpatialCollection([new MutableTypes\Point(1, null)]),
        ];

        yield 'immutable GeometryCollection' => [
            new ImmutableTypes\GeometryCollectionImmutable(elements: [new ImmutableTypes\PointImmutable(1, 2)]),
            new ImmutableTypes\GeometryCollectionImmutable(elements: [new ImmutableTypes\PointImmutable(1, null)]),
        ];
    }

    /**
     * @param SpatialInterface $valid   valid value
     * @param SpatialInterface $invalid invalid value
     */
    #[DataProvider('geometries')]
    public function testCurrentModelsRetainGeometricValidation(SpatialInterface $valid, SpatialInterface $invalid): void
    {
        $validator = Validation::createValidator();
        self::assertCount(0, $validator->validate($valid, new Rules\Spatial()));
        $violations = $validator->validate($invalid, new Rules\Spatial());
        self::assertGreaterThan(0, \count($violations));
        foreach ($violations as $violation) {
            self::assertNotNull($violation->getCode());
            self::assertNull($violation->getConstraint()?->payload);
        }
    }
}
