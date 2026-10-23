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
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialValidator\Internal\PolyhedralTopology;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/** @internal
 * @coversNothing
 */
final class PolyhedralSurfaceValidationTest extends TestCase
{
    /** @return list<list<list<list<float|int>>>> */
    public static function cube(): array
    {
        $vertexA = [0, 0, 0];
        $vertexB = [2, 0, 0];
        $vertexC = [2, 2, 0];
        $vertexD = [0, 2, 0];
        $vertexE = [0, 0, 2];
        $vertexF = [2, 0, 2];
        $vertexG = [2, 2, 2];
        $vertexH = [0, 2, 2];

        return array_map(self::face(...), [
            [$vertexD, $vertexC, $vertexB, $vertexA],
            [$vertexA, $vertexB, $vertexF, $vertexE],
            [$vertexB, $vertexC, $vertexG, $vertexF],
            [$vertexC, $vertexD, $vertexH, $vertexG],
            [$vertexD, $vertexA, $vertexE, $vertexH],
            [$vertexE, $vertexF, $vertexG, $vertexH],
        ]);
    }

    /** @param array<mixed> $patches */
    public static function surface(
        array $patches,
        Dimension $dimension = Dimension::XYZ,
        Family $family = Family::GEOMETRY,
    ): PolyhedralSurfaceInterface {
        $value = self::createStub(PolyhedralSurfaceInterface::class);
        $value->method('getType')->willReturn(GeometryTypeEnum::POLYHEDRALSURFACE);
        $value->method('getDimension')->willReturn($dimension);
        $value->method('getFamily')->willReturn($family);
        $value->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $value->method('getPatches')->willReturn($patches);
        $value->method('getElements')->willReturn($patches);
        // Deliberately inconsistent EMPTY flag must never hide patches.
        $value->method('isEmpty')->willReturn(true);

        return $value;
    }

    /** @return list<list<list<list<float|int>>>> */
    public static function tetrahedron(): array
    {
        $origin = [0, 0, 0];
        $east = [2, 0, 0];
        $north = [0, 2, 0];
        $top = [0, 0, 2];

        return [self::face([$origin, $north, $east]), self::face([$origin, $east, $top]),
            self::face([$origin, $top, $north]), self::face([$east, $north, $top])];
    }

    /** @param list<list<list<list<float|int>>>> $faces */
    private static function candidate(array $faces, Dimension $dimension = Dimension::XYZ): PolyhedralSurfaceInterface
    {
        return self::surface(array_map(
            static fn (array $rings) => PolygonStructureTest::polygon($rings, $dimension),
            $faces
        ), $dimension);
    }

    /** @param list<list<float|int>> $vertices
     * @return list<list<list<float|int>>>
     */
    private static function face(array $vertices): array
    {
        return [[...$vertices, $vertices[0]]];
    }

    public function testClosedEnvelopeWithHoledPatchSeparatesOrientationWarningFromVolume(): void
    {
        $cube = self::cube();
        $hole = [[0.5, 0.5, 2], [0.5, 1.5, 2], [1.5, 1.5, 2], [1.5, 0.5, 2], [0.5, 0.5, 2]];
        foreach ([false, true] as $wrongOrientation) {
            $faces = $cube;
            $faces[5][] = $wrongOrientation ? array_reverse($hole) : $hole;
            $faces[] = [array_reverse($hole)];
            $violations = Validation::createValidator()->validate(
                self::candidate($faces),
                new Rules\ClosedPolyhedralSurface()
            );
            self::assertCount($wrongOrientation ? 1 : 0, $violations, (string) $violations);
            if ($wrongOrientation) {
                self::assertSame(Rules\Polygon::ORIENTATION_ERROR, $violations->get(0)->getCode());
            }
        }
    }

    public function testConnectedSurfaceStillRejectsNonAdjacentFaceCrossing(): void
    {
        $faces = [self::face([[0, 0, 0], [8, 0, 0], [8, 8, 0], [0, 8, 0]]),
            self::face([[8, 0, 0], [0, 0, 0], [0, 0, 2], [8, 0, 2]]),
            self::face([[8, 0, 2], [0, 0, 2], [0, 8, -2], [8, 8, -2]])];
        $violations = Validation::createValidator()->validate(self::candidate($faces), new Rules\PolyhedralSurface());
        $codes = array_map(static fn ($violation) => $violation->getCode(), iterator_to_array($violations));
        self::assertContains(Rules\PolyhedralSurface::INTERSECTION_ERROR, $codes);
        self::assertNotContains(Rules\PolyhedralSurface::CONNECTED_ERROR, $codes);
        self::assertNotContains(Rules\PolyhedralSurface::ORIENTATION_ERROR, $codes);
        self::assertNotContains(Rules\PolyhedralSurface::INCIDENCE_ERROR, $codes);
    }

