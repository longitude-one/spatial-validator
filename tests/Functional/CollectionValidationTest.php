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
use LongitudeOne\SpatialTypes\Interfaces\CollectionInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geography\Point as GeographyPoint;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class CollectionValidationTest extends TestCase
{
    /** @param array<mixed> $members */
    public static function aggregate(
        string $type,
        array $members,
        CoordinateDimensionEnum $dimension = CoordinateDimensionEnum::XY,
        SpatialModelEnum $family = SpatialModelEnum::GEOMETRY,
        ?SpatialReference $reference = null,
    ): CollectionInterface {
        $name = 'GeometryCollection' === $type ? 'Collection' : $type;

        /** @var class-string<CollectionInterface> $interface */
        $interface = 'LongitudeOne\SpatialTypes\Interfaces\\' . $name . 'Interface';
        $value = self::createStub($interface);
        $value->method('getType')->willReturn(GeometryTypeEnum::from($type));
        $value->method('getDimension')->willReturn($dimension);
        $value->method('getFamily')->willReturn($family);
        $value->method('getSpatialReference')->willReturn($reference ?? SpatialReference::fromSrid(0));
        $value->method('isEmpty')->willReturn(true);
        $value->method('getElements')->willReturn($members);
        if ('MultiPoint' === $type) {
            $value->method('getPoints')->willReturn($members);
        }

        return $value;
    }

    public function testClosureRequiresEveryMemberAndAggregateNonEmptiness(): void
    {
        $service = Validation::createValidator();
        $closed = LineStringValidationTest::line([[0, 0], [1, 0], [0, 0]]);
        $open = LineStringValidationTest::line([[0, 0], [1, 0]]);
        $empty = LineStringValidationTest::line([]);
        foreach ([[], [$empty], [$empty, $empty]] as $members) {
            $value = self::aggregate('MultiLineString', $members);
            self::assertCount(0, $service->validate($value, new Rules\MultiLineString()));
            $violations = $service->validate($value, new Rules\ClosedMultiLineString());
            self::assertCount(1, $violations);
            self::assertSame(Rules\ClosedMultiLineString::INVALID_ERROR, $violations->get(0)->getCode());
        }
        self::assertCount(
            0,
            $service->validate(self::aggregate('MultiLineString', [$closed]), new Rules\ClosedMultiLineString())
        );
        foreach ([$open, $empty] as $member) {
            $violations = $service->validate(
                self::aggregate('MultiLineString', [$closed, $member]),
                new Rules\ClosedMultiLineString()
            );
            self::assertCount(1, $violations);
            self::assertSame('lineStrings[1]', $violations->get(0)->getPropertyPath());
        }
    }

    #[DataProvider('types')]
    public function testEmptyAndMixedMembersAreRetained(string $type, string $path): void
    {
        $empty = 'MultiLineString' === $type ? LineStringValidationTest::line([]) : new Point();
        $member = 'MultiLineString' === $type ? LineStringValidationTest::line([[0, 0], [1, 1]]) : new Point(1, 2);
        foreach ([[], [$empty], [$empty, $empty], [$empty, $member, $empty]] as $members) {
            $value = self::aggregate($type, $members);
            self::assertCount(0, Validation::createValidator()->validate($value, new Rules\Spatial()));
            self::assertSame($members, $value->getElements());
        }
        $value = self::aggregate($type, [$empty, '']);
        $violations = Validation::createValidator()->validate($value, new Rules\Spatial());
        self::assertCount(1, $violations);
        self::assertSame(Rules\Components::TYPE_ERROR, $violations->get(0)->getCode());
        self::assertSame($path . '[1]', $violations->get(0)->getPropertyPath());
    }

    public function testGeographyOptionalChecksWarnAndPreserveExplicitPayload(): void
    {
        $point = new GeographyPoint(1, 2);
        $line = LineStringValidationTest::line(
            [[0, 0], [1, 1]],
            CoordinateDimensionEnum::XY,
            SpatialModelEnum::GEOGRAPHY
        );
        foreach ([null, ['severity' => 'custom']] as $payload) {
            $pointConstraint = new Rules\SimpleMultiPoint(payload: $payload);
            $value = self::aggregate(
                'MultiPoint',
                [$point],
                CoordinateDimensionEnum::XY,
                SpatialModelEnum::GEOGRAPHY,
                $point->getSpatialReference()
            );
            $violations = Validation::createValidator()->validate($value, $pointConstraint);
            self::assertCount(1, $violations);
            self::assertSame(Rules\SimpleMultiPoint::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
            self::assertNotSame($pointConstraint, $violations->get(0)->getConstraint());
            self::assertSame($payload ?? ['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
            self::assertSame($payload, $pointConstraint->payload);
            $value = self::aggregate(
                'MultiLineString',
                [$line],
                CoordinateDimensionEnum::XY,
                SpatialModelEnum::GEOGRAPHY
            );
            $lineConstraint = new Rules\SimpleMultiLineString(payload: $payload);
            $violations = Validation::createValidator()->validate($value, $lineConstraint);
            self::assertCount(2, $violations);
            self::assertSame(Rules\SimpleMultiLineString::UNAVAILABLE_ERROR, $violations->get(1)->getCode());
            self::assertSame($payload ?? ['severity' => 'warning'], $violations->get(1)->getConstraint()?->payload);
            self::assertSame($payload, $lineConstraint->payload);
            $violations = Validation::createValidator()->validate(
                $value,
                new Rules\ClosedMultiLineString(payload: $payload)
            );
            self::assertCount(1, $violations);
            self::assertSame(Rules\FirstPointEqualsLastPoint::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        }
    }

    public function testHeterogeneousNestingPreservesContextAndPolygonStructureFailure(): void
    {
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        $polygon->method('getFamily')->willReturn(SpatialModelEnum::GEOMETRY);
        $polygon->method('getDimension')->willReturn(CoordinateDimensionEnum::XY);
        $polygon->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $polygon->method('getRings')->willReturn([]);
        $value = self::aggregate(
            'GeometryCollection',
            [new Point(0, 0), self::aggregate('GeometryCollection', [$polygon])]
        );
        $root = ['shape' => $value];
        $constraint = new Rules\GeometryCollection(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame('[shape].geometries[1].geometries[0]', $violations->get(0)->getPropertyPath());
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertNotNull($violations->get(0)->getConstraint());
        self::assertSame(['custom'], $violations->get(0)->getConstraint()->groups);
        self::assertNull($violations->get(0)->getConstraint()->payload);
        self::assertSame('A non-empty Polygon requires an exterior ring.', $violations->get(0)->getMessage());
    }

    #[DataProvider('optionalRules')]
    public function testInvalidMemberPreventsAdditionalPropertyClaims(string $name, string $type): void
    {
        /** @var class-string<Constraint> $class */
        $class = 'LongitudeOne\SpatialValidator\Validator\Constraints\\' . $name;
        $violations = Validation::createValidator()->validate(self::aggregate($type, ['invalid']), new $class());
        self::assertCount(1, $violations);
        self::assertSame(Rules\Components::TYPE_ERROR, $violations->get(0)->getCode());
    }

    public function testMeasuredPointEqualityIgnoresMAndIncludesZ(): void
    {
        $dimensions = [CoordinateDimensionEnum::XYZ, CoordinateDimensionEnum::XYM, CoordinateDimensionEnum::XYZM];
        foreach ($dimensions as $dimension) {
            $first = [1, 2, 3, 4];
            $second = [1, 2, 9, 10];
            $members = LineStringValidationTest::line([$first, $second], $dimension)->getPoints();
            $value = self::aggregate('MultiPoint', $members, $dimension);
            $violations = Validation::createValidator()->validate($value, new Rules\SimpleMultiPoint());
            self::assertCount($dimension->hasZ() ? 0 : 1, $violations);
        }
    }

    #[DataProvider('types')]
    public function testMetadataAndInvalidMemberRemainVisibleUnderEmptyParent(string $type, string $path): void
    {
        $point = self::createStub(PointInterface::class);
        $point->method('getType')->willReturn(GeometryTypeEnum::POINT);
        $point->method('getFamily')->willReturn(SpatialModelEnum::GEOMETRY);
        $point->method('getDimension')->willReturn(CoordinateDimensionEnum::XY);
        $point->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $point->method('getX')->willReturn(INF);
        $point->method('getY')->willReturn(0.0);
        $member = 'MultiLineString' === $type ? LineStringValidationTest::line([[INF, 0], [1, 1]]) : $point;
        $value = self::aggregate($type, [$member]);
        $violations = Validation::createValidator()->validate($value, new Rules\Spatial());
        self::assertGreaterThan(0, count($violations));
        self::assertSame(
            $path . '[0]' . ('MultiLineString' === $type ? '.points[0]' : ''),
            $violations->get(0)->getPropertyPath()
        );
        $empty = 'MultiLineString' === $type ? LineStringValidationTest::line([]) : new Point();
        $value = self::aggregate(
            $type,
            [$empty],
            CoordinateDimensionEnum::XYZM,
            SpatialModelEnum::GEOGRAPHY,
            SpatialReference::epsg(4326)
        );
        $codes = [];
        foreach (Validation::createValidator()->validate($value, new Rules\Spatial()) as $violation) {
            $codes[] = $violation->getCode();
        }
        self::assertSame(
            [Rules\Components::FAMILY_ERROR, Rules\Components::DIMENSION_ERROR, Rules\Components::REFERENCE_ERROR],
            $codes
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function types(): iterable
    {
        yield 'general' => ['GeometryCollection', 'geometries'];

        yield 'points' => ['MultiPoint', 'points'];

        yield 'lines' => ['MultiLineString', 'lineStrings'];
    }

    /** @param list<list<list<float|int>>> $lines */
    #[DataProvider('interactions')]
    public function testMultiLineSimplicity(array $lines, bool $simple): void
    {
        $members = array_map(LineStringValidationTest::line(...), $lines);
        $value = self::aggregate('MultiLineString', $members);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, new Rules\MultiLineString()));
        $violations = $service->validate($value, new Rules\SimpleMultiLineString());
        self::assertSame($simple, 0 === count($violations));
        if (!$simple) {
            self::assertStringStartsWith('lineStrings[', $violations->get(0)->getPropertyPath());
        }
    }

    /** @return iterable<string, array{list<list<list<float|int>>>, bool}> */
    public static function interactions(): iterable
    {
        yield 'boundary' => [[[[0, 0], [1, 1]], [[1, 1], [2, 0]]], true];

        yield 'crossing' => [[[[0, 0], [2, 2]], [[0, 2], [2, 0]]], false];

        yield 'overlap' => [[[[0, 0], [2, 0]], [[1, 0], [3, 0]]], false];

        yield 'endpoint interior' => [[[[0, 0], [2, 0]], [[1, 0], [1, 1]]], false];

        yield 'closed member contact' => [[[[0, 0], [1, 0], [0, 1], [0, 0]], [[0, 0], [-1, -1]]], false];

        yield 'disjoint' => [[[[0, 0], [1, 1]], [[2, 0], [3, 1]]], true];

        yield 'empty member' => [[[], [[0, 0], [1, 1]]], true];

        yield 'all empty' => [[[], []], true];

        yield 'self crossing' => [[[[0, 0], [2, 2], [0, 2], [2, 0]]], false];

        yield 'small crossing' => [[[[0, 0], [1e-12, 1e-12]], [[0, 1e-12], [1e-12, 0]]], false];

        yield 'large crossing' => [[[[0, 0], [1e150, 1e150]], [[0, 1e150], [1e150, 0]]], false];

        yield 'close disjoint' => [[[[0, 0], [1, 0]], [[0, 1e-20], [1, 1e-20]]], true];
    }

    public function testMultiPointSimplicityIsSeparateAndSkipsEmptyPositions(): void
    {
        $value = self::aggregate(
            'MultiPoint',
            [new Point(), new Point(1, 2), new Point(3, 4), new Point(1, 2), new Point()]
        );
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, new Rules\MultiPoint()));
        $violations = $service->validate($value, new Rules\SimpleMultiPoint());
        self::assertCount(1, $violations);
        self::assertSame('points[3]', $violations->get(0)->getPropertyPath());
        self::assertSame('points[1]', $violations->get(0)->getParameters()['{{ other }}']);
        self::assertSame(Rules\SimpleMultiPoint::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertCount(
            0,
            $service->validate(self::aggregate('MultiPoint', [new Point(), new Point()]), new Rules\SimpleMultiPoint())
        );
    }

    #[DataProvider('optionalRules')]
    public function testOptionalDiagnosticsPreserveNestedContext(string $name, string $type): void
    {
        /** @var class-string<Constraint> $class */
        $class = 'LongitudeOne\SpatialValidator\Validator\Constraints\\' . $name;
        $members = 'MultiPoint' === $type
            ? [new Point(1, 2), new Point(1, 2)]
            : [LineStringValidationTest::line([[0, 0], [2, 2]]), LineStringValidationTest::line([[0, 2], [2, 0]])];
        $value = self::aggregate($type, $members);
        $root = ['shape' => $value];
        $constraint = new $class(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertGreaterThan(0, count($violations));
        foreach ($violations as $violation) {
            self::assertSame($root, $violation->getRoot());
            self::assertStringStartsWith('[shape].', $violation->getPropertyPath());
            self::assertSame(['custom'], $violation->getConstraint()?->groups);
            self::assertSame(['source' => 'consumer'], $violation->getConstraint()->payload);
        }
    }

    #[DataProvider('optionalRules')]
    public function testOptionalInputContracts(string $name, string $type): void
    {
        /** @var class-string<Constraint> $class */
        $class = 'LongitudeOne\SpatialValidator\Validator\Constraints\\' . $name;

        /** @var class-string<ConstraintValidator> $validatorClass */
        $validatorClass = $class . 'Validator';
        $constraint = new $class();
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, $constraint));
        foreach (['', 'POINT(0 0)', 42, [], new Point()] as $value) {
            self::assertCount(1, $service->validate($value, $constraint));
        }

        try {
            (new $validatorClass())->validate(null, new Assert\NotNull());
            self::fail('Wrong constraint must throw.');
        } catch (UnexpectedTypeException $exception) {
            self::assertStringContainsString($name, $exception->getMessage());
        }

        try {
            (new $validatorClass())->validate('', $constraint);
            self::fail('Strings must throw directly.');
        } catch (UnexpectedValueException $exception) {
            self::assertStringContainsString($type, $exception->getMessage());
        }
    }

    /** @return iterable<string, array{string, string}> */
    public static function optionalRules(): iterable
    {
        yield 'point simplicity' => ['SimpleMultiPoint', 'MultiPoint'];

        yield 'line simplicity' => ['SimpleMultiLineString', 'MultiLineString'];

        yield 'line closure' => ['ClosedMultiLineString', 'MultiLineString'];
    }

    public function testSpatialLineInteractionsUseZAndActualContact(): void
    {
        $cases = [
            [[[0, 0, 0], [2, 2, 0]], [[0, 2, 1], [2, 0, 1]], true],
            [[[0, 0, 0], [2, 2, 2]], [[0, 2, 0], [2, 0, 2]], false],
            [[[0, 0, 0], [0, 2, 2]], [[0, 0, 2], [0, 2, 0]], false],
            [[[0, 0, 0], [0, 0, 2]], [[0, 0, 2], [0, 0, 3]], true],
            [[[0, 0, 0], [2, 0, 0]], [[2, 0, 0], [3, 0, 0]], true],
        ];
        foreach ($cases as [$first, $second, $simple]) {
            $members = [
                LineStringValidationTest::line($first, CoordinateDimensionEnum::XYZ),
                LineStringValidationTest::line($second, CoordinateDimensionEnum::XYZ),
            ];
            $value = self::aggregate('MultiLineString', $members, CoordinateDimensionEnum::XYZ);
            self::assertSame(
                $simple,
                0 === count(Validation::createValidator()->validate($value, new Rules\SimpleMultiLineString()))
            );
        }
    }

    #[DataProvider('variants')]
    public function testVariantsAndEmptyGeographyProperties(
        CoordinateDimensionEnum $dimension,
        SpatialModelEnum $family,
    ): void {
        $coordinates = [0, 0];
        if ($dimension->hasZ()) {
            $coordinates[] = 2;
        }
        if ($dimension->hasM()) {
            $coordinates[] = 8;
        }
        $line = LineStringValidationTest::line(
            [$coordinates, array_map(static fn (int $ordinate): int => $ordinate + 1, $coordinates)],
            $dimension,
            $family
        );
        $service = Validation::createValidator();
        $value = self::aggregate('MultiLineString', [$line], $dimension, $family);
        self::assertCount(0, $service->validate($value, new Rules\Spatial()));
        foreach (['MultiPoint', 'MultiLineString', 'GeometryCollection'] as $type) {
            self::assertCount(
                0,
                $service->validate(self::aggregate($type, [], $dimension, $family), new Rules\Spatial())
            );
        }
        self::assertCount(
            0,
            $service->validate(self::aggregate('MultiPoint', [], $dimension, $family), new Rules\SimpleMultiPoint())
        );
        self::assertCount(
            0,
            $service->validate(
                self::aggregate('MultiLineString', [], $dimension, $family),
                new Rules\SimpleMultiLineString()
            )
        );
    }

    /** @return iterable<string, array{CoordinateDimensionEnum, SpatialModelEnum}> */
    public static function variants(): iterable
    {
        foreach (CoordinateDimensionEnum::cases() as $dimension) {
            foreach (SpatialModelEnum::cases() as $family) {
                yield $dimension->name . $family->name => [$dimension, $family];
            }
        }
    }
}
