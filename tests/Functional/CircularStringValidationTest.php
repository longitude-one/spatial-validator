<?php

/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
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
final class CircularStringValidationTest extends CompatibleConstraintValidatorTestCase
{
    /**
     * @return iterable<string, array{list<list<float|int|null>>, bool}>
     */
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

        yield 'invalid point despite cardinality' => [[[0, 0], [\INF, 0]], false];

        yield 'retraced remains valid' => [[[-1, 0], [0, 1], [1, 0], [0, 1], [-1, 0]], true];
    }

    /**
     * @return iterable<string, array{Constraint}>
     */
    public static function constraints(): iterable
    {
        yield 'validity' => [new SpatialAssert\CircularString()];

        yield 'count' => [new SpatialAssert\CircularPointCount()];

        yield 'points' => [new SpatialAssert\CircularArcPoints()];

        yield 'simplicity' => [new SpatialAssert\SimpleCircularString()];
    }

    /**
     * @param array                   $coordinates coordinates
     * @param CoordinateDimensionEnum $dimension   dimension
     * @param SpatialModelEnum        $family      family
     *
     * @phpstan-param list<list<float|int|null>> $coordinates
     */
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

        return $curve;
    }

    #[DataProvider('constraints')]
    /**
     * Verify declared type must match.
     *
     * @param Constraint $constraint applied validation constraint
     */
    public function testDeclaredTypeMustMatch(Constraint $constraint): void
    {
        $curve = self::createStub(CircularStringInterface::class);
        $curve->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        $violations = Validation::createValidator()->validate($curve, $constraint);
        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
    }

    /**
     * Verify dimensions and families.
     */
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

    /**
     * Verify direct composition preserves context and active group.
     */
    public function testDirectCompositionPreservesContextAndActiveGroup(): void
    {
        $curve = self::curve([[-1, 0], [0, 1], [1, 0]]);
        $constraint = new SpatialAssert\CircularString(groups: ['direct'], payload: ['source' => 'consumer']);
        $context = $this->createMock(ExecutionContextInterface::class);
        $service = $this->createMock(ValidatorInterface::class);
        $contextual = $this->createMock(ContextualValidatorInterface::class);
        $context->method('getGroup')->willReturn('direct');
        $context->expects($this->once())->method('getValidator')->willReturn($service);
        $service->expects($this->once())->method('inContext')
            ->with(self::identicalTo($context))->willReturn($contextual)
        ;
        $rules = [new SpatialAssert\Components(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\CircularPointCount(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\CircularArcPoints(groups: ['direct'], payload: $constraint->payload)];
        $contextual->expects($this->once())->method('validate')
            ->with(self::identicalTo($curve), $rules, 'direct')->willReturnSelf()
        ;
        $validator = new SpatialAssert\CircularStringValidator();
        self::validateConstraint($validator, $curve, $constraint, $context);
    }

    #[DataProvider('constraints')]
    /**
     * Verify direct input exception.
     *
     * @param Constraint $constraint applied validation constraint
     */
    public function testDirectInputException(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedValueException::class);
        self::validateConstraint($validator, '', $constraint);
    }

    #[DataProvider('constraints')]
    /**
     * Verify input contract.
     *
     * @param Constraint $constraint applied validation constraint
     */
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

    /**
     * Verify localized context and payload.
     */
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

    /**
     * @param array $coordinates coordinates
     * @param bool  $valid       valid
     *
     * @phpstan-param list<list<float|int|null>> $coordinates
     */
    #[DataProvider('cases')]
    public function testValidity(array $coordinates, bool $valid): void
    {
        $violations = Validation::createValidator()->validate(
            self::curve($coordinates),
            new SpatialAssert\CircularString()
        );
        self::assertSame($valid, 0 === \count($violations));
    }

    #[DataProvider('constraints')]
    /**
     * Verify wrong constraint.
     *
     * @param Constraint $constraint applied validation constraint
     */
    public function testWrongConstraint(Constraint $constraint): void
    {
        $validator = SpecializedValidationTest::validator($constraint);
        $this->expectException(UnexpectedTypeException::class);
        self::validateConstraint($validator, null, new Assert\NotNull());
    }
}
