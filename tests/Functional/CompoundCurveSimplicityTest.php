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

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class CompoundCurveSimplicityTest extends TestCase
{
    /** @param list<array{bool, list<list<float|int>>}> $parts */
    #[DataProvider('cases')]
    public function testGlobalTopology(array $parts, bool $simple): void
    {
        $members = [];
        foreach ($parts as [$circular, $coordinates]) {
            $members[] = $circular
                ? CircularStringValidationTest::curve($coordinates) : LineStringValidationTest::line($coordinates);
        }
        $value = CompoundCurveValidationTest::curve($members);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, new SpatialAssert\CompoundCurve()));
        $violations = $service->validate($value, new SpatialAssert\SimpleCompoundCurve());
        self::assertSame($simple, 0 === count($violations));
        foreach ($violations as $violation) {
            self::assertSame(SpatialAssert\SimpleCompoundCurve::INVALID_ERROR, $violation->getCode());
            self::assertStringStartsWith('curves[', $violation->getPropertyPath());
        }
    }

    /** @return iterable<string, array{list<array{bool, list<list<float|int>>}>, bool}> */
    public static function cases(): iterable
    {
        yield 'EMPTY' => [[], true];

        yield 'line and arc join' => [[[false, [[-2, 0], [-1, 0]]], [true, [[-1, 0], [0, 1], [1, 0]]]], true];

        yield 'closed arc and chord' => [[[true, [[-1, 0], [0, 1], [1, 0]]], [false, [[1, 0], [-1, 0]]]], true];

        yield 'closed triangle' => [[[false, [[0, 0], [1, 0]]], [false, [[1, 0], [0, 1], [0, 0]]]], true];

        yield 'line crossing' => [[[false, [[0, 0], [2, 2]]], [false, [[2, 2], [0, 2], [2, 0]]]], false];

        yield 'line overlap' => [[[false, [[0, 0], [2, 0]]], [false, [[2, 0], [1, 0]]]], false];

        yield 'line interior tangency' => [
            [[false, [[0, 0], [2, 0]]], [false, [[2, 0], [2, 1], [1, 0], [0, 1]]]], false];

        yield 'line arc crossing' => [[[true, [[-1, 0], [0, 1], [1, 0]]], [false, [[1, 0], [-1, 2]]]], false];

        yield 'arc line crossing reversed' => [[[false, [[-1, 2], [1, 0]]], [true, [[1, 0], [0, 1], [-1, 0]]]], false];

        yield 'line arc interior tangency' => [
            [[true, [[-1, 0], [0, 1], [1, 0]]], [false, [[1, 0], [2, 0], [2, 1], [-2, 1]]]], false];

        yield 'degenerate arc line overlap' => [[[true, [[0, 0], [1, 0], [2, 0]]], [false, [[2, 0], [1, 0]]]], false];

        yield 'two complementary arcs' => [
            [[true, [[-1, 0], [0, 1], [1, 0]]], [true, [[1, 0], [0, -1], [-1, 0]]]], true];

        yield 'arc retracing' => [[[true, [[-1, 0], [0, 1], [1, 0]]], [true, [[1, 0], [0, 1], [-1, 0]]]], false];

        yield 'two circles tangent at interior join' => [
            [[true, [[1, 0], [-1, 0], [1, 0]]], [true, [[1, 0], [3, 0], [1, 0]]]], false];

        yield 'two circles crossing' => [[[true, [[1, 0], [-1, 0], [1, 0]]], [true, [[1, 0], [0, 1], [1, 0]]]], false];

        yield 'self-intersecting member' => [[[false, [[0, 0], [2, 2], [0, 2], [2, 0]]]], false];

        yield 'single full circle' => [[[true, [[1, 0], [-1, 0], [1, 0]]]], true];

        yield 'boundary repeated internally' => [[[false, [[0, 0], [1, 0], [0, 0], [0, 1]]]], false];
    }

    public function testLocalizedGroupAndPayload(): void
    {
        $value = CompoundCurveValidationTest::curve([LineStringValidationTest::line([[0, 0], [2, 0]]),
            LineStringValidationTest::line([[2, 0], [1, 0]])]);
        $constraint = new SpatialAssert\SimpleCompoundCurve(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $root = ['shape' => $value];
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame('[shape].curves[1].segments[0]', $violations->get(0)->getPropertyPath());
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame($constraint->payload, $violations->get(0)->getConstraint()?->payload);
    }

    public function testNearlyTangentArcIsNotSnapped(): void
    {
        $height = 1.0 + PHP_FLOAT_EPSILON;
        $value = CompoundCurveValidationTest::curve([
            CircularStringValidationTest::curve([[-1, 0], [0, 1], [1, 0]]),
            LineStringValidationTest::line([[1, 0], [2, 0], [2, $height], [-2, $height]]),
        ]);
        self::assertCount(0, Validation::createValidator()->validate($value, new SpatialAssert\SimpleCompoundCurve()));
    }

    public function testNumericalScaleAndAxisPermutations(): void
    {
        $service = Validation::createValidator();
        foreach ([1.0E-7, 1.0, 1.0E100] as $scale) {
            foreach ([0, 1, 2] as $axis) {
                $coordinates = [];
                foreach ([[-1, 0, 0], [0, 1, 0], [1, 0, 0], [-1, 2, 0]] as $point) {
                    $point = array_map(static fn (int $ordinate): float => $ordinate * $scale, $point);
                    $coordinates[] = [$point[$axis], $point[($axis + 1) % 3], $point[($axis + 2) % 3]];
                }
                $value = CompoundCurveValidationTest::curve([
                    CircularStringValidationTest::curve(array_slice($coordinates, 0, 3), CoordinateDimensionEnum::XYZ),
                    LineStringValidationTest::line(array_slice($coordinates, 2), CoordinateDimensionEnum::XYZ),
                ], CoordinateDimensionEnum::XYZ);
                self::assertCount(0, $service->validate($value, new SpatialAssert\CompoundCurve()));
                self::assertGreaterThan(0, count($service->validate($value, new SpatialAssert\SimpleCompoundCurve())));
            }
        }
    }

    public function testUnknownFullCirclePlaneIsExplicit(): void
    {
        $dimension = CoordinateDimensionEnum::XYZ;
        $value = CompoundCurveValidationTest::curve([
            CircularStringValidationTest::curve([[1, 0, 0], [-1, 0, 0], [1, 0, 0]], $dimension),
            LineStringValidationTest::line([[1, 0, 0], [2, 0, 0]], $dimension),
        ], $dimension);
        $constraint = new SpatialAssert\SimpleCompoundCurve();
        $violations = Validation::createValidator()->validate($value, $constraint);
        self::assertGreaterThan(0, count($violations));
        $warnings = [];
        foreach ($violations as $violation) {
            if (SpatialAssert\SimpleCompoundCurve::UNAVAILABLE_ERROR === $violation->getCode()) {
                $warnings[] = $violation;
                self::assertSame(['severity' => 'warning'], $violation->getConstraint()?->payload);
            }
        }
        self::assertCount(1, $warnings);
        self::assertNull($constraint->payload);
    }

    public function testXyzDoesNotUseProjectedCrossing(): void
    {
        $dimension = CoordinateDimensionEnum::XYZ;
        $value = CompoundCurveValidationTest::curve([
            LineStringValidationTest::line([[0, 0, 0], [2, 2, 0]], $dimension),
            LineStringValidationTest::line([[2, 2, 0], [0, 2, 1], [2, 0, 1]], $dimension),
        ], $dimension);
        self::assertCount(0, Validation::createValidator()->validate($value, new SpatialAssert\SimpleCompoundCurve()));
    }
}
