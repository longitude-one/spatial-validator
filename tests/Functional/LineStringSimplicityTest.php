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

use InvalidArgumentException as InvalidCoordinates;
use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString as PlanarLine;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\LineString as ElevatedLine;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Internal\RobustPredicates;
use LongitudeOne\SpatialValidator\Internal\SegmentIntersections;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal @coversNothing
 *
 * @coversNothing
 */
final class LineStringSimplicityTest extends TestCase
{
    public function testDefensiveSpatialHelpers(): void
    {
        self::assertNull(PointCoordinates::position('POINT (0 0)'));
        self::assertFalse(PointCoordinates::same([0.0, 0.0], [0.0, 0.0, 0.0]));
        self::assertSame(0, SegmentIntersections::kind(
            [0.0, 0.0, 0.0],
            [0.0, 0.0, 0.0],
            [1.0, 1.0, 1.0],
            [2.0, 2.0, 2.0]
        ));
        self::assertSame(0, SegmentIntersections::kind([0.0, 0.0], [1.0, 0.0], [2.0, 0.0], [3.0, 0.0]));
        self::assertSame(0, SegmentIntersections::kind([0.0, 0.0], [1.0, 1.0], [0.0, 2.0], [1.0, 3.0]));
        self::assertSame(1, SegmentIntersections::kind([0.0, 0.0], [1.0, 0.0], [1.0, 0.0], [2.0, 0.0]));
    }

