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
use LongitudeOne\SpatialValidator\Internal\PolygonStructure;
use LongitudeOne\SpatialValidator\Internal\PolygonTopology;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class PolygonValidationTest extends TestCase
{
    /**
     * @param list<list<list<float|int>>> $rings
     *
     * @return list<list<list<float|int>>>
     */
    private static function oppositeHoles(array $rings): array
    {
        foreach ($rings as $index => $ring) {
            if (0 < $index) {
                $rings[$index] = array_reverse($ring);
            }
        }

        return $rings;
    }

    /**
     * @param list<list<list<float|int>>> $rings
     */
    #[DataProvider('topologies')]
    public function testExplicitAndGenericTopology(array $rings, ?string $error): void
    {
        foreach ([new Rules\Polygon(), new Rules\Spatial()] as $constraint) {
            $polygon = PolygonStructureTest::polygon(self::oppositeHoles($rings));
            $violations = Validation::createValidator()->validate($polygon, $constraint);
            $codes = [];
            foreach ($violations as $violation) {
                $codes[] = $violation->getCode();
                self::assertNull($violation->getConstraint()?->payload);
            }
            if (null === $error) {
                self::assertSame([], $codes, (string) $violations);

                continue;
            }
            self::assertContains($error, $codes, (string) $violations);
        }
    }

    /** @return iterable<string, array{list<list<list<int>>>, ?string}> */
    public static function topologies(): iterable
    {
        $shell = [[0, 0], [12, 0], [12, 12], [0, 12], [0, 0]];
        $inside = [[2, 2], [4, 2], [4, 4], [2, 4], [2, 2]];
        $tangent = [[0, 6], [3, 4], [3, 8], [0, 6]];

        yield 'empty' => [[], null];

        yield 'no holes' => [[$shell], null];

        yield 'inside' => [[$shell, $inside], null];

        yield 'outside smaller hole' => [[$shell, [[20, 2], [22, 2], [20, 4], [20, 2]]], Rules\Polygon::OUTSIDE_ERROR];

        yield 'hole contains exterior' => [[$inside, $shell], Rules\Polygon::OUTSIDE_ERROR];

        yield 'outside tangent' => [[$shell, [[12, 6], [15, 4], [15, 8], [12, 6]]], Rules\Polygon::OUTSIDE_ERROR];

        yield 'crossing' => [[$shell, [[10, 4], [14, 4], [14, 8], [10, 8], [10, 4]]],
            Rules\Polygon::INTERSECTION_ERROR];

        yield 'shell vertex crossing' => [[$shell, [[10, 2], [14, -2], [14, 2], [10, 2]]],
            Rules\Polygon::INTERSECTION_ERROR];

        yield 'nested' => [[$shell, [[1, 1], [8, 1], [8, 8], [1, 8], [1, 1]], $inside], Rules\Polygon::NESTED_ERROR];

        yield 'nested reverse order' => [[$shell, $inside, [[1, 1], [8, 1], [8, 8], [1, 8], [1, 1]]],
            Rules\Polygon::NESTED_ERROR];

        yield 'overlap' => [[$shell, $inside, [[3, 3], [6, 3], [6, 6], [3, 6], [3, 3]]],
            Rules\Polygon::INTERSECTION_ERROR];

        yield 'coincident holes' => [[$shell, $inside, $inside], Rules\Polygon::INTERSECTION_ERROR];

        yield 'shared shell interval' => [[$shell, [[0, 4], [3, 4], [3, 8], [0, 8], [0, 4]]],
            Rules\Polygon::INTERSECTION_ERROR];

        yield 'shared holes interval' => [[$shell, $inside, [[4, 2], [6, 2], [6, 4], [4, 4], [4, 2]]],
            Rules\Polygon::INTERSECTION_ERROR];

        yield 'single shell tangency' => [[$shell, $tangent], null];

        yield 'hole vertex tangent to middle of edge' => [[$shell, $inside, [[4, 3], [6, 2], [6, 4], [4, 3]]], null];

        yield 'crossing only at hole vertices' => [[$shell, [[10, 6], [12, 4], [14, 6], [12, 8], [10, 6]]],
            Rules\Polygon::OUTSIDE_ERROR];

        yield 'single hole tangency' => [[$shell, $inside, [[4, 4], [6, 4], [6, 6], [4, 6], [4, 4]]], null];

        yield 'multiple rings at one contact' => [[$shell, $tangent, [[0, 6], [2, 8], [1, 10], [0, 6]]], null];

        yield 'fan of holes at one contact' => [[$shell,
            [[6, 6], [8, 6], [7, 8], [6, 6]],
            [[6, 6], [5, 8], [4, 6], [6, 6]],
            [[6, 6], [5, 4], [7, 4], [6, 6]],
        ], null];

        yield 'hole disconnects shell twice' => [[$shell, [[0, 6], [6, 2], [12, 6], [6, 10], [0, 6]]],
            Rules\Polygon::DISCONNECTED_ERROR];

        yield 'all hole vertices on shell' => [[$shell, [[0, 6], [6, 0], [12, 6], [6, 12], [0, 6]]],
            Rules\Polygon::DISCONNECTED_ERROR];

        yield 'chain joining shell' => [[$shell,
            [[0, 6], [3, 3], [6, 6], [3, 9], [0, 6]],
            [[6, 6], [9, 3], [12, 6], [9, 9], [6, 6]],
        ], Rules\Polygon::DISCONNECTED_ERROR];

        yield 'cycle enclosing island' => [[$shell,
            [[3, 5], [5, 5], [5, 7], [3, 7], [3, 5]],
            [[5, 7], [7, 7], [7, 9], [5, 9], [5, 7]],
            [[7, 5], [9, 5], [9, 7], [7, 7], [7, 5]],
            [[5, 3], [7, 3], [7, 5], [5, 5], [5, 3]],
        ], Rules\Polygon::DISCONNECTED_ERROR];

        yield 'vertex only crossings' => [[$shell,
            [[2, 2], [8, 2], [8, 8], [2, 8], [2, 2]],
            [[2, 5], [5, 1], [8, 5], [5, 9], [2, 5]],
        ], Rules\Polygon::INTERSECTION_ERROR];
    }

    public function testGeographyKeepsStructuralErrorsAndUnavailableWarnings(): void
    {
        $polygon = PolygonStructureTest::polygon([[[0, 0], [2, 0], [0, 2], [0, 0]], []], family: Family::GEOGRAPHY);
        $constraint = new Rules\Polygon();
        $violations = Validation::createValidator()->validate($polygon, $constraint);
        $codes = [];
        foreach ($violations as $violation) {
            $codes[] = $violation->getCode();
        }
        self::assertContains(Rules\Ring::EMPTY_ERROR, $codes);
        self::assertContains(PolygonStructure::UNAVAILABLE_ERROR, $codes);
        self::assertSame(Rules\Polygon::UNAVAILABLE_ERROR, $violations->get(count($violations) - 1)->getCode());
        $last = $violations->get(count($violations) - 1);
        self::assertSame(['severity' => 'warning'], $last->getConstraint()?->payload);
        self::assertNull($constraint->payload);
    }

    public function testMeasuresAndNearContacts(): void
    {
        foreach ([Dimension::XY, Dimension::XYM, Dimension::XYZM] as $dimension) {
            $rings = [[[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]],
                [[PHP_FLOAT_EPSILON, 1], [PHP_FLOAT_EPSILON, 2], [1, 2], [1, 1], [PHP_FLOAT_EPSILON, 1]]];
            if (Dimension::XY !== $dimension) {
                $rings = array_map(static fn (array $ring): array => array_map(
                    static fn (array $point, int $index): array => Dimension::XYM === $dimension
                        ? [...$point, $index * 17] : [...$point, 0, $index * 17],
                    $ring,
                    array_keys($ring)
                ), $rings);
            }
            $polygon = PolygonStructureTest::polygon($rings, $dimension);
            self::assertCount(0, Validation::createValidator()->validate($polygon, new Rules\Polygon()));
        }
    }

    public function testOrientationWarningsKeepTopologyErrorsAndSymfonyContext(): void
    {
        $rings = [[[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]], [[10, 1], [12, 1], [10, 3], [10, 1]]];
        $service = Validation::createValidator();
        foreach ([false, true] as $reverse) {
            $coordinates = $reverse ? array_values(array_map(array_reverse(...), $rings)) : $rings;
            $polygon = PolygonStructureTest::polygon($coordinates);
            $root = ['polygon' => $polygon];
            $constraint = new Rules\Polygon(groups: ['custom']);
            self::assertCount(0, $service->validate($polygon, $constraint));
            $violations = $service->validate(
                $root,
                new Assert\Collection(fields: ['polygon' => $constraint], groups: ['custom']),
                ['custom']
            );
            self::assertCount(2, $violations);
            self::assertSame(Rules\Polygon::ORIENTATION_ERROR, $violations->get(0)->getCode());
            self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
            self::assertNotSame($constraint, $violations->get(0)->getConstraint());
            self::assertSame(Rules\Polygon::OUTSIDE_ERROR, $violations->get(1)->getCode());
            self::assertSame($constraint, $violations->get(1)->getConstraint());
            self::assertNull($constraint->payload);
            foreach ($violations as $violation) {
                self::assertSame($root, $violation->getRoot());
                self::assertSame('[polygon].rings[1]', $violation->getPropertyPath());
                self::assertSame(['custom'], $violation->getConstraint()?->groups);
            }
        }
        $polygon = PolygonStructureTest::polygon($rings);
        $payload = ['consumer' => true];
        $violations = $service->validate($polygon, new Rules\Polygon(payload: $payload));
        self::assertSame($payload, $violations->get(0)->getConstraint()?->payload);
        $generic = $service->validate($polygon, new Rules\Spatial(payload: $payload));
        self::assertSame(['severity' => 'warning'], $generic->get(0)->getConstraint()?->payload);
        self::assertNull($generic->get(1)->getConstraint()?->payload);
    }

    public function testOutsideNearBoundaryAndNearDegenerateHoleUseExactPredicates(): void
    {
        $shell = [[0, 0], [4, 0], [4, 4], [0, 4], [0, 0]];
        $outside = [[-PHP_FLOAT_EPSILON, 1], [1, 1], [1, 2], [-PHP_FLOAT_EPSILON, 2], [-PHP_FLOAT_EPSILON, 1]];
        $polygon = PolygonStructureTest::polygon([$shell, array_reverse($outside)]);
        $violations = Validation::createValidator()->validate($polygon, new Rules\Polygon());
        self::assertSame(Rules\Polygon::INTERSECTION_ERROR, $violations->get(0)->getCode());
        $skinny = [[1, 1], [2, 2], [3, 3 + 4 * PHP_FLOAT_EPSILON], [1, 1]];
        $polygon = PolygonStructureTest::polygon([$shell, array_reverse($skinny)]);
        self::assertCount(0, Validation::createValidator()->validate($polygon, new Rules\Polygon()));
    }

    public function testProjectionDefensivelyDeclinesMissingOrInvalidPlaneData(): void
    {
        self::assertSame([], PolygonTopology::project(PolygonStructureTest::polygon([])));
        $polygon = PolygonStructureTest::polygon([[[0, 0], [INF, 0], [0, 2], [0, 0]]]);
        self::assertSame([], PolygonTopology::project($polygon));
    }

    public function testQ02ClosureRetainsBothPositionsAndChecksTheClosingInterval(): void
    {
        $origin = 1.0e18;
        $valid = [[0, 0], [4096, 0], [4096, 4096], [0, 4096], [128, 128]];
        $crossed = [[0, 0], [0, -1024], [1024, -896], [-896, 1024], [128, 1024], [128, 128]];
        foreach ([$valid, $crossed] as $index => $relative) {
            $ring = array_map(static fn (array $point): array => [$origin + $point[0], $origin + $point[1]], $relative);
            $line = LineStringValidationTest::line($ring);
            $service = Validation::createValidator();
            self::assertCount(0, $service->validate($line, new Rules\Ring()));
            $polygon = PolygonStructureTest::polygon([$ring]);
            foreach ([new Rules\Polygon(), new Rules\Spatial()] as $constraint) {
                $violations = $service->validate($polygon, $constraint);
                self::assertCount($index, $violations, (string) $violations);
                if (1 === $index) {
                    self::assertSame(Rules\Polygon::INTERSECTION_ERROR, $violations->get(0)->getCode());
                    self::assertSame('rings[0]', $violations->get(0)->getPropertyPath());
                }
            }
        }
    }

    public function testScaleAndActualPlaneWithoutProximityInflation(): void
    {
        foreach ([1.0e-10, 1.0, 1.0e150, 1.0e307] as $scale) {
            foreach ([[0, 1, 2], [2, 0, 1], [1, 2, 0]] as $axes) {
                foreach ([false, true] as $vertical) {
                    $rings = [[[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]], [[1, 1], [1, 3], [3, 3], [3, 1], [1, 1]]];
                    $project = static function (array $point) use ($scale, $axes, $vertical): array {
                        $spatial = $vertical ? [0.0, $scale * $point[0], $scale * $point[1]]
                            : [$scale * $point[0], $scale * $point[1], $scale * $point[0]];

                        return array_map(static fn (int $axis): float => $spatial[$axis], $axes);
                    };
                    $rings = array_map(static fn (array $ring): array => array_map($project, $ring), $rings);
                    $polygon = PolygonStructureTest::polygon(array_values($rings), Dimension::XYZ);
                    $violations = Validation::createValidator()->validate($polygon, new Rules\Polygon());
                    self::assertCount(0, $violations, (string) $violations);
                }
            }
        }
    }

    public function testSpikesPuncturesAndCutLinesFailRingComposition(): void
    {
        $shell = [[0, 0], [8, 0], [8, 8], [0, 8], [0, 0]];
        $holes = [
            [[1, 1], [4, 1], [4, 4], [3, 4], [3, 3], [3, 4], [1, 4], [1, 1]],
            [[1, 1], [2, 2], [1, 1]],
            [[1, 1], [4, 1], [4, 4], [1, 4], [1, 1], [2, 2], [3, 2], [2, 3], [2, 2], [1, 1]],
        ];
        foreach ($holes as $hole) {
            foreach ([new Rules\Polygon(), new Rules\Spatial()] as $constraint) {
                $polygon = PolygonStructureTest::polygon([$shell, $hole]);
                $violations = Validation::createValidator()->validate($polygon, $constraint);
                self::assertGreaterThan(0, count($violations));
                foreach ($violations as $violation) {
                    self::assertStringStartsWith('rings[1]', $violation->getPropertyPath());
                    self::assertNotSame(Rules\Polygon::ORIENTATION_ERROR, $violation->getCode());
                }
            }
        }
    }

    public function testStructureFailurePreventsDependentTopology(): void
    {
        foreach ([new Rules\Polygon(), new Rules\Spatial()] as $constraint) {
            $polygon = PolygonStructureTest::polygon([[[0, 0, 0], [4, 0, 0], [4, 4, 0], [0, 4, 1], [0, 0, 0]],
                [[10, 1, 0], [12, 1, 0], [10, 3, 0], [10, 1, 0]]], Dimension::XYZ);
            $violations = Validation::createValidator()->validate($polygon, $constraint);
            self::assertCount(1, $violations);
            self::assertSame(PolygonStructure::PLANE_ERROR, $violations->get(0)->getCode());
        }
    }
}