    public function testDimensionsAndGeographyWarning(): void
    {
        foreach ([Dimension::XY, Dimension::XYM] as $dimension) {
            $violations = Validation::createValidator()->validate(
                self::surface([], $dimension),
                new Rules\PolyhedralSurface()
            );
            self::assertSame(Rules\PolyhedralSurface::DIMENSION_ERROR, $violations->get(0)->getCode());
        }
        $member = PolygonStructureTest::polygon(self::tetrahedron()[0], Dimension::XYZ, Family::GEOGRAPHY);
        foreach ([null, ['custom' => true]] as $payload) {
            $constraint = new Rules\PolyhedralSurface(payload: $payload);
            $violations = Validation::createValidator()->validate(
                self::surface([$member], family: Family::GEOGRAPHY),
                $constraint
            );
            $last = $violations->get(count($violations) - 1);
            self::assertSame(Rules\PolyhedralSurface::UNAVAILABLE_ERROR, $last->getCode());
            self::assertSame($payload ?? ['severity' => 'warning'], $last->getConstraint()?->payload);
            self::assertSame($payload, $constraint->payload);
        }
    }

    public function testEdgeConnectedPatchesCannotHavePinchedBoundaryVertex(): void
    {
        $faces = [];
        foreach ([[0, 0], [-1, 0], [-1, 1], [-1, 2], [0, 2], [1, 2], [1, 1]] as [$east, $north]) {
            $faces[] = self::face([[$east, $north, 0], [$east + 1, $north, 0],
                [$east + 1, $north + 1, 0], [$east, $north + 1, 0]]);
        }
        $violations = Validation::createValidator()->validate(self::candidate($faces), new Rules\PolyhedralSurface());
        self::assertCount(1, $violations, (string) $violations);
        self::assertSame(Rules\PolyhedralSurface::MANIFOLD_ERROR, $violations->get(0)->getCode());
    }

    /** @param list<list<list<list<float|int>>>> $faces */
    #[DataProvider('validSurfaces')]
    public function testGeneralAndClosedContracts(array $faces, bool $closed): void
    {
        $value = self::candidate($faces);
        $validator = Validation::createValidator();
        foreach ([new Rules\PolyhedralSurface(), new Rules\Spatial()] as $constraint) {
            self::assertCount(0, $validator->validate($value, $constraint));
        }
        $violations = $validator->validate($value, new Rules\ClosedPolyhedralSurface());
        self::assertCount($closed ? 0 : 1, $violations, (string) $violations);
        if (!$closed) {
            self::assertSame(Rules\ClosedPolyhedralSurface::INVALID_ERROR, $violations->get(0)->getCode());
            self::assertNull($violations->get(0)->getConstraint()?->payload);
        }
    }

    /** @return iterable<string, array{list<list<list<list<float|int>>>>, bool}> */
    public static function validSurfaces(): iterable
    {
        yield 'empty' => [[], false];

        yield 'single face' => [[self::tetrahedron()[0]], false];

        yield 'open book' => [array_slice(self::tetrahedron(), 0, 2), false];

        yield 'tetrahedron' => [self::tetrahedron(), true];

        yield 'cube' => [self::cube(), true];
        $cube = self::cube();
        array_splice($cube[0][0], 2, 0, [[2, 1, 0]]);

        yield 'subdivided cube edge' => [$cube, true];

        yield 'open cube' => [array_slice(self::cube(), 0, 5), false];
    }

    public function testHoledFacesAndOrientationWarningsRemainIndependent(): void
    {
        $outer = [[0, 0, 0], [8, 0, 0], [8, 8, 0], [0, 8, 0], [0, 0, 0]];
        $hole = [[2, 2, 0], [2, 6, 0], [6, 6, 0], [6, 2, 0], [2, 2, 0]];
        $wall = self::face([[2, 2, 0], [6, 2, 0], [6, 2, 2], [2, 2, 2]]);
        foreach ([false, true] as $wrongOrientation) {
            $rings = [$outer, $wrongOrientation ? array_reverse($hole) : $hole];
            $value = self::candidate([$rings, $wall]);
            $violations = Validation::createValidator()->validate($value, new Rules\PolyhedralSurface());
            self::assertCount($wrongOrientation ? 1 : 0, $violations, (string) $violations);
            if ($wrongOrientation) {
                self::assertSame(Rules\Polygon::ORIENTATION_ERROR, $violations->get(0)->getCode());
                self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
            }
        }
        $throughHole = self::face([[4, 3, -1], [4, 5, -1], [4, 5, 1], [4, 3, 1]]);
        $faces = [[$outer, $hole], $throughHole];
        $violations = Validation::createValidator()->validate(self::candidate($faces), new Rules\PolyhedralSurface());
        $codes = array_map(static fn ($violation) => $violation->getCode(), iterator_to_array($violations));
        self::assertNotContains(Rules\PolyhedralSurface::INTERSECTION_ERROR, $codes);
        self::assertContains(Rules\PolyhedralSurface::CONNECTED_ERROR, $codes);
    }

