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
use LongitudeOne\SpatialValidator\Internal\MultiPolygonTopology;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/** @internal
 * @coversNothing
 */
final class MultiPolygonValidationTest extends TestCase
{
    /** @return iterable<string, array{list<list<list<float|int>>>, list<list<list<float|int>>>, bool, Dimension}> */
    public static function planarPairs(): iterable
    {
        $square = [[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]];
        $inside = [[2, 2], [4, 2], [4, 4], [2, 4], [2, 2]];
        $hole = [[1, 1], [1, 7], [7, 7], [7, 1], [1, 1]];

        yield 'empty' => [[], [], false, Dimension::XY];

        yield 'one empty' => [[$square], [], false, Dimension::XY];

        yield 'separate' => [[$square], [[[10, 0], [12, 0], [12, 2], [10, 2], [10, 0]]], false, Dimension::XY];

        yield 'vertex contact' => [[$square], [[[8, 8], [10, 8], [8, 10], [8, 8]]], false, Dimension::XY];

        yield 'edge interior vertex contact' => [[$square], [[[8, 4], [10, 3], [10, 5], [8, 4]]], false, Dimension::XY];

        yield 'shared edge' => [[$square], [[[8, 0], [10, 0], [10, 8], [8, 8], [8, 0]]], true, Dimension::XY];

        yield 'crossing' => [[$square], [[[6, 2], [10, 2], [10, 6], [6, 6], [6, 2]]], true, Dimension::XY];

        yield 'containment' => [[$square], [$inside], true, Dimension::XY];

        yield 'identical' => [[$square], [$square], true, Dimension::XY];

        yield 'inside hole' => [[$square, $hole], [$inside], false, Dimension::XY];

        yield 'hole boundary shared' => [
            [$square, $hole],
            [[[1, 2], [3, 2], [3, 4], [1, 4], [1, 2]]],
            true,
            Dimension::XY,
        ];

        $near = 8 + 16 * PHP_FLOAT_EPSILON;

        yield 'near but disjoint' => [
            [$square],
            [[[$near, 0], [10, 0], [10, 2], [$near, 2], [$near, 0]]],
            false,
            Dimension::XY,
        ];
    }

    /** @return iterable<string, array{list<list<list<float|int>>>, list<list<list<float|int>>>, bool, Dimension}> */
    public static function spatialPairs(): iterable
    {
        $square = [[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]];
        $inside = [[2, 2], [4, 2], [4, 4], [2, 4], [2, 2]];
        $hole = [[1, 1], [1, 7], [7, 7], [7, 1], [1, 1]];
        $flat = self::lift($square, 0);

        yield 'parallel planes' => [[$flat], [self::lift($square, 10)], false, Dimension::XYZ];

        yield 'coplanar XYZ' => [[$flat], [self::lift($inside, 0)], true, Dimension::XYZ];

        yield 'interior intersection' => [
            [$flat],
            [[[4, -2, -1], [4, 10, -1], [4, 10, 1], [4, -2, 1], [4, -2, -1]]],
            true,
            Dimension::XYZ,
        ];

        yield 'filled intersection without boundary crossing' => [
            [$flat],
            [[[4, 2, -1], [4, 6, -1], [4, 6, 1], [4, 2, 1], [4, 2, -1]]],
            true,
            Dimension::XYZ,
        ];

        yield 'other plane inside hole' => [
            [$flat, self::lift($hole, 0)],
            [[[4, 2, -1], [4, 6, -1], [4, 6, 1], [4, 2, 1], [4, 2, -1]]],
            false,
            Dimension::XYZ,
        ];

        yield 'different plane point contact' => [
            [$flat],
            [[[8, 8, 0], [10, 8, 1], [8, 10, 0], [8, 8, 0]]],
            false,
            Dimension::XYZ,
        ];

        yield 'different plane shared boundary interval' => [
            [$flat],
            [[[0, 0, 0], [8, 0, 0], [8, 0, 2], [0, 0, 2], [0, 0, 0]]],
            true,
            Dimension::XYZ,
        ];

        yield 'rational interior cut' => [
            [$flat],
            [[[4, 2, -1], [4, 6, -1], [4, 6, 2], [4, 2, 2], [4, 2, -1]]],
            true,
            Dimension::XYZ,
        ];

        yield 'boundary through another interior' => [
            [$flat],
            [[[4, 2, 0], [4, 6, 0], [4, 6, 2], [4, 2, 2], [4, 2, 0]]],
            true,
            Dimension::XYZ,
        ];

        yield 'single boundary point in another interior' => [
            [$flat],
            [[[4, 4, 0], [4, 2, 1], [4, 6, 1], [4, 4, 0]]],
            false,
            Dimension::XYZ,
        ];

        yield 'single plane contact outside surface' => [
            [$flat],
            [[[10, 4, 0], [10, 2, 1], [10, 6, 1], [10, 4, 0]]],
            false,
            Dimension::XYZ,
        ];

        yield 'partial boundary overlap different planes' => [
            [$flat],
            [[[2, 0, 0], [4, 0, 0], [4, 0, 2], [2, 0, 2], [2, 0, 0]]],
            true,
            Dimension::XYZ,
        ];

        yield 'different plane disjoint' => [
            [$flat],
            [[[10, 0, -1], [10, 8, -1], [10, 8, 1], [10, 0, 1], [10, 0, -1]]],
            false,
            Dimension::XYZ,
        ];
    }