    public function testDirectCompositionUsesOriginalContextAndGroup(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [1, 1]]);
        $constraint = new SpatialAssert\LineString(groups: ['direct'], payload: ['source' => 'consumer']);
        $context = $this->createMock(ExecutionContextInterface::class);
        $service = $this->createMock(ValidatorInterface::class);
        $contextual = $this->createMock(ContextualValidatorInterface::class);
        $context->method('getGroup')->willReturn('direct');
        $context->expects(self::once())->method('getValidator')->willReturn($service);
        $service->expects(self::once())->method('inContext')->with(self::identicalTo($context))
            ->willReturn($contextual)
        ;
        $rules = [
            new SpatialAssert\Components(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\MinimumPointCount(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\NonEmptyPoints(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\NoConsecutiveDuplicatePoints(groups: ['direct'], payload: $constraint->payload),
        ];
        $contextual->expects(self::once())->method('validate')
            ->with(self::identicalTo($value), $rules, 'direct')->willReturnSelf()
        ;
        $validator = new SpatialAssert\LineStringValidator();
        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }

    public function testEmptyGeographyAndInvalidInputDoNotRunGeodeticPredicates(): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(
            LineStringValidationTest::line([], family: SpatialModelEnum::GEOGRAPHY),
            new SpatialAssert\SimpleLineString()
        ));
        $invalid = LineStringValidationTest::line([[INF, 0], [1, 1]]);
        $violations = $service->validate($invalid, new SpatialAssert\SimpleLineString());
        self::assertGreaterThan(0, count($violations));
        self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
    }

    public function testExactCoplanarityAtExtremeScales(): void
    {
        self::assertTrue(RobustPredicates::coplanar(
            [0.0, 0.0, 0.0],
            [1.0e308, 0.0, 0.0],
            [0.0, 1.0e308, 0.0],
            [1.0e308, 1.0e308, 0.0]
        ));
        self::assertFalse(RobustPredicates::coplanar(
            [0.0, 0.0, 0.0],
            [1.0e308, 0.0, 0.0],
            [0.0, 1.0e308, 0.0],
            [0.0, 0.0, 5.0e-324]
        ));
        self::assertFalse(RobustPredicates::coplanar(
            [0.0, 0.0, 0.0],
            [5.0e-324, 0.0, 0.0],
            [0.0, 5.0e-324, 0.0],
            [0.0, 0.0, 5.0e-324]
        ));
    }

    /** @param list<list<float>> $points */
    #[DataProvider('orientations')]
    public function testExactOrientation(array $points, int $sign): void
    {
        self::assertSame($sign, RobustPredicates::orientation(...$points));
        self::assertSame(-$sign, RobustPredicates::orientation($points[0], $points[2], $points[1]));
    }

    /** @return iterable<string, array{list<list<float>>, int}> */
    public static function orientations(): iterable
    {
        yield 'different scales' => [[[1.0, 1.0], [1.0e100, 1.0], [1.0, 1.0e100]], 1];

        yield 'reversed different scales' => [[[1.0e100, 1.0e100], [1.0, 1.0], [0.0, 1.0]], -1];

        yield 'positive' => [[[0.0, 0.0], [1.0, 0.0], [0.0, 1.0]], 1];

        yield 'collinear and signed zero' => [[[-0.0, 0.0], [1.0, 1.0], [2.0, 2.0]], 0];

        yield 'cancellation' => [[[0.0, 0.0], [1.0, 1.0], [1.0 + PHP_FLOAT_EPSILON, 1.0]], -1];

        yield 'overflow products' => [[[0.0, 0.0], [1.0e308, 1.0e308], [1.0e308, 5.0e307]], -1];

        yield 'overflow differences' => [[[-1.0e308, -1.0e308], [1.0e308, 1.0e308], [0.0, 1.0]], 1];

        yield 'subnormal products' => [[[0.0, 0.0], [5.0e-324, 0.0], [0.0, 5.0e-324]], 1];

        yield 'tiny mixed signs' => [[[-1.0e-200, 1.0e-200], [1.0e-200, 1.0e-200], [0.0, -1.0e-200]], -1];
    }

    /** @param list<list<float|int>> $coordinates */
    #[DataProvider('simplicity')]
    public function testExplicitSimplicity(array $coordinates, CoordinateDimensionEnum $dimension, bool $simple): void
    {
        $line = LineStringValidationTest::line($coordinates, $dimension);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($line, new SpatialAssert\LineString()));
        $violations = $service->validate($line, new SpatialAssert\SimpleLineString());
        self::assertSame($simple, 0 === count($violations));
        if (!$simple) {
            self::assertSame(SpatialAssert\SimpleLineString::INVALID_ERROR, $violations->get(0)->getCode());
            self::assertNull($violations->get(0)->getConstraint()?->payload);
        }
    }

    /** @return iterable<string, array{list<list<float|int>>, CoordinateDimensionEnum, bool}> */
    public static function simplicity(): iterable
    {
        $layout = CoordinateDimensionEnum::XY;
        $xyz = CoordinateDimensionEnum::XYZ;

        yield 'EMPTY' => [[], $layout, true];

        yield 'line' => [[[0, 0], [1, 1]], $layout, true];

        yield 'adjacent endpoint' => [[[0, 0], [2, 2], [3, 0]], $layout, true];

        yield 'closed triangle' => [[[0, 0], [2, 0], [1, 1], [0, 0]], $layout, true];

        yield 'closed XYZ triangle' => [[[0, 0, 5], [2, 0, 5], [1, 1, 5], [0, 0, 5]], $xyz, true];

        yield 'crossing' => [[[0, 0], [2, 2], [0, 2], [2, 0]], $layout, false];

        yield 'backtracking' => [[[0, 0], [2, 0], [1, 0]], $layout, false];

        yield 'self-tangency' => [[[0, 0], [2, 0], [1, 1], [1, 0], [3, 1]], $layout, false];

        yield 'collinear continuation' => [[[0, 0], [1, 0], [2, 0]], $layout, true];

        yield 'collinear nonadjacent overlap' => [[[0, 0], [3, 0], [3, 1], [1, 0], [2, 0]], $layout, false];

        yield 'elevation separated crossing' => [[[0, 0, 0], [2, 2, 0], [0, 2, 1], [2, 0, 1]], $xyz, true];

        yield 'coplanar XYZ crossing' => [[[0, 0, 1], [2, 2, 1], [0, 2, 1], [2, 0, 1]], $xyz, false];

        yield 'vertical YZ crossing' => [[[0, 0, 0], [0, 2, 2], [0, 0, 2], [0, 2, 0]], $xyz, false];

        yield 'vertical XZ crossing' => [[[0, 0, 0], [2, 0, 2], [0, 0, 2], [2, 0, 0]], $xyz, false];

        yield 'vertical collinear overlap' => [[[0, 0, 0], [0, 0, 2], [0, 0, 1]], $xyz, false];

        yield 'XYZM ignores measures' => [
            [[0, 0, 0, 100], [2, 2, 0, 1], [0, 2, 0, -5], [2, 0, 0, 9]],
            CoordinateDimensionEnum::XYZM,
            false,
        ];

        yield 'XYM ignores measures' => [
            [[0, 0, 100], [2, 2, 1], [0, 2, -5], [2, 0, 9]],
            CoordinateDimensionEnum::XYM,
            false,
        ];

        yield 'large crossing' => [
            [[-1.0e308, -1.0e308], [1.0e308, 1.0e308], [-1.0e308, 1.0e308], [1.0e308, -1.0e308]],
            $layout,
            false,
        ];

        yield 'small separated lines' => [
            [[0, 0], [1.0e-10, 1.0e-10], [1.0e-10, 2.0e-10], [0, 1.0e-10]],
            $layout,
            true,
        ];

        yield 'near degenerate' => [[[0, 0], [1, 1], [2, 2 + PHP_FLOAT_EPSILON * 2], [3, 3]], $layout, true];
    }

    public function testGeographyWarningUsesSeparateConstraint(): void
    {
        $line = LineStringValidationTest::line([[0, 0], [1, 1]], family: SpatialModelEnum::GEOGRAPHY);
        $constraint = new SpatialAssert\SimpleLineString();
        $minimum = new SpatialAssert\MinimumPointCount(limit: 4);
        $violations = Validation::createValidator()->validate($line, [$constraint, $minimum]);
        self::assertCount(2, $violations);
        self::assertSame(SpatialAssert\SimpleLineString::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
        self::assertNotSame($constraint, $violations->get(0)->getConstraint());
        self::assertNull($constraint->payload);
        self::assertSame($minimum, $violations->get(1)->getConstraint());
        self::assertNull($minimum->payload);
        $custom = new SpatialAssert\SimpleLineString(payload: ['source' => 'consumer']);
        $customViolations = Validation::createValidator()->validate($line, $custom);
        self::assertSame(['source' => 'consumer'], $customViolations->get(0)->getConstraint()?->payload);
        self::assertSame(['source' => 'consumer'], $custom->payload);
    }

    public function testIntegerOrientationOracle(): void
    {
        for ($index = 1; $index <= 80; ++$index) {
            $first = [(float) (($index * 43) % 101 - 50), (float) (($index * 29) % 97 - 50)];
            $second = [(float) (($index * 17) % 103 - 50), (float) (($index * 61) % 109 - 50)];
            $third = [(float) (($index * 13) % 107 - 50), (float) (($index * 37) % 113 - 50)];
            // Small integer coordinates keep every operation exact in native arithmetic.
            $determinant = ($second[0] - $first[0]) * ($third[1] - $first[1])
                - ($second[1] - $first[1]) * ($third[0] - $first[0]);
            self::assertSame($determinant <=> 0.0, RobustPredicates::orientation($first, $second, $third));
        }
    }

    #[DataProvider('nearClosureLines')]
    public function testNearClosureDoesNotPermitInteriorCrossing(LineStringInterface $line): void
    {
        $epsilon = PHP_FLOAT_EPSILON / 4;
        self::assertTrue(PointCoordinates::same([0.0, 0.0], [$epsilon, -$epsilon]));
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($line, new SpatialAssert\LineString()));
        $violations = $service->validate($line, new SpatialAssert\SimpleLineString());
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\SimpleLineString::INVALID_ERROR, $violations->get(0)->getCode());
    }

    /** @return iterable<string, array{LineStringInterface}> */
    public static function nearClosureLines(): iterable
    {
        $epsilon = PHP_FLOAT_EPSILON / 4;

        yield 'XY interior crossing near closure' => [new PlanarLine([[0, 0], [1, 0], [0, 1], [$epsilon, -$epsilon]])];

        yield 'XYZ coplanar interior crossing near closure' => [
            new ElevatedLine([[0, 0, 5], [1, 0, 5], [0, 1, 5], [$epsilon, -$epsilon, 5]]),
        ];
    }

    public function testNonFinitePredicateInputIsRejected(): void
    {
        $this->expectException(InvalidCoordinates::class);
        RobustPredicates::orientation([INF, 0.0], [0.0, 0.0], [1.0, 1.0]);
    }
}
