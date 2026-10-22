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
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\CircularString;
use LongitudeOne\SpatialValidator\Internal\ArcIntersections;
use LongitudeOne\SpatialValidator\Internal\CircularArcs;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal @coversNothing
 *
 * @coversNothing
 */
final class CircularStringSimplicityTest extends TestCase
{
    /** @param list<list<float|int>> $coordinates */
    #[DataProvider('cases')]
    public function testActualArcs(array $coordinates, bool $simple): void
    {
        $curve = CircularStringValidationTest::curve($coordinates);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($curve, new SpatialAssert\CircularString()));
        $violations = $service->validate($curve, new SpatialAssert\SimpleCircularString());
        self::assertSame($simple, 0 === count($violations));
    }

    /** @return iterable<string, array{list<list<float|int>>, bool}> */
    public static function cases(): iterable
    {
        yield 'EMPTY' => [[], true];

        yield 'semicircle' => [[[-1, 0], [0, 1], [1, 0]], true];

        yield 'retraced semicircle' => [[[-1, 0], [0, 1], [1, 0], [0, 1], [-1, 0]], false];

        yield 'complementary semicircles' => [[[-1, 0], [0, 1], [1, 0], [0, -1], [-1, 0]], true];

        yield 'single full circle' => [[[1, 0], [-1, 0], [1, 0]], true];

        yield 'collinear segment' => [[[0, 0], [1, 0], [2, 0]], true];

        yield 'collinear continuation' => [[[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]], true];

        yield 'collinear backtrack' => [[[0, 0], [1, 0], [2, 0], [1, 0], [0, 0]], false];

        yield 'full circles tangent at allowed join' => [[[1, 0], [-1, 0], [1, 0], [3, 0], [1, 0]], true];

        yield 'full circles overlap' => [[[1, 0], [-1, 0], [1, 0], [0, 1], [1, 0]], false];

        yield 'arc and chord only join' => [[[-1, 0], [0, 1], [1, 0], [0, 0], [-1, 0]], true];

        yield 'arc and line interior crossing' => [[[-1, 0], [0, 1], [1, 0], [0, 1], [-1, 2]], false];
    }

    /** @param list<list<float|int>> $first
     * @param list<list<float|int>> $second */
    #[DataProvider('supportPairs')]
    public function testExactSupportInteractions(array $first, array $second, bool $conflict): void
    {
        $left = CircularArcs::build($first)[0];
        $right = CircularArcs::build($second)[0];
        self::assertSame(
            $conflict,
            ArcIntersections::conflict($left, $right, [])
        );
    }

    /** @return iterable<string, array{list<list<float|int>>, list<list<float|int>>, bool}> */
    public static function supportPairs(): iterable
    {
        yield 'full circle overlapping semicircle' => [
            [[1, 0], [-1, 0], [1, 0]], [[-1, 0], [0, 1], [1, 0]], true];

        yield 'parallel separate planes' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[-1, 0, 1], [0, 1, 1], [1, 0, 1]], false];

        yield 'concentric disjoint supports' => [
            [[-1, 0], [0, 1], [1, 0]], [[-2, 0], [0, 2], [2, 0]], false];

        yield 'disjoint circles coplanar' => [
            [[-1, 0], [0, 1], [1, 0]], [[3, 0], [4, 1], [5, 0]], false];

        yield 'interior circle tangency' => [
            [[-1, 0], [0, 1], [1, 0]], [[-1, 2], [0, 1], [1, 2]], true];

        yield 'coplanar crossing' => [
            [[-1, 0], [0, 1], [1, 0]], [[0, 0], [1, 1], [2, 0]], true];

        yield 'same plane but disjoint sectors' => [
            [[-1, 0], [0, 1], [1, 0]], [[-0.5, -1], [0.5, -2], [1.5, -1]], false];

        yield 'nonparallel planes intersect at common support point' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[0, 0, -1], [0, 1, 0], [0, 0, 1]], true];

        yield 'nonparallel planes separated circles' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[0, 0, 2], [0, 1, 3], [0, 0, 4]], false];

        yield 'segment transverse to circle plane at circle point' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[0, 1, -1], [0, 1, 0], [0, 1, 1]], true];

        yield 'segment transverse away from support' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[0, 2, -1], [0, 2, 0], [0, 2, 1]], false];

        yield 'segment parallel outside plane' => [
            [[-1, 0, 0], [0, 1, 0], [1, 0, 0]], [[0, -2, 1], [0, 0, 1], [0, 2, 1]], false];

        yield 'segment hits circle outside segment extent' => [
            [[-1, 0], [0, 1], [1, 0]], [[0, 2], [0, 3], [0, 4]], false];

        yield 'segment hits circle interior' => [
            [[-1, 0], [0, 1], [1, 0]], [[0, -2], [0, 0], [0, 2]], true];

        yield 'support endpoint forbidden without allowed join' => [
            [[-1, 0], [0, 1], [1, 0]], [[1, 0], [0, -1], [-1, 0]], true];

        yield 'actual arc intersection missed by chords' => [
            [[-1, 0], [0, 1], [1, 0]], [[0.4, 0.9], [0.45, 0.9], [0.5, 0.9]], true];

        yield 'chord intersection absent from actual arc' => [
            [[-1, 0], [0, 1], [1, 0]], [[0.2, 0.7], [0.2, 0.8], [0.2, 0.9]], false];

        yield 'reverse argument segment arc' => [
            [[0, -2], [0, 0], [0, 2]], [[-1, 0], [0, 1], [1, 0]], true];
    }

    public function testGeographyAndIndeterminatePlaneWarnings(): void
    {
        $service = Validation::createValidator();
        $constraint = new SpatialAssert\SimpleCircularString();
        $geography = CircularStringValidationTest::curve(
            [[-1, 0], [0, 1], [1, 0]],
            family: SpatialModelEnum::GEOGRAPHY
        );
        $violations = $service->validate($geography, $constraint);
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\SimpleCircularString::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
        self::assertNotSame($constraint, $violations->get(0)->getConstraint());
        self::assertNull($constraint->payload);
        $full = CircularStringValidationTest::curve([[1, 0, 0], [-1, 0, 0], [1, 0, 0]], CoordinateDimensionEnum::XYZ);
        self::assertCount(0, $service->validate($full, $constraint));
        $mixed = CircularStringValidationTest::curve(
            [[1, 0, 0], [-1, 0, 0], [1, 0, 0], [0, 1, 0], [-1, 0, 0]],
            CoordinateDimensionEnum::XYZ
        );
        $violations = $service->validate($mixed, $constraint);
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\SimpleCircularString::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
    }

    public function testLiteralSemicircleClosureAndValidityAreSeparate(): void
    {
        $service = Validation::createValidator();
        $examples = [
            [[[-1, 0], [0, 1], [1, 0]], false, true],
            [[[-1, 0], [0, 1], [1, 0], [0, 1], [-1, 0]], true, false],
            [[[-1, 0], [0, 1], [1, 0], [0, -1], [-1, 0]], true, true],
        ];
        foreach ($examples as [$positions, $closed, $simple]) {
            $curve = new CircularString($positions);
            self::assertSame($closed, $curve->isClosed());
            self::assertCount(0, $service->validate($curve, new SpatialAssert\CircularString()));
            self::assertSame($simple, 0 === count($service->validate(
                $curve,
                new SpatialAssert\SimpleCircularString()
            )));
        }
    }

    public function testNearTangenciesWithoutProximityTolerance(): void
    {
        $first = CircularArcs::build([[1.0, 0.0], [-1.0, 0.0], [1.0, 0.0]])[0];
        $heights = [[2.0 + PHP_FLOAT_EPSILON * 2, false], [2.0, true], [2.0 - PHP_FLOAT_EPSILON, true]];
        foreach ($heights as [$height, $expected]) {
            $second = CircularArcs::build(
                [[1.0, $height], [-1.0, $height], [1.0, $height]]
            )[0];
            self::assertSame(
                $expected,
                ArcIntersections::conflict($first, $second, [])
            );
        }
        $curve = CircularStringValidationTest::curve([[0, 0], [1, 1], [2, 2 + PHP_FLOAT_EPSILON * 2]]);
        self::assertCount(0, Validation::createValidator()->validate(
            $curve,
            new SpatialAssert\SimpleCircularString()
        ));
    }

    public function testScaleAndAxisPermutations(): void
    {
        foreach ([1.0e308, 1.0e-10, 1.0] as $scale) {
            $xy = [[-$scale, 0], [0, $scale], [$scale, 0], [0, -$scale], [-$scale, 0]];
            foreach ([[0, 1], [0, 2], [1, 2]] as [$horizontal, $vertical]) {
                $xyz = [];
                foreach ($xy as $point) {
                    $position = [0, 0, 0];
                    $position[$horizontal] = $point[0];
                    $position[$vertical] = $point[1];
                    $xyz[] = $position;
                }
                $curve = CircularStringValidationTest::curve($xyz, CoordinateDimensionEnum::XYZ);
                self::assertCount(0, Validation::createValidator()->validate(
                    $curve,
                    new SpatialAssert\SimpleCircularString()
                ));
            }
        }
    }
}
