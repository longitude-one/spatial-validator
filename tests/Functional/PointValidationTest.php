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
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialValidator\Internal\PointCoordinates;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
final class PointValidationTest extends TestCase
{
    public function testBoundsAndAbsentAxes(): void
    {
        $service = Validation::createValidator();
        foreach ([[-180, -90], [180, 90]] as [$longitude, $latitude]) {
            $point = $this->candidate(
                CoordinateDimensionEnum::XY,
                SpatialModelEnum::GEOGRAPHY,
                [$longitude, $latitude, NAN, INF],
                false
            );
            self::assertCount(0, $service->validate($point, new SpatialAssert\Point()));
        }
        foreach ([[180.001, 0], [-180.001, 0], [0, 90.001], [0, -90.001]] as [$longitude, $latitude]) {
            $point = $this->candidate(
                CoordinateDimensionEnum::XY,
                SpatialModelEnum::GEOGRAPHY,
                [$longitude, $latitude, null, null],
                false
            );
            $violations = $service->validate($point, new SpatialAssert\Point());
            self::assertSame(SpatialAssert\GeographyBounds::INVALID_ERROR, $violations->get(0)->getCode());
        }
        $point = $this->candidate(
            CoordinateDimensionEnum::XY,
            SpatialModelEnum::GEOMETRY,
            [700000, 200000, null, null],
            false
        );
        self::assertCount(0, $service->validate($point, new SpatialAssert\Point()));
    }

    public function testContextGroupsPayloadAndGenericRouting(): void
    {
        $value = $this->candidate(CoordinateDimensionEnum::XY, SpatialModelEnum::GEOMETRY, [1, null, null, null], true);
        $constraint = new SpatialAssert\Point(groups: ['custom'], payload: ['source' => 'consumer']);
        $root = ['shape' => $value];
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(
                fields: ['shape' => $constraint],
                groups: ['custom']
            ),
            ['custom']
        );
        self::assertCount(1, $violations);
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame('[shape]', $violations->get(0)->getPropertyPath());
        self::assertNotNull($violations->get(0)->getConstraint());
        self::assertSame($constraint->payload, $violations->get(0)->getConstraint()->payload);
        self::assertSame($value, $violations->get(0)->getInvalidValue());
        self::assertSame(
            SpatialAssert\CoordinateLayout::INVALID_ERROR,
            $service->validate(
                $value,
                new SpatialAssert\Spatial()
            )->get(0)->getCode()
        );
    }

    #[DataProvider('variants')]
    public function testDeclaredVariants(CoordinateDimensionEnum $dimension, SpatialModelEnum $family): void
    {
        $service = Validation::createValidator();
        $point = $this->candidate($dimension, $family, [0, -0.0, 0, 0], false);
        self::assertCount(0, $service->validate($point, new SpatialAssert\Point()));
        $empty = $this->candidate($dimension, $family, [null, null, null, null], true);
        self::assertCount(
            0,
            $service->validate(
                $empty,
                new Assert\Sequentially([new Assert\NotBlank(), new SpatialAssert\Point()])
            )
        );
        $partial = $this->candidate($dimension, $family, [1, null, 0, 0], true);
        self::assertSame(
            SpatialAssert\CoordinateLayout::INVALID_ERROR,
            $service->validate(
                $partial,
                new SpatialAssert\Point()
            )->get(0)->getCode()
        );
        $inconsistent = $this->candidate($dimension, $family, [null, null, null, null], false);
        self::assertCount(1, $service->validate($inconsistent, new SpatialAssert\Point()));
        foreach ($dimension->ordinates() as $axis) {
            foreach ([INF, -INF, NAN] as $invalid) {
                $values = ['X' => 0, 'Y' => 0, 'Z' => 0, 'M' => 0];
                $values[$axis] = $invalid;
                $candidate = $this->candidate($dimension, $family, array_values($values), false);
                $violations = $service->validate($candidate, new SpatialAssert\Point());
                self::assertCount(1, $violations);
                self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
            }
        }
    }

    /** @return iterable<string, array{CoordinateDimensionEnum, SpatialModelEnum}> */
    public static function variants(): iterable
    {
        foreach (CoordinateDimensionEnum::cases() as $dimension) {
            foreach (SpatialModelEnum::cases() as $family) {
                yield $dimension->value . $family->value => [$dimension, $family];
            }
        }
    }

    public function testElementaryInputContracts(): void
    {
        $constraints = [
            new SpatialAssert\CoordinateLayout(),
            new SpatialAssert\FiniteCoordinates(),
            new SpatialAssert\GeographyBounds(),
        ];
        foreach ($constraints as $constraint) {
            $class = $constraint->validatedBy();
            $validator = new $class();
            self::assertInstanceOf(ConstraintValidator::class, $validator);
            $validator->validate(null, $constraint);
            self::assertCount(0, Validation::createValidator()->validate(null, $constraint));

            try {
                $validator->validate(null, new Assert\NotNull());
                self::fail('Wrong constraint must throw.');
            } catch (UnexpectedTypeException $exception) {
                self::assertStringContainsString($constraint::class, $exception->getMessage());
            }

            try {
                $validator->validate('', $constraint);
                self::fail('Strings must throw.');
            } catch (UnexpectedValueException $exception) {
                self::assertSame(PointInterface::class, $exception->getExpectedType());
            }
        }
    }

    public function testPresenceAndFiniteComparison(): void
    {
        $service = Validation::createValidator();
        self::assertCount(1, $service->validate(null, [new Assert\NotNull(), new SpatialAssert\Point()]));
        foreach ([null, ''] as $value) {
            $violations = $service->validate(
                $value,
                new Assert\Sequentially([new Assert\NotBlank(), new SpatialAssert\Point()])
            );
            self::assertCount(1, $violations);
            self::assertInstanceOf(Assert\NotBlank::class, $violations->get(0)->getConstraint());
        }
        self::assertTrue(PointCoordinates::equal(0.0, -0.0));
        self::assertTrue(PointCoordinates::equal(1.0, 1.0 + PHP_FLOAT_EPSILON));
        self::assertTrue(PointCoordinates::equal(1.0e15, 1.0e15 + 0.125));
        self::assertFalse(PointCoordinates::equal(1.0, 1.0 + 4 * PHP_FLOAT_EPSILON));
        self::assertFalse(PointCoordinates::equal(INF, INF));
        self::assertFalse(PointCoordinates::equal(0.0, NAN));
    }

    /** @param list<null|float|int> $values */
    private function candidate(
        CoordinateDimensionEnum $dimension,
        SpatialModelEnum $family,
        array $values,
        bool $empty
    ): PointInterface {
        $point = self::createStub(PointInterface::class);
        $point->method('getType')->willReturn(GeometryTypeEnum::POINT);
        $point->method('getDimension')->willReturn($dimension);
        $point->method('getFamily')->willReturn($family);
        $point->method('isEmpty')->willReturn($empty);
        foreach (['X', 'Y', 'Z', 'M'] as $index => $axis) {
            $point->method('get' . $axis)->willReturn($values[$index]);
        }

        return $point;
    }
}
