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
use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal @coversNothing
 *
 * @coversNothing
 */
final class CircularStringValidationTest extends TestCase
{
    /** @param list<list<null|float|int>> $coordinates */
    public static function curve(
        array $coordinates,
        CoordinateDimensionEnum $dimension = CoordinateDimensionEnum::XY,
        SpatialModelEnum $family = SpatialModelEnum::GEOMETRY
    ): CircularStringInterface {
        $points = LineStringValidationTest::line($coordinates, $dimension, $family)->getPoints();
        $curve = self::createStub(CircularStringInterface::class);
        $curve->method('getType')->willReturn(GeometryTypeEnum::CIRCULARSTRING);
        $curve->method('getDimension')->willReturn($dimension);
        $curve->method('getFamily')->willReturn($family);
        $curve->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $curve->method('getPoints')->willReturn($points);
        $curve->method('isEmpty')->willReturn([] === $points);

        return $curve;
    }

    #[DataProvider('constraints')]
    public function testDeclaredTypeMustMatch(Constraint $constraint): void
    {
        $curve = self::createStub(CircularStringInterface::class);
        $curve->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        $violations = Validation::createValidator()->validate($curve, $constraint);
        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
    }

    public function testDimensionsAndFamilies(): void
    {
        foreach (SpatialModelEnum::cases() as $family) {
            foreach ([CoordinateDimensionEnum::XYM, CoordinateDimensionEnum::XYZM] as $dimension) {
                $coordinates = CoordinateDimensionEnum::XYM === $dimension
                    ? [[0, 0, 1], [0, 0, 2], [1, 0, 3]] : [[0, 0, 0, 1], [0, 0, 0, 2], [1, 0, 0, 3]];
                $violations = Validation::createValidator()->validate(
                    self::curve($coordinates, $dimension, $family),
                    new SpatialAssert\CircularString()
                );
                self::assertCount(1, $violations);
                self::assertSame(SpatialAssert\CircularArcPoints::INVALID_ERROR, $violations->get(0)->getCode());
            }
        }
        self::assertCount(0, Validation::createValidator()->validate(
            self::curve([[0, 0, 0], [0, 0, 1], [0, 0, 2]], CoordinateDimensionEnum::XYZ),
            new SpatialAssert\CircularString()
        ));
    }

    public function testDirectCompositionPreservesContextAndActiveGroup(): void
    {
        $curve = self::curve([[-1, 0], [0, 1], [1, 0]]);
        $constraint = new SpatialAssert\CircularString(groups: ['direct'], payload: ['source' => 'consumer']);
        $context = $this->createMock(ExecutionContextInterface::class);
        $service = $this->createMock(ValidatorInterface::class);
        $contextual = $this->createMock(ContextualValidatorInterface::class);
        $context->method('getGroup')->willReturn('direct');
        $context->expects(self::once())->method('getValidator')->willReturn($service);
        $service->expects(self::once())->method('inContext')
            ->with(self::identicalTo($context))->willReturn($contextual)
        ;
        $rules = [new SpatialAssert\Components(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\CircularPointCount(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\CircularArcPoints(groups: ['direct'], payload: $constraint->payload)];
        $contextual->expects(self::once())->method('validate')
            ->with(self::identicalTo($curve), $rules, 'direct')->willReturnSelf()
        ;
        $validator = new SpatialAssert\CircularStringValidator();
        $validator->initialize($context);
        $validator->validate($curve, $constraint);
    }

    #[DataProvider('constraints')]
    public function testDirectInputException(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedValueException::class);
        $validator->validate('', $constraint);
    }

    #[DataProvider('constraints')]
    public function testInputContract(Constraint $constraint): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, $constraint));
        foreach (['', 'CIRCULARSTRING EMPTY', 42, false, [], LineStringValidationTest::line([])] as $value) {
            $violations = $service->validate($value, $constraint);
            self::assertCount(1, $violations);
            self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        }
    }

    public function testLocalizedContextAndPayload(): void
    {
        $value = self::curve([[0, 0], [0, 0], [1, 0]]);
        $root = ['shape' => $value];
        $rule = new SpatialAssert\CircularString(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $rule));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $rule], groups: ['custom']),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame('[shape].arcs[0]', $violations->get(0)->getPropertyPath());
        self::assertSame(['source' => 'consumer'], $violations->get(0)->getConstraint()?->payload);
    }

    /** @param list<list<null|float|int>> $coordinates */
    #[DataProvider('cases')]
    public function testValidity(array $coordinates, bool $valid): void
    {
        $violations = Validation::createValidator()->validate(
            self::curve($coordinates),
            new SpatialAssert\CircularString()
        );
        self::assertSame($valid, 0 === count($violations));
    }

    /** @return iterable<string, array{list<list<null|float|int>>, bool}> */
    public static function cases(): iterable
    {
        yield 'EMPTY' => [[], true];

        yield 'one' => [[[0, 0]], false];

        yield 'two' => [[[0, 0], [1, 0]], false];

        yield 'four' => [[[0, 0], [1, 0], [2, 0], [3, 0]], false];

        yield 'collinear' => [[[0, 0], [1, 0], [2, 0]], true];

        yield 'full circle' => [[[1, 0], [-1, 0], [1, 0]], true];

        yield 'AAB' => [[[0, 0], [0, 0], [1, 0]], false];

        yield 'ABB' => [[[0, 0], [1, 0], [1, 0]], false];

        yield 'EMPTY point' => [[[0, 0], [null, null], [1, 0]], false];

        yield 'invalid point despite cardinality' => [[[0, 0], [INF, 0]], false];

        yield 'retraced remains valid' => [[[-1, 0], [0, 1], [1, 0], [0, 1], [-1, 0]], true];
    }

    #[DataProvider('constraints')]
    public function testWrongConstraint(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(null, new Assert\NotNull());
    }

    /** @return iterable<string, array{Constraint}> */
    public static function constraints(): iterable
    {
        yield 'validity' => [new SpatialAssert\CircularString()];

        yield 'count' => [new SpatialAssert\CircularPointCount()];

        yield 'points' => [new SpatialAssert\CircularArcPoints()];

        yield 'simplicity' => [new SpatialAssert\SimpleCircularString()];
    }
}