    /** @param list<list<float|int>> $ring
     * @return list<list<float|int>>
     */
    private static function lift(array $ring, float $height): array
    {
        return array_map(static fn (array $point): array => [...$point, $height], $ring);
    }

    /** @param list<list<list<float|int>>> $rings
     * @param list<int> $axes
     *
     * @return list<list<list<float>>>
     */
    private static function transform(array $rings, array $axes, float $scale): array
    {
        $result = [];
        foreach ($rings as $ring) {
            $positions = [];
            foreach ($ring as $point) {
                $positions[] = array_map(static fn (int $axis): float => $point[$axis] * $scale, $axes);
            }
            $result[] = $positions;
        }

        return $result;
    }

    public function testDegeneratePlaneIsDefensivelyDeclined(): void
    {
        $rings = [[[0.0, 0.0, 0.0], [1.0, 0.0, 0.0], [2.0, 0.0, 0.0]]];
        self::assertFalse(MultiPolygonTopology::intersects($rings, $rings));
    }

    public function testExactTopologyAtScalesAndAxisPermutations(): void
    {
        $datasets = iterator_to_array(self::pairs());
        $names = ['parallel planes', 'filled intersection without boundary crossing', 'other plane inside hole',
            'different plane point contact', 'different plane shared boundary interval', 'coplanar XYZ',
            'single boundary point in another interior', 'boundary through another interior'];
        foreach ($names as $name) {
            [$first, $second, $invalid] = $datasets[$name];
            foreach ([1.0e-10, 1.0, 1.0e150, 1.0e307] as $scale) {
                foreach ([[0, 1, 2], [2, 0, 1], [1, 2, 0]] as $axes) {
                    self::assertSame($invalid, MultiPolygonTopology::intersects(
                        self::transform($first, $axes, $scale),
                        self::transform($second, $axes, $scale)
                    ), $name);
                }
            }
        }
    }

    /** @param list<list<list<float|int>>> $first
     * @param list<list<list<float|int>>> $second
     */
    #[DataProvider('pairs')]
    public function testFilledSurfaceRelationships(
        array $first,
        array $second,
        bool $invalid,
        Dimension $dimension,
    ): void {
        $members = [PolygonStructureTest::polygon($first, $dimension),
            PolygonStructureTest::polygon($second, $dimension)];
        foreach ([$members, array_reverse($members)] as $ordered) {
            foreach ([new Rules\MultiPolygon(), new Rules\Spatial()] as $constraint) {
                $value = CollectionValidationTest::aggregate('MultiPolygon', $ordered, $dimension);
                $violations = Validation::createValidator()->validate($value, $constraint);
                self::assertCount($invalid ? 1 : 0, $violations, (string) $violations);
                if ($invalid) {
                    self::assertSame(Rules\MultiPolygon::INTERSECTION_ERROR, $violations->get(0)->getCode());
                    self::assertSame('polygons[1]', $violations->get(0)->getPropertyPath());
                    self::assertNull($violations->get(0)->getConstraint()?->payload);
                }
                self::assertSame($ordered, $value->getElements());
            }
        }
    }

    /** @return iterable<string, array{list<list<list<float|int>>>, list<list<list<float|int>>>, bool, Dimension}> */
    public static function pairs(): iterable
    {
        yield from self::planarPairs();

        yield from self::spatialPairs();
    }

