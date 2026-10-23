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
use LongitudeOne\Core\Enum\SpatialModelEnum as Family;
use LongitudeOne\SpatialTypes\Interfaces\TriangleInterface;
use LongitudeOne\SpatialValidator\Internal\PolygonStructure;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class TriangleValidationTest extends TestCase
{
    public function testAllVariantsScalesAndAxisPermutations(): void
    {
        foreach (Dimension::cases() as $dimension) {
            foreach ([1.0e-10, 1.0, 1.0e150, 1.0e307] as $scale) {
                foreach ([[0, 1, 2], [2, 0, 1], [1, 2, 0]] as $axes) {
                    $points = [[0, 0, 0], [0, $scale, 0], [0, 0, $scale], [0, 0, 0]];
                    if (!$dimension->hasZ()) {
                        $points = [[0, 0], [$scale, 0], [0, $scale], [0, 0]];
                    }
                    if ($dimension->hasZ()) {
                        $points = array_map(static fn (array $point): array => array_map(
                            static fn (int $axis) => $point[$axis],
                            $axes
                        ), $points);
                    }
                    if ($dimension->hasM()) {
                        $points = array_map(
                            static fn (array $point, int $index): array => [...$point, $index * 17],
                            $points,
                            array_keys($points)
                        );
                    }
                    $triangle = PolygonStructureTest::triangle([$points], $dimension);
                    $violations = Validation::createValidator()->validate($triangle, new Rules\Spatial());
                    self::assertCount(0, $violations, (string) $violations);
                }
            }
        }
        $skinny = [[1, 1], [2, 2], [3, 3 + 4 * PHP_FLOAT_EPSILON], [1, 1]];
        $triangle = PolygonStructureTest::triangle([$skinny]);
        self::assertCount(0, Validation::createValidator()->validate($triangle, new Rules\Triangle()));
    }

    public function testApproximateClosureNeverHidesAnExactCrossing(): void
    {
        $origin = 1.0e18;
        $relative = [[0, 0], [1024, 0], [-1024, 128], [128, 128]];
        $ring = array_map(static fn (array $point): array => [$origin + $point[0], $origin + $point[1]], $relative);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(LineStringValidationTest::line($ring), new Rules\Ring()));
        foreach ([new Rules\Triangle(), new Rules\Spatial()] as $constraint) {
            $violations = $service->validate(PolygonStructureTest::triangle([$ring]), $constraint);
            self::assertCount(1, $violations, (string) $violations);
            self::assertSame(Rules\Triangle::TOPOLOGY_ERROR, $violations->get(0)->getCode());
        }
    }

    public function testEmptyFlagsAndGeographyWarnings(): void
    {
        $service = Validation::createValidator();
        $missing = $service->validate(PolygonStructureTest::triangle([], empty: false), new Rules\Triangle());
        self::assertSame(PolygonStructure::EMPTY_ERROR, $missing->get(0)->getCode());
        $rings = [[[0, 0], [1, 0], [0, 1], [0, 0]]];
        $hidden = $service->validate(PolygonStructureTest::triangle($rings, empty: true), new Rules\Triangle());
        self::assertSame(PolygonStructure::EMPTY_ERROR, $hidden->get(0)->getCode());
        $empty = PolygonStructureTest::triangle([], family: Family::GEOGRAPHY);
        self::assertCount(0, $service->validate($empty, new Rules\Triangle()));
        $rings[] = [];
        $geography = PolygonStructureTest::triangle($rings, family: Family::GEOGRAPHY);
        $violations = $service->validate($geography, new Rules\Triangle());
        $codes = [];
        foreach ($violations as $violation) {
            $codes[] = $violation->getCode();
            $warning = in_array($violation->getCode(), [Rules\FirstPointEqualsLastPoint::UNAVAILABLE_ERROR,
                Rules\SimpleLineString::UNAVAILABLE_ERROR, PolygonStructure::UNAVAILABLE_ERROR], true);
            self::assertSame($warning ? ['severity' => 'warning'] : null, $violation->getConstraint()?->payload);
        }
        self::assertContains(Rules\Triangle::INTERIOR_ERROR, $codes);
        self::assertContains(Rules\Ring::EMPTY_ERROR, $codes);
        self::assertContains(PolygonStructure::UNAVAILABLE_ERROR, $codes);
    }

    /** @param list<list<list<null|float|int>>> $rings */
    #[DataProvider('candidates')]
    public function testExplicitAndGenericRules(array $rings, ?string $code): void
    {
        foreach ([new Rules\Triangle(), new Rules\Spatial()] as $constraint) {
            $violations = Validation::createValidator()->validate(PolygonStructureTest::triangle($rings), $constraint);
            $codes = [];
            foreach ($violations as $violation) {
                $codes[] = $violation->getCode();
                self::assertNull($violation->getConstraint()?->payload);
            }
            if (null === $code) {
                self::assertSame([], $codes, (string) $violations);

                continue;
            }
            self::assertContains($code, $codes, (string) $violations);
        }
    }

    /** @return iterable<string, array{list<list<list<null|float|int>>>, ?string}> */
    public static function candidates(): iterable
    {
        $valid = [[0, 0], [1, 0], [0, 1], [0, 0]];

        yield 'EMPTY' => [[], null];

        yield 'valid XY' => [[$valid], null];

        yield 'collinear' => [[[[0, 0], [1, 1], [2, 2], [0, 0]]], PolygonStructure::DEGENERATE_ERROR];

        yield 'repeated' => [[[[0, 0], [1, 1], [0, 0], [0, 0]]], Rules\Triangle::DISTINCT_ERROR];

        yield 'four distinct vertices' => [[[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]],
            Rules\Triangle::POSITION_COUNT_ERROR];

        yield 'open' => [[[[0, 0], [1, 0], [0, 1], [2, 2]]], Rules\FirstPointEqualsLastPoint::INVALID_ERROR];

        yield 'EMPTY ring' => [[[]], Rules\Ring::EMPTY_ERROR];

        yield 'EMPTY point' => [[[[null, null], [1, 0], [0, 1], [0, 0]]], Rules\NonEmptyPoints::INVALID_ERROR];

        yield 'non finite point' => [[[[0, 0], [INF, 0], [0, 1], [0, 0]]], Rules\FiniteCoordinates::INVALID_ERROR];

        yield 'hole' => [[$valid, [[0.1, 0.1], [0.2, 0.1], [0.1, 0.2], [0.1, 0.1]]], Rules\Triangle::INTERIOR_ERROR];
    }

    public function testGroupsPayloadAndLocalizedContext(): void
    {
        $triangle = PolygonStructureTest::triangle([[[0, 0], [1, 0], [1, 1], [0, 1], [0, 0]]]);
        $root = ['shape' => $triangle];
        $payload = ['consumer' => true];
        $rule = new Rules\Triangle(groups: ['custom'], payload: $payload);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($triangle, $rule));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $rule], groups: ['custom']),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame('[shape].rings[0]', $violations->get(0)->getPropertyPath());
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame($rule, $violations->get(0)->getConstraint());
        self::assertSame($payload, $violations->get(0)->getConstraint()->payload);
    }

    public function testPolygonCoordinatesNeverBroadenTriangleInput(): void
    {
        $polygon = PolygonStructureTest::polygon([[[0, 0], [1, 0], [0, 1], [0, 0]]]);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($polygon, new Rules\Spatial()));
        $violations = $service->validate($polygon, new Rules\Triangle());
        self::assertCount(1, $violations);
        self::assertSame(TriangleInterface::class, $violations->get(0)->getParameters()['{{ type }}']);
        $this->expectException(UnexpectedValueException::class);
        (new Rules\TriangleValidator())->validate($polygon, new Rules\Triangle());
    }
}
