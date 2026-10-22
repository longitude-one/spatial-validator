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
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\CircularString as ModelCircle;
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
final class RingValidationTest extends TestCase
{
    #[DataProvider('constraints')]
    public function testDeclaredTypeIsRechecked(Constraint $constraint): void
    {
        $value = self::createStub(LineStringInterface::class);
        $value->method('getType')->willReturn(GeometryTypeEnum::CIRCULARSTRING);
        $violations = Validation::createValidator()->validate($value, $constraint);
        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
    }

    public function testEmptyLineRemainsValidButRingRejectsIt(): void
    {
        $value = LineStringValidationTest::line([]);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, new SpatialAssert\LineString()));
        $violations = $service->validate($value, new SpatialAssert\Ring());
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\Ring::EMPTY_ERROR, $violations->get(0)->getCode());
        self::assertNull($violations->get(0)->getConstraint()?->payload);
    }

    public function testGeographyWarnsWithoutMutatingSharedConstraints(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [1, 0], [0, 1], [0, 0]], family: SpatialModelEnum::GEOGRAPHY);
        $ring = new SpatialAssert\Ring();
        $violations = Validation::createValidator()->validate($value, $ring);
        self::assertCount(2, $violations);
        self::assertSame(SpatialAssert\FirstPointEqualsLastPoint::UNAVAILABLE_ERROR, $violations->get(0)->getCode());
        self::assertSame(SpatialAssert\SimpleLineString::UNAVAILABLE_ERROR, $violations->get(1)->getCode());
        foreach ($violations as $violation) {
            self::assertSame(['severity' => 'warning'], $violation->getConstraint()?->payload);
        }
        self::assertNull($ring->payload);
        $closure = new SpatialAssert\FirstPointEqualsLastPoint(groups: ['custom'], payload: ['source' => 'consumer']);
        $minimum = new SpatialAssert\MinimumPointCount(limit: 5, groups: ['custom']);
        $service = Validation::createValidator();
        $custom = $service->validate($value, [$closure, $minimum], ['custom']);
        self::assertCount(2, $custom);
        self::assertNotSame($closure, $custom->get(0)->getConstraint());
        self::assertSame(['source' => 'consumer'], $custom->get(0)->getConstraint()?->payload);
        self::assertSame($minimum, $custom->get(1)->getConstraint());
        self::assertSame(['source' => 'consumer'], $closure->payload);
    }

    public function testGroupsPayloadAndLocalizedPointErrors(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [INF, 0], [1, 1], [0, 0]]);
        $root = ['boundary' => $value];
        $constraint = new SpatialAssert\Ring(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['boundary' => $constraint], groups: ['custom']),
            ['custom']
        );
        self::assertCount(2, $violations);
        foreach ($violations as $violation) {
            self::assertSame($root, $violation->getRoot());
            self::assertSame('[boundary].points[1]', $violation->getPropertyPath());
            self::assertSame(['custom'], $violation->getConstraint()?->groups);
        }
        self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertNull($violations->get(0)->getConstraint()?->payload);
        self::assertSame(SpatialAssert\NoConsecutiveDuplicatePoints::INPUT_ERROR, $violations->get(1)->getCode());
        self::assertSame(['source' => 'consumer'], $violations->get(1)->getConstraint()?->payload);
    }

    public function testIndependentBoundaryFailuresKeepTheirConstraints(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [1, 0], [1, 1]]);
        $payload = ['source' => 'consumer'];
        $constraint = new SpatialAssert\Ring(payload: $payload);
        $violations = Validation::createValidator()->validate($value, $constraint);
        self::assertCount(2, $violations);
        self::assertSame(SpatialAssert\MinimumPointCount::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertSame(SpatialAssert\FirstPointEqualsLastPoint::INVALID_ERROR, $violations->get(1)->getCode());
        foreach ($violations as $violation) {
            self::assertSame('', $violation->getPropertyPath());
            self::assertSame($payload, $violation->getConstraint()?->payload);
        }
    }

    #[DataProvider('constraints')]
    public function testInputContract(Constraint $constraint): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, $constraint));
        foreach (['', 'LINESTRING (0 0, 1 1)', 42, false, [], new ModelCircle([[1, 0], [-1, 0], [1, 0]])] as $value) {
            $violations = $service->validate($value, $constraint);
            self::assertCount(1, $violations);
            self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        }
    }

    public function testMetadataCompatibilityIsInheritedFromLineValidation(): void
    {
        $points = LineStringValidationTest::line([[0, 0], [1, 0], [0, 1], [0, 0]])->getPoints();
        $line = self::createStub(LineStringInterface::class);
        $line->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        $line->method('getPoints')->willReturn($points);
        $line->method('getDimension')->willReturn(CoordinateDimensionEnum::XYZ);
        $line->method('getFamily')->willReturn(SpatialModelEnum::GEOGRAPHY);
        $line->method('getSpatialReference')->willReturn(SpatialReference::epsg(4326));
        $violations = Validation::createValidator()->validate($line, new SpatialAssert\Ring());
        $errors = [];
        $metadataCodes = [SpatialAssert\Components::FAMILY_ERROR,
            SpatialAssert\Components::DIMENSION_ERROR, SpatialAssert\Components::REFERENCE_ERROR];
        foreach ($violations as $violation) {
            if (in_array($violation->getCode(), $metadataCodes, true)) {
                $errors[] = $violation->getCode();
                self::assertStringStartsWith('points[', $violation->getPropertyPath());
                self::assertNull($violation->getConstraint()?->payload);
            }
        }
        self::assertCount(12, $errors);
    }

    /** @param list<list<null|float|int>> $coordinates */
    #[DataProvider('rings')]
    public function testRingRules(array $coordinates, CoordinateDimensionEnum $dimension, bool $valid): void
    {
        $value = LineStringValidationTest::line($coordinates, $dimension);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Ring());
        self::assertSame($valid, 0 === count($violations));
    }

    /** @return iterable<string, array{list<list<null|float|int>>, CoordinateDimensionEnum, bool}> */
    public static function rings(): iterable
    {
        $layout = CoordinateDimensionEnum::XY;
        $xyz = CoordinateDimensionEnum::XYZ;
        $epsilon = PHP_FLOAT_EPSILON / 4;

        yield 'EMPTY' => [[], $layout, false];

        yield 'single point' => [[[0, 0]], $layout, false];

        yield 'three positions' => [[[0, 0], [1, 0], [0, 0]], $layout, false];

        yield 'triangle with closing repetition' => [[[0, 0], [2, 0], [1, 1], [0, 0]], $layout, true];

        yield 'open four positions' => [[[0, 0], [2, 0], [1, 1], [0, 1]], $layout, false];

        yield 'bow-tie' => [[[0, 0], [2, 2], [0, 2], [2, 0], [0, 0]], $layout, false];

        yield 'collinear retracing' => [[[0, 0], [1, 0], [2, 0], [0, 0]], $layout, false];

        yield 'adjacent duplicate' => [[[0, 0], [1, 0], [1, 0], [0, 1], [0, 0]], $layout, false];

        yield 'EMPTY member' => [[[0, 0], [null, null], [1, 1], [0, 0]], $layout, false];

        yield 'partial null' => [[[0, 0], [1, null], [1, 1], [0, 0]], $layout, false];

        yield 'nonfinite interior point' => [[[0, 0], [INF, 0], [1, 1], [0, 0]], $layout, false];

        yield 'nonfinite endpoint' => [[[NAN, 0], [1, 0], [1, 1], [0, 0]], $layout, false];

        yield 'large coordinates' => [[[0, 0], [1.0e308, 0], [0, 1.0e308], [0, 0]], $layout, true];

        yield 'small coordinates' => [[[0, 0], [1.0e-10, 0], [0, 1.0e-10], [0, 0]], $layout, true];

        yield 'near collinear' => [[[0, 0], [1, 1], [2, 2 + PHP_FLOAT_EPSILON * 2], [0, 0]], $layout, true];

        yield 'near closure interior crossing' => [[[0, 0], [1, 0], [0, 1], [$epsilon, -$epsilon]], $layout, false];

        yield 'Q02 closure without contact movement' => [
            [[0, 0], [1, 0], [0, 1], [-$epsilon, -$epsilon]], $layout, true,
        ];

        yield 'closed vertical XYZ' => [[[0, 0, 0], [0, 2, 0], [0, 1, 1], [0, 0, 0]], $xyz, true];

        yield 'XYZ unequal endpoint Z' => [[[0, 0, 0], [2, 0, 0], [1, 1, 0], [0, 0, 1]], $xyz, false];

        yield 'XYM endpoint M ignored' => [
            [[0, 0, 1], [2, 0, 2], [1, 1, 3], [0, 0, 9]], CoordinateDimensionEnum::XYM, true,
        ];

        yield 'XYZM endpoint M ignored' => [
            [[0, 0, 0, 1], [2, 0, 0, 2], [1, 1, 0, 3], [0, 0, 0, 9]], CoordinateDimensionEnum::XYZM, true,
        ];
    }

    /** @param list<list<null|float|int>> $coordinates */
    #[DataProvider('closures')]
    public function testStandaloneClosure(array $coordinates, bool $closed): void
    {
        $value = LineStringValidationTest::line($coordinates);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\FirstPointEqualsLastPoint());
        self::assertCount($closed ? 0 : 1, $violations);
        if (!$closed) {
            self::assertSame(SpatialAssert\FirstPointEqualsLastPoint::INVALID_ERROR, $violations->get(0)->getCode());
        }
    }

    /** @return iterable<string, array{list<list<null|float|int>>, bool}> */
    public static function closures(): iterable
    {
        yield 'EMPTY not closed' => [[], false];

        yield 'equal endpoints' => [[[1, 2], [1, 2]], true];

        yield 'within scaled equality' => [[[1, 2], [1 + PHP_FLOAT_EPSILON, 2]], true];

        yield 'outside scaled equality' => [[[1, 2], [1 + 2 * PHP_FLOAT_EPSILON, 2]], false];

        yield 'EMPTY endpoint' => [[[null, null], [1, 2]], false];

        yield 'infinite endpoint' => [[[INF, 2], [1, 2]], false];
    }

    #[DataProvider('constraints')]
    public function testUnsupportedValuesThrowDirectly(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedValueException::class);
        $validator->validate('', $constraint);
    }

    #[DataProvider('constraints')]
    public function testWrongConstraintTakesPrecedence(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(null, new Assert\NotNull());
    }

    /** @return iterable<string, array{Constraint}> */
    public static function constraints(): iterable
    {
        yield 'ring' => [new SpatialAssert\Ring()];

        yield 'closure' => [new SpatialAssert\FirstPointEqualsLastPoint()];
    }
}