    public function testGeographyReportsDistinctAggregateWarning(): void
    {
        $member = PolygonStructureTest::polygon([[[0, 0], [2, 0], [0, 2], [0, 0]]], family: Family::GEOGRAPHY);
        foreach ([null, ['custom' => true]] as $payload) {
            $constraint = new Rules\MultiPolygon(payload: $payload);
            $value = CollectionValidationTest::aggregate('MultiPolygon', [$member, $member], family: Family::GEOGRAPHY);
            $violations = Validation::createValidator()->validate($value, $constraint);
            $last = $violations->get(count($violations) - 1);
            self::assertSame(Rules\MultiPolygon::UNAVAILABLE_ERROR, $last->getCode());
            self::assertSame($payload ?? ['severity' => 'warning'], $last->getConstraint()?->payload);
            self::assertSame($payload, $constraint->payload);
        }
        $value = CollectionValidationTest::aggregate('MultiPolygon', [$member], family: Family::GEOGRAPHY);
        foreach (Validation::createValidator()->validate($value, new Rules\MultiPolygon()) as $violation) {
            self::assertNotSame(Rules\MultiPolygon::UNAVAILABLE_ERROR, $violation->getCode());
        }
    }

    public function testGroupsPayloadNestedDiagnosticsAndInvalidMemberIsolation(): void
    {
        $square = [[[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]]];
        $member = PolygonStructureTest::polygon($square);
        $outside = [[20, 2], [20, 4], [22, 4], [22, 2], [20, 2]];
        $invalid = PolygonStructureTest::polygon([...$square, $outside]);
        $value = CollectionValidationTest::aggregate('MultiPolygon', [$invalid, $member, $member, '']);
        $constraint = new Rules\MultiPolygon(groups: ['custom'], payload: ['user' => true]);
        $root = ['surface' => $value];
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['surface' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(3, $violations, (string) $violations);
        self::assertSame('[surface].polygons[0].rings[1]', $violations->get(0)->getPropertyPath());
        self::assertSame(Rules\Polygon::OUTSIDE_ERROR, $violations->get(0)->getCode());
        self::assertSame(Rules\Components::TYPE_ERROR, $violations->get(1)->getCode());
        $last = $violations->get(2);
        self::assertSame(Rules\MultiPolygon::INTERSECTION_ERROR, $last->getCode());
        self::assertSame('[surface].polygons[2]', $last->getPropertyPath());
        self::assertSame(['user' => true], $last->getConstraint()?->payload);
        self::assertSame(['custom'], $last->getConstraint()->groups);
        self::assertSame($root, $last->getRoot());
    }

    public function testMeasuresDoNotChangeSpatialRelationshipsAndEmptyMembersRemainPresent(): void
    {
        foreach ([Dimension::XYM, Dimension::XYZM] as $dimension) {
            $ring = [[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]];
            $points = [];
            foreach ($ring as $index => $point) {
                $points[] = Dimension::XYM === $dimension ? [...$point, $index * 17] : [...$point, 2, $index * 17];
            }
            $member = PolygonStructureTest::polygon([$points], $dimension);
            $empty = PolygonStructureTest::polygon([], $dimension);
            $members = [$empty, $member, $member, $empty];
            $value = CollectionValidationTest::aggregate('MultiPolygon', $members, $dimension);
            $violations = Validation::createValidator()->validate($value, new Rules\MultiPolygon());
            self::assertCount(1, $violations, (string) $violations);
            self::assertSame('polygons[2]', $violations->get(0)->getPropertyPath());
            self::assertCount(4, $value->getElements());
        }
    }

    public function testOrientationWarningDoesNotSuppressAggregateTopology(): void
    {
        $shell = [[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]];
        $hole = [[2, 2], [4, 2], [4, 4], [2, 4], [2, 2]];
        $member = PolygonStructureTest::polygon([$shell, $hole]);
        $other = PolygonStructureTest::polygon([[[6, 2], [7, 2], [7, 4], [6, 4], [6, 2]]]);
        $value = CollectionValidationTest::aggregate('MultiPolygon', [$member, $other]);
        $violations = Validation::createValidator()->validate($value, new Rules\MultiPolygon());
        self::assertCount(2, $violations, (string) $violations);
        self::assertSame(Rules\Polygon::ORIENTATION_ERROR, $violations->get(0)->getCode());
        self::assertSame(Rules\MultiPolygon::INTERSECTION_ERROR, $violations->get(1)->getCode());
    }

    public function testTriangleMembersReuseTheirCompleteContract(): void
    {
        $triangle = PolygonStructureTest::triangle([[[0, 0], [2, 0], [0, 2], [0, 0]]]);
        $empty = PolygonStructureTest::triangle([]);
        foreach ([[$empty], [$empty, $triangle]] as $members) {
            self::assertCount(0, Validation::createValidator()->validate(
                CollectionValidationTest::aggregate('MultiPolygon', $members),
                new Rules\MultiPolygon()
            ));
        }
    }
}
