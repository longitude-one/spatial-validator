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

use InvalidArgumentException as InvalidMinimum;
use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal @coversNothing
 *
 * @coversNothing
 */
final class LineStringValidationTest extends TestCase
{
    /** @param list<list<null|float|int>> $coordinates */
    public static function line(
        array $coordinates,
        CoordinateDimensionEnum $dimension = CoordinateDimensionEnum::XY,
        SpatialModelEnum $family = SpatialModelEnum::GEOMETRY,
    ): LineStringInterface {
        $points = [];
        foreach ($coordinates as $ordinates) {
            $point = self::createStub(PointInterface::class);
            $point->method('getType')->willReturn(GeometryTypeEnum::POINT);
            $point->method('getDimension')->willReturn($dimension);
            $point->method('getFamily')->willReturn($family);
            $point->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
            $present = array_filter($ordinates, static fn ($value): bool => null !== $value);
            $point->method('isEmpty')->willReturn([] === $present);
            foreach (['X', 'Y', 'Z', 'M'] as $index => $axis) {
                $ordinate = $ordinates[$index] ?? null;
                if ('M' === $axis && CoordinateDimensionEnum::XYM === $dimension) {
                    $ordinate = $ordinates[2] ?? null;
                }
                $point->method('get' . $axis)->willReturn($ordinate);
            }
            $points[] = $point;
        }
        $line = self::createStub(LineStringInterface::class);
        $line->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        $line->method('getDimension')->willReturn($dimension);
        $line->method('getFamily')->willReturn($family);
        $line->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $line->method('getPoints')->willReturn($points);
        $line->method('isEmpty')->willReturn([] === $points);

        return $line;
    }