    public function testInvalidPatchesRetainRingAndPointPaths(): void
    {
        $faces = self::cube();
        $faces[0] = [[[0, 2, 0], [2, 2, 0], [2, 0, 1], [0, 0, 0], [0, 2, 0]]];
        $violations = Validation::createValidator()->validate(self::candidate($faces), new Rules\Spatial());
        self::assertNotEmpty($violations);
        self::assertStringStartsWith('patches[0].rings[0]', $violations->get(0)->getPropertyPath());
        $missingZ = PolygonStructureTest::polygon([[[0, 0], [2, 0], [0, 2], [0, 0]]]);
        $violations = Validation::createValidator()->validate(
            self::surface([$missingZ]),
            new Rules\PolyhedralSurface()
        );
        self::assertSame(Rules\Components::DIMENSION_ERROR, $violations->get(0)->getCode());
    }

    /** @param list<list<list<list<float|int>>>> $faces */
    #[DataProvider('invalidSurfaces')]
    public function testInvalidTopology(array $faces, string $code): void
    {
        foreach (
            [new Rules\PolyhedralSurface(), new Rules\ClosedPolyhedralSurface(), new Rules\Spatial()] as $constraint
        ) {
            $violations = Validation::createValidator()->validate(self::candidate($faces), $constraint);
            $codes = array_map(static fn ($violation) => $violation->getCode(), iterator_to_array($violations));
            self::assertContains($code, $codes, (string) $violations);
        }
    }

    /** @return iterable<string, array{list<list<list<list<float|int>>>>, string}> */
    public static function invalidSurfaces(): iterable
    {
        $tetra = self::tetrahedron();
        $wrong = $tetra;
        $wrong[1][0] = array_reverse($wrong[1][0]);

        yield 'same direction shared edge' => [$wrong, Rules\PolyhedralSurface::ORIENTATION_ERROR];

        yield 'three incident faces' => [[...$tetra, $tetra[0]], Rules\PolyhedralSurface::INCIDENCE_ERROR];

        yield 'coincident opposite faces' => [
            [$tetra[0], [array_reverse($tetra[0][0])]],
            Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];

        yield 'vertex only' => [
            [$tetra[0], self::face([[0, 0, 0], [-2, 0, 0], [0, -2, 0]])], Rules\PolyhedralSurface::CONNECTED_ERROR,
        ];

        yield 'disjoint' => [
            [$tetra[0], self::face([[5, 5, 0], [7, 5, 0], [5, 7, 0]])], Rules\PolyhedralSurface::CONNECTED_ERROR,
        ];
        $square = self::face([[0, 0, 0], [8, 0, 0], [8, 8, 0], [0, 8, 0]]);

        yield 'crossing filled face interiors' => [
            [$square, self::face([[4, 2, -1], [4, 6, -1], [4, 6, 1], [4, 2, 1]])],
            Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];

        yield 'interior interval with only shared boundary endpoints' => [[
            self::face([[-2, 0, 0], [0, -2, 0], [2, 0, 0], [0, 2, 0]]),
            self::face([[0, -2, 0], [0, 0, -2], [0, 2, 0], [0, 0, 2]]),
        ], Rules\PolyhedralSurface::INTERSECTION_ERROR];

        yield 'boundary in another interior' => [
            [$square, self::face([[4, 2, 0], [4, 6, 0], [4, 6, 2], [4, 2, 2]])],
            Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];

        yield 'isolated point in face interior' => [
            [$square, self::face([[4, 4, 0], [4, 2, 1], [4, 6, 1]])], Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];

        yield 'coplanar crossing boundaries' => [
            [$square, self::face([[6, -1, 0], [9, -1, 0], [9, 2, 0], [6, 2, 0]])],
            Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];

        yield 'coplanar containment' => [
            [$square, self::face([[2, 2, 0], [4, 2, 0], [4, 4, 0], [2, 4, 0]])],
            Rules\PolyhedralSurface::INTERSECTION_ERROR,
        ];
        $other = array_map(static fn (array $rings): array => array_map(
            static fn (array $ring): array => array_map(
                static fn (array $point): array => [-$point[0], -$point[1], -$point[2]],
                $ring
            ),
            $rings
        ), $tetra);

        yield 'pinched vertex closed fans' => [[...$tetra, ...$other], Rules\PolyhedralSurface::MANIFOLD_ERROR];
    }

