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
use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\CompoundCurveInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Polygon;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class CompoundCurveValidationTest extends TestCase
{
    /** @param list<SpatialInterface> $curves */
    public static function curve(
        array $curves,
        CoordinateDimensionEnum $dimension = CoordinateDimensionEnum::XY,
        SpatialModelEnum $family = SpatialModelEnum::GEOMETRY
    ): CompoundCurveInterface {
        $value = self::createStub(CompoundCurveInterface::class);
        $value->method('getType')->willReturn(GeometryTypeEnum::COMPOUNDCURVE);
        $value->method('getDimension')->willReturn($dimension);
        $value->method('getFamily')->willReturn($family);
        $value->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $value->method('getCurves')->willReturn($curves);
        $value->method('isEmpty')->willReturn([] === $curves);

        return $value;
    }

    public function testBrokenZJoinAndLocalizedContext(): void
    {
        $dimension = CoordinateDimensionEnum::XYZ;
        $first = LineStringValidationTest::line([[0, 0, 0], [1, 0, 0]], $dimension);
        $second = LineStringValidationTest::line([[1, 0, 1], [2, 0, 1]], $dimension);
        $value = self::curve([$first, $second], $dimension);
        $root = ['shape' => $value];
        $rule = new SpatialAssert\CompoundCurve(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $rule));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $rule], groups: ['custom']),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\CompoundCurve::JOIN_ERROR, $violations->get(0)->getCode());
        self::assertSame('[shape].curves[1]', $violations->get(0)->getPropertyPath());
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame(['source' => 'consumer'], $violations->get(0)->getConstraint()?->payload);
        self::assertSame([$first, $second], $value->getCurves());
    }

    public function testContinuityAndM(): void
    {
        $service = Validation::createValidator();
        foreach (CoordinateDimensionEnum::cases() as $dimension) {
            $first = match ($dimension) {
                CoordinateDimensionEnum::XY => [[0, 0], [1, 0]],
                CoordinateDimensionEnum::XYZ => [[0, 0, 0], [1, 0, 0]],
                CoordinateDimensionEnum::XYM => [[0, 0, 1], [1, 0, 2]],
                default => [[0, 0, 0, 1], [1, 0, 0, 2]],
            };
            $arc = match ($dimension) {
                CoordinateDimensionEnum::XY => [[1, 0], [2, 1], [3, 0]],
                CoordinateDimensionEnum::XYZ => [[1, 0, 0], [2, 1, 0], [3, 0, 0]],
                CoordinateDimensionEnum::XYM => [[1, 0, 30], [2, 1, 40], [3, 0, 50]],
                default => [[1, 0, 0, 30], [2, 1, 0, 40], [3, 0, 0, 50]],
            };
            $value = self::curve([LineStringValidationTest::line($first, $dimension),
                CircularStringValidationTest::curve($arc, $dimension)], $dimension);
            self::assertCount(0, $service->validate($value, new SpatialAssert\CompoundCurve()));
            self::assertCount(0, $service->validate($value, new SpatialAssert\SimpleCompoundCurve()));
        }
    }

    #[DataProvider('constraints')]
    public function testDirectInputContract(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedValueException::class);
        $validator->validate('', $constraint);
    }

    public function testEmptyAndInvalidMembers(): void
    {
        $service = Validation::createValidator();
        foreach ([new SpatialAssert\CompoundCurve(), new SpatialAssert\SimpleCompoundCurve()] as $constraint) {
            self::assertCount(0, $service->validate(self::curve([]), $constraint));
            foreach ([LineStringValidationTest::line([]), CircularStringValidationTest::curve([])] as $empty) {
                $violations = $service->validate(self::curve([$empty]), $constraint);
                self::assertCount(1, $violations);
                self::assertSame(SpatialAssert\CompoundCurve::EMPTY_ERROR, $violations->get(0)->getCode());
                self::assertSame('curves[0]', $violations->get(0)->getPropertyPath());
            }
        }
        $violations = $service->validate(self::curve([new Polygon([])]), new SpatialAssert\CompoundCurve());
        self::assertSame(SpatialAssert\Components::TYPE_ERROR, $violations->get(0)->getCode());
        $value = self::curve([LineStringValidationTest::line([[0, 0], [INF, 0]]),
            LineStringValidationTest::line([[2, 0], [3, 0]])]);
        $violations = $service->validate($value, new SpatialAssert\CompoundCurve());
        self::assertGreaterThan(0, count($violations));
        foreach ($violations as $violation) {
            self::assertNotSame(SpatialAssert\CompoundCurve::JOIN_ERROR, $violation->getCode());
        }
    }

    public function testFiniteJoinEqualityHasNoAddedTolerance(): void
    {
        $service = Validation::createValidator();
        foreach ([1.0, 1.0E100] as $scale) {
            foreach ([1.0, 4.0] as $distance) {
                $join = $scale + $distance * PHP_FLOAT_EPSILON * $scale;
                $value = self::curve([
                    LineStringValidationTest::line([[0, 0], [$scale, 0]]),
                    LineStringValidationTest::line([[$join, 0], [2 * $scale, $scale]]),
                ]);
                $violations = $service->validate($value, new SpatialAssert\CompoundCurve());
                self::assertCount(1.0 === $distance ? 0 : 1, $violations);
            }
        }
    }

    public function testGeographyWarningsAndPayload(): void
    {
        $family = SpatialModelEnum::GEOGRAPHY;
        $value = self::curve([
            LineStringValidationTest::line([[0, 0], [1, 0]], family: $family),
            CircularStringValidationTest::curve([[1, 0], [2, 1], [3, 0]], family: $family),
        ], family: $family);
        $service = Validation::createValidator();
        $constraint = new SpatialAssert\CompoundCurve();
        $violations = $service->validate($value, $constraint);
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\CompoundCurve::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
        self::assertNull($constraint->payload);
        $both = $service->validate($value, new SpatialAssert\SimpleCompoundCurve());
        self::assertCount(2, $both);
        self::assertSame(SpatialAssert\SimpleCompoundCurve::UNAVAILABLE_ERROR, $both->get(1)->getCode());
        $single = self::curve([$value->getCurves()[0]], family: $family);
        $simple = new SpatialAssert\SimpleCompoundCurve(payload: ['source' => 'consumer']);
        $violations = $service->validate($single, $simple);
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\SimpleCompoundCurve::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        self::assertSame($simple->payload, $violations->get(0)->getConstraint()?->payload);
    }

    #[DataProvider('constraints')]
    public function testInputContract(Constraint $constraint): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, $constraint));
        foreach (['', 'COMPOUNDCURVE EMPTY', 42, false, [], LineStringValidationTest::line([])] as $value) {
            self::assertCount(1, $service->validate($value, $constraint));
        }
        $candidate = self::createStub(CompoundCurveInterface::class);
        $candidate->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        self::assertCount(1, $service->validate($candidate, $constraint));
    }

    public function testMetadataAndPointFailuresRemainLocalized(): void
    {
        $member = LineStringValidationTest::line([[0, 0, 1], [1, 0, 2]], CoordinateDimensionEnum::XYM);
        $violations = Validation::createValidator()->validate(
            self::curve([$member]),
            new SpatialAssert\CompoundCurve()
        );
        self::assertSame(SpatialAssert\Components::DIMENSION_ERROR, $violations->get(0)->getCode());
        self::assertSame('curves[0]', $violations->get(0)->getPropertyPath());
        $family = SpatialModelEnum::GEOGRAPHY;
        $member = LineStringValidationTest::line([[0, 0], [181, 0]], family: $family);
        $violations = Validation::createValidator()->validate(
            self::curve([$member], family: $family),
            new SpatialAssert\SimpleCompoundCurve()
        );
        self::assertSame(SpatialAssert\GeographyBounds::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertSame('curves[0].points[1]', $violations->get(0)->getPropertyPath());
    }

    #[DataProvider('constraints')]
    public function testWrongConstraintFirst(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(null, new Assert\NotNull());
    }

    /** @return iterable<string, array{Constraint}> */
    public static function constraints(): iterable
    {
        yield 'validity' => [new SpatialAssert\CompoundCurve()];

        yield 'simplicity' => [new SpatialAssert\SimpleCompoundCurve()];
    }
}