    public function testCustomMinimumAndEmptyAllowance(): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(self::line([]), new SpatialAssert\MinimumPointCount(limit: 4)));
        $violations = $service->validate(self::line([[0, 0], [1, 1]]), new SpatialAssert\MinimumPointCount(limit: 4));
        self::assertSame(SpatialAssert\MinimumPointCount::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertSame('4', $violations->get(0)->getParameters()['{{ limit }}']);
    }

    public function testGroupsPayloadPathsAndRoot(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [0, 0], [200, 91]], family: SpatialModelEnum::GEOGRAPHY);
        $constraint = new SpatialAssert\LineString(groups: ['custom'], payload: ['source' => 'consumer']);
        $root = ['shape' => $value];
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        $codes = [];
        foreach ($violations as $violation) {
            $codes[] = $violation->getCode();
            self::assertSame($root, $violation->getRoot());
            self::assertSame(['custom'], $violation->getConstraint()?->groups);
            if (SpatialAssert\NoConsecutiveDuplicatePoints::INVALID_ERROR === $violation->getCode()) {
                self::assertSame('[shape].points[1]', $violation->getPropertyPath());
                self::assertSame(['source' => 'consumer'], $violation->getConstraint()->payload);
            }
            if (SpatialAssert\GeographyBounds::INVALID_ERROR === $violation->getCode()) {
                self::assertSame('[shape].points[2]', $violation->getPropertyPath());
                self::assertNull($violation->getConstraint()->payload);
            }
        }
        self::assertContains(SpatialAssert\NoConsecutiveDuplicatePoints::INVALID_ERROR, $codes);
        self::assertContains(SpatialAssert\GeographyBounds::INVALID_ERROR, $codes);
    }

    public function testMinimumMustBePositive(): void
    {
        $this->expectException(InvalidMinimum::class);
        new SpatialAssert\MinimumPointCount(limit: 0);
    }

    #[DataProvider('constraints')]
    public function testNullAndUnsupportedValues(Constraint $constraint): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, $constraint));
        foreach (['', 'LINESTRING (0 0, 1 1)', 42, false, []] as $value) {
            $violations = $service->validate($value, $constraint);
            self::assertCount(1, $violations);
            self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        }
    }

    #[DataProvider('constraints')]
    public function testUnsupportedValueThrowsDirectly(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedValueException::class);
        $validator->validate('', $constraint);
    }

    /** @param list<list<null|float|int>> $coordinates */
    #[DataProvider('validityCases')]
    public function testValidity(array $coordinates, bool $valid): void
    {
        $value = self::line($coordinates);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\LineString());
        self::assertSame($valid, 0 === count($violations));
        self::assertSame([] === $coordinates, $value->isEmpty());
    }

    /** @return iterable<string, array{list<list<null|float|int>>, bool}> */
    public static function validityCases(): iterable
    {
        yield 'EMPTY' => [[], true];

        yield 'single point' => [[[0, 0]], false];

        yield 'two distinct' => [[[0, 0], [1, 1]], true];

        yield 'adjacent duplicate' => [[[0, 0], [0, 0], [1, 1]], false];

        yield 'backtracking remains valid' => [[[0, 0], [1, 1], [0, 0]], true];

        yield 'crossing remains valid' => [[[0, 0], [2, 2], [0, 2], [2, 0]], true];

        yield 'EMPTY member' => [[[null, null], [1, 1]], false];

        yield 'two EMPTY members are not an EMPTY line' => [[[null, null], [null, null]], false];

        yield 'partial null' => [[[0, null], [1, 1]], false];

        yield 'infinite' => [[[INF, 0], [1, 1]], false];

        yield 'NaN' => [[[NAN, 0], [1, 1]], false];
    }

    /** @param list<list<null|float|int>> $coordinates */
    #[DataProvider('variants')]
    public function testVariantEquality(array $coordinates, CoordinateDimensionEnum $dimension, bool $valid): void
    {
        foreach (SpatialModelEnum::cases() as $family) {
            $value = self::line($coordinates, $dimension, $family);
            $violations = Validation::createValidator()->validate($value, new SpatialAssert\Spatial());
            self::assertSame($valid, 0 === count($violations));
        }
    }

    /** @return iterable<string, array{list<list<null|float|int>>, CoordinateDimensionEnum, bool}> */
    public static function variants(): iterable
    {
        yield 'M does not separate' => [[[0, 0, 1], [0, 0, 2]], CoordinateDimensionEnum::XYM, false];

        yield 'Z separates' => [[[0, 0, 1], [0, 0, 2]], CoordinateDimensionEnum::XYZ, true];

        yield 'XYZM ignores M' => [[[0, 0, 1, 2], [0, 0, 1, 3]], CoordinateDimensionEnum::XYZM, false];

        yield 'nonfinite M' => [[[0, 0, 1, INF], [1, 1, 2, 3]], CoordinateDimensionEnum::XYZM, false];

        yield 'nonfinite Z' => [[[0, 0, INF], [1, 1, 2]], CoordinateDimensionEnum::XYZ, false];

        yield 'relative equality' => [[[700000.1 + 0.2, 1], [700000.3, 1]], CoordinateDimensionEnum::XY, false];

        yield 'absolute equality' => [[[0.1 + 0.2, 1], [0.3, 1]], CoordinateDimensionEnum::XY, false];

        yield 'distinct' => [[[3, 1], [3.000001, 1]], CoordinateDimensionEnum::XY, true];
    }

    #[DataProvider('constraints')]
    public function testWrongConstraintPrecedesNull(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(null, new Assert\NotNull());
    }

    /** @return iterable<string, array{Constraint}> */
    public static function constraints(): iterable
    {
        yield 'cardinality' => [new SpatialAssert\MinimumPointCount()];

        yield 'EMPTY members' => [new SpatialAssert\NonEmptyPoints()];

        yield 'consecutive duplicates' => [new SpatialAssert\NoConsecutiveDuplicatePoints()];

        yield 'simplicity' => [new SpatialAssert\SimpleLineString()];
    }
}