    public function testMeasuresAndSignedZero(): void
    {
        foreach ([Dimension::XYZ, Dimension::XYZM] as $dimension) {
            $faces = [];
            foreach (self::tetrahedron() as [$ring]) {
                $points = [];
                foreach ($ring as $index => $point) {
                    $point[0] = 0 === $point[0] ? -0.0 : $point[0];
                    $points[] = Dimension::XYZM === $dimension ? [...$point, $index * 17] : $point;
                }
                $faces[] = [$points];
            }
            self::assertCount(
                0,
                Validation::createValidator()->validate(
                    self::candidate($faces, $dimension),
                    new Rules\ClosedPolyhedralSurface()
                )
            );
        }
    }

    public function testPartialEdgeAndCoplanarAdjacentPatches(): void
    {
        $first = self::face([[0, 0, 0], [4, 0, 0], [4, 4, 0], [0, 4, 0]]);
        $second = self::face([[4, 1, 0], [6, 1, 0], [6, 3, 0], [4, 3, 0]]);
        self::assertCount(
            0,
            Validation::createValidator()->validate(
                self::candidate([$first, $second]),
                new Rules\Spatial()
            )
        );
    }

    public function testScalesAxesMeasuresAndReversedOrientation(): void
    {
        foreach ([1.0e-10, 1.0, 1.0e150, 1.0e307] as $scale) {
            foreach ([[0, 1, 2], [2, 0, 1], [1, 2, 0]] as $axes) {
                foreach ([false, true] as $reverse) {
                    $faces = [];
                    foreach (self::tetrahedron() as [$ring]) {
                        $points = array_map(static fn (array $point): array => array_map(
                            static fn (int $axis): float => $point[$axis] * $scale,
                            $axes
                        ), $ring);
                        $faces[] = [$reverse ? array_reverse($points) : $points];
                    }
                    self::assertSame([], PolyhedralTopology::errors($faces));
                    self::assertTrue(PolyhedralTopology::closed($faces));
                }
            }
        }
    }

    public function testStructuralFailuresAndLocalizedContext(): void
    {
        $outside = PolygonStructureTest::polygon([
            [[0, 0, 0], [8, 0, 0], [8, 8, 0], [0, 8, 0], [0, 0, 0]],
            [[20, 2, 0], [20, 4, 0], [22, 4, 0], [22, 2, 0], [20, 2, 0]],
        ], Dimension::XYZ);
        $value = self::surface([$outside, PolygonStructureTest::polygon([], Dimension::XYZ), '']);
        $constraint = new Rules\PolyhedralSurface(groups: ['custom'], payload: ['user' => true]);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $root = ['shape' => $value];
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(3, $violations, (string) $violations);
        self::assertSame('[shape].patches[0].rings[1]', $violations->get(0)->getPropertyPath());
        self::assertSame(Rules\Polygon::OUTSIDE_ERROR, $violations->get(0)->getCode());
        $last = $violations->get(2);
        self::assertSame('[shape].patches[1]', $last->getPropertyPath());
        self::assertSame(['user' => true], $last->getConstraint()?->payload);
        self::assertSame($root, $last->getRoot());
        self::assertSame(Rules\PolyhedralSurface::EMPTY_PATCH_ERROR, $last->getCode());
    }

    public function testThinEnvelopeAndCloseDisjointPlanesAreNotCollapsed(): void
    {
        $faces = self::tetrahedron();
        foreach ($faces as &$rings) {
            foreach ($rings[0] as &$point) {
                $point[2] *= 1.0e-12;
            }
            unset($point);
        }
        unset($rings);
        self::assertCount(
            0,
            Validation::createValidator()->validate(
                self::candidate($faces),
                new Rules\ClosedPolyhedralSurface()
            )
        );
        $flat = self::face([[0, 0, 0], [2, 0, 0], [0, 2, 0]]);
        $near = array_map(static fn (array $ring): array => array_map(
            static fn (array $point): array => [$point[0], $point[1], PHP_FLOAT_EPSILON],
            $ring
        ), $flat);
        $violations = Validation::createValidator()->validate(
            self::candidate([$flat, $near]),
            new Rules\PolyhedralSurface()
        );
        self::assertCount(1, $violations, (string) $violations);
        self::assertSame(Rules\PolyhedralSurface::CONNECTED_ERROR, $violations->get(0)->getCode());
    }

    public function testVerticalHoledFaceKeepsAvailableOrientationWarningSeparate(): void
    {
        $rings = [
            [[0, 0, 0], [0, 8, 0], [0, 8, 8], [0, 0, 8], [0, 0, 0]],
            [[0, 2, 2], [0, 6, 2], [0, 6, 6], [0, 2, 6], [0, 2, 2]],
        ];
        $violations = Validation::createValidator()->validate(self::candidate([$rings]), new Rules\PolyhedralSurface());
        self::assertCount(1, $violations, (string) $violations);
        self::assertSame(Rules\Polygon::ORIENTATION_ERROR, $violations->get(0)->getCode());
    }
}
