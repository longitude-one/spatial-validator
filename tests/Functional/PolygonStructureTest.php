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

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use LongitudeOne\Core\Enum\CoordinateDimensionEnum as Dimension;
use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum as Family;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\TriangleInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialValidator\Internal\PolygonPlane;
use LongitudeOne\SpatialValidator\Internal\PolygonStructure;
use LongitudeOne\SpatialValidator\Internal\PolygonStructureValidator;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass as UnsupportedObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class PolygonStructureTest extends TestCase
{
    /** @param list<list<list<null|float|int>>> $coordinates */
    public static function polygon(
        array $coordinates,
        Dimension $dimension = Dimension::XY,
        Family $family = Family::GEOMETRY,
        ?bool $empty = null,
    ): PolygonInterface {
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        $polygon->method('getFamily')->willReturn($family);
        $polygon->method('getDimension')->willReturn($dimension);
        $polygon->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $polygon->method('isEmpty')->willReturn($empty ?? [] === $coordinates);
        $rings = array_map(
            static fn (array $ring) => LineStringValidationTest::line($ring, $dimension, $family),
            $coordinates
        );
        $polygon->method('getRings')->willReturn($rings);

        return $polygon;
    }

    /** @param list<list<list<null|float|int>>> $coordinates */
    public static function triangle(
        array $coordinates,
        Dimension $dimension = Dimension::XY,
        Family $family = Family::GEOMETRY,
        ?bool $empty = null,
    ): TriangleInterface {
        $triangle = self::createStub(TriangleInterface::class);
        $triangle->method('getType')->willReturn(GeometryTypeEnum::TRIANGLE);
        $triangle->method('getFamily')->willReturn($family);
        $triangle->method('getDimension')->willReturn($dimension);
        $triangle->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $triangle->method('isEmpty')->willReturn($empty ?? [] === $coordinates);
        $triangle->method('getRings')->willReturn(array_map(
            static fn (array $ring) => LineStringValidationTest::line($ring, $dimension, $family),
            $coordinates
        ));

        return $triangle;
    }

    public function testBoundaryDeclaredTypeIsCheckedBeforeRingDelegation(): void
    {
        $ring = self::createStub(LineStringInterface::class);
        $ring->method('getType')->willReturn(GeometryTypeEnum::CIRCULARSTRING);
        $ring->method('getDimension')->willReturn(Dimension::XY);
        $ring->method('getFamily')->willReturn(Family::GEOMETRY);
        $ring->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        $polygon->method('getDimension')->willReturn(Dimension::XY);
        $polygon->method('getFamily')->willReturn(Family::GEOMETRY);
        $polygon->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $polygon->method('getRings')->willReturn([$ring]);
        $violations = Validation::createValidator()->validate($polygon, new PolygonStructure());
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\Components::TYPE_ERROR, $violations->get(0)->getCode());
        self::assertSame('rings[0]', $violations->get(0)->getPropertyPath());
    }

    public function testDirectUnsupportedInput(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new PolygonStructureValidator())->validate('', new PolygonStructure());
    }

    public function testEmptyFlagsNeverHideMembers(): void
    {
        $service = Validation::createValidator();
        $missing = $service->validate(self::polygon([], empty: false), new PolygonStructure());
        self::assertSame(PolygonStructure::EMPTY_ERROR, $missing->get(0)->getCode());
        $hidden = self::polygon([[[0, 0], [2, 0], [0, 2], [0, 0]]], empty: true);
        $violations = $service->validate($hidden, new PolygonStructure());
        self::assertSame(PolygonStructure::EMPTY_ERROR, $violations->get(0)->getCode());
    }

    public function testGeographyWarnsAndRetainsExplicitPayload(): void
    {
        $polygon = self::polygon([[[0, 0], [2, 0], [0, 2], [0, 0]]], family: Family::GEOGRAPHY);
        foreach ([null, ['consumer' => true]] as $payload) {
            $constraint = new PolygonStructure(payload: $payload);
            $violations = Validation::createValidator()->validate($polygon, $constraint);
            self::assertCount(3, $violations);
            self::assertSame(PolygonStructure::UNAVAILABLE_ERROR, $violations->get(2)->getCode());
            self::assertNotSame($constraint, $violations->get(2)->getConstraint());
            self::assertSame($payload ?? ['severity' => 'warning'], $violations->get(2)->getConstraint()?->payload);
            self::assertSame($payload, $constraint->payload);
        }
        $empty = self::polygon([], family: Family::GEOGRAPHY);
        self::assertCount(0, Validation::createValidator()->validate($empty, new PolygonStructure()));
    }

    public function testInputServiceAndPublicCoverageRemainExplicit(): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, new PolygonStructure()));
        foreach (['', 'POLYGON EMPTY', false, 42, [], new UnsupportedObject()] as $value) {
            self::assertCount(1, $service->validate($value, new PolygonStructure()));
        }
        foreach ([new SpatialAssert\Polygon(), new SpatialAssert\Spatial()] as $constraint) {
            self::assertCount(0, $service->validate(self::polygon([]), $constraint));
        }
        self::assertNull(PolygonPlane::basis([]));
    }

    public function testMetadataAndWrongDeclaredBoundaryType(): void
    {
        $ring = LineStringValidationTest::line([[0, 0], [2, 0], [0, 2], [0, 0]]);
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        $polygon->method('getFamily')->willReturn(Family::GEOGRAPHY);
        $polygon->method('getDimension')->willReturn(Dimension::XYZ);
        $polygon->method('getSpatialReference')->willReturn(SpatialReference::epsg(4326));
        $polygon->method('getRings')->willReturn([$ring]);
        $violations = Validation::createValidator()->validate($polygon, new PolygonStructure());
        self::assertCount(4, $violations);
        self::assertSame(SpatialAssert\Components::FAMILY_ERROR, $violations->get(0)->getCode());
        self::assertSame(SpatialAssert\Components::DIMENSION_ERROR, $violations->get(1)->getCode());
        self::assertSame(SpatialAssert\Components::REFERENCE_ERROR, $violations->get(2)->getCode());
    }

    public function testPlaneDiagnosticsAndPayload(): void
    {
        $polygon = self::polygon([[[0, 0, 0], [2, 0, 0], [2, 2, 0], [0, 2, 1], [0, 0, 0]]], Dimension::XYZ);
        $constraint = new PolygonStructure(payload: ['consumer' => true]);
        $violations = Validation::createValidator()->validate($polygon, $constraint);
        self::assertCount(1, $violations);
        self::assertSame('rings[0].points[3]', $violations->get(0)->getPropertyPath());
        self::assertSame(PolygonStructure::PLANE_ERROR, $violations->get(0)->getCode());
        self::assertSame($constraint, $violations->get(0)->getConstraint());
    }

    public function testPointErrorsAreReportedOnceInTheActiveContext(): void
    {
        $polygon = self::polygon([[[0, 0], [INF, 0], [0, 2], [0, 0]]]);
        $root = ['polygon' => $polygon];
        $constraint = new PolygonStructure(groups: ['custom'], payload: ['consumer' => true]);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($polygon, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['polygon' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(2, $violations);
        self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertNull($violations->get(0)->getConstraint()?->payload);
        foreach ($violations as $violation) {
            self::assertSame('[polygon].rings[0].points[1]', $violation->getPropertyPath());
            self::assertSame($root, $violation->getRoot());
            self::assertSame(['custom'], $violation->getConstraint()?->groups);
        }
    }

    /** @param list<list<list<null|float|int>>> $coordinates */
    #[DataProvider('structures')]
    public function testStructures(array $coordinates, Dimension $dimension, bool $valid): void
    {
        $polygon = self::polygon($coordinates, $dimension);
        $violations = Validation::createValidator()->validate($polygon, new PolygonStructure());
        self::assertSame($valid, 0 === count($violations), (string) $violations);
    }

    /** @return iterable<string, array{list<list<list<null|float|int>>>, Dimension, bool}> */
    public static function structures(): iterable
    {
        $triangle = [[0, 0], [4, 0], [0, 4], [0, 0]];

        yield 'EMPTY' => [[], Dimension::XY, true];

        yield 'triangle' => [[$triangle], Dimension::XY, true];

        yield 'hole' => [[$triangle, [[1, 1], [2, 1], [1, 2], [1, 1]]], Dimension::XY, true];

        yield 'outside hole left for K' => [[$triangle, [[10, 10], [11, 10], [10, 11], [10, 10]]], Dimension::XY, true];

        yield 'empty exterior with hole' => [[[], $triangle], Dimension::XY, false];

        yield 'empty hole' => [[$triangle, []], Dimension::XY, false];

        yield 'open hole' => [[$triangle, [[1, 1], [2, 1], [1, 2], [2, 2]]], Dimension::XY, false];

        yield 'crossed hole' => [[$triangle, [[1, 1], [2, 2], [1, 2], [2, 1], [1, 1]]], Dimension::XY, false];

        yield 'collinear exterior' => [[[[0, 0], [1, 1], [2, 2], [0, 0]]], Dimension::XY, false];

        yield 'duplicate before plane' => [[[[0, 0], [0, 0], [2, 0], [0, 2], [0, 0]]], Dimension::XY, false];

        yield 'invalid point' => [[[[0, 0], [INF, 0], [0, 2], [0, 0]]], Dimension::XY, false];

        yield 'empty point' => [[[[0, 0], [null, null], [0, 2], [0, 0]]], Dimension::XY, false];

        yield 'M is not a plane coordinate' => [
            [[[0, 0, 8], [2, 0, 100], [0, 2, -50], [0, 0, 9]]],
            Dimension::XYM,
            true,
        ];

        yield 'XYZM ignores M' => [
            [[[0, 0, 0, 8], [2, 0, 2, 100], [0, 2, 2, -50], [0, 0, 0, 9]]],
            Dimension::XYZM,
            true,
        ];

        yield 'nonfinite M' => [[[[0, 0, 8], [2, 0, INF], [0, 2, -50], [0, 0, 9]]], Dimension::XYM, false];
        foreach ([1.0e-10, 1.0, 1.0e150, 1.0e308] as $scale) {
            foreach ([[0, 1, 2], [2, 0, 1], [1, 2, 0]] as $permutation) {
                foreach ([false, true] as $vertical) {
                    $ring = [[0, 0, 0], [$scale, 0, $scale], [$scale, $scale, $scale], [0, $scale, 0], [0, 0, 0]];
                    if ($vertical) {
                        $ring = [[0, 0, 0], [0, $scale, 0], [0, $scale, $scale], [0, 0, $scale], [0, 0, 0]];
                    }
                    $ring = array_map(
                        static fn (array $point): array => array_map(
                            static fn (int $axis) => $point[$axis],
                            $permutation
                        ),
                        $ring
                    );

                    yield 'plane ' . $scale . implode('', $permutation) . (int) $vertical => [
                        [$ring],
                        Dimension::XYZ,
                        true,
                    ];
                }
            }
        }

        yield 'noncoplanar fourth vertex' => [
            [[[0, 0, 0], [2, 0, 0], [2, 2, 0], [0, 2, 1], [0, 0, 0]]],
            Dimension::XYZ,
            false,
        ];

        yield 'near plane is not in plane' => [
            [[[0, 0, 0], [2, 0, 0], [2, 2, 0], [0, 2, PHP_FLOAT_EPSILON], [0, 0, 0]]],
            Dimension::XYZ,
            false,
        ];

        yield 'near degenerate but nonzero' => [
            [[[0, 0], [1, 1], [2, 2 + 2 * PHP_FLOAT_EPSILON], [0, 0]]],
            Dimension::XY,
            true,
        ];

        yield 'off plane hole' => [
            [[[0, 0, 0], [4, 0, 0], [0, 4, 0], [0, 0, 0]], [[1, 1, 1], [2, 1, 1], [1, 2, 1], [1, 1, 1]]],
            Dimension::XYZ,
            false,
        ];
    }

    public function testWrongConstraintPrecedesNull(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        (new PolygonStructureValidator())->validate(null, new Assert\NotNull());
    }
}
