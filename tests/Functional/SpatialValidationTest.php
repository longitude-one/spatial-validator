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

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints\Point as PointConstraint;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use LongitudeOne\SpatialValidator\Validator\Constraints\SpatialValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpatialValidationTest extends TestCase
{
    public function testContextAndCustomGroupArePreserved(): void
    {
        $point = new Point(1, 2);
        $root = ['location' => $point];
        $constraint = new Spatial(groups: ['spatial']);
        $validator = Validation::createValidator();

        self::assertCount(0, $validator->validate($point, $constraint));
        $collection = new Collection(fields: ['location' => $constraint], groups: ['spatial']);
        $violations = $validator->validate($root, $collection, ['spatial']);

        self::assertCount(1, $violations);
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame('[location]', $violations->get(0)->getPropertyPath());
        self::assertSame($point, $violations->get(0)->getInvalidValue());
        self::assertInstanceOf(PointConstraint::class, $violations->get(0)->getConstraint());
    }

    public function testSpatialContractDoesNotRequireGeometryInspection(): void
    {
        $value = $this->createMock(PointInterface::class);
        $value->expects(self::never())->method('isEmpty');
        $value->expects(self::atLeastOnce())->method('getType')->willReturn(GeometryTypeEnum::POINT);
        $value->expects(self::never())->method('toArray');

        self::assertCount(1, Validation::createValidator()->validate($value, new Spatial()));
    }

    #[DataProvider('spatialValues')]
    public function testSpatialObjectsAlwaysReportUnavailableValidation(SpatialInterface $value): void
    {
        $constraint = new Spatial();
        $violations = Validation::createValidator()->validate($value, $constraint);

        self::assertInstanceOf(ConstraintViolationListInterface::class, $violations);
        self::assertCount(1, $violations);
        $violation = $violations->get(0);
        self::assertSame('Point validation is not implemented yet.', $violation->getMessage());
        self::assertInstanceOf(PointConstraint::class, $violation->getConstraint());
        self::assertNull($constraint->payload);
        self::assertSame(['severity' => 'warning'], $violation->getConstraint()->payload);
        self::assertSame($value, $violation->getInvalidValue());
        self::assertSame($value, $violation->getRoot());
        self::assertSame('', $violation->getPropertyPath());
        self::assertSame(SpatialValidator::class, $constraint->validatedBy());
    }

    /** @return iterable<string, array{SpatialInterface}> */
    public static function spatialValues(): iterable
    {
        foreach (['Dimension2', 'Dimension3z', 'Dimension3m', 'Dimension4zm'] as $dimension) {
            foreach (['Geometry', 'Geography'] as $family) {
                $class = 'LongitudeOne\SpatialTypes\Types\\' . $dimension . '\\' . $family . '\Point';

                // @var class-string<SpatialInterface> $class
                yield $dimension . ' ' . $family . ' EMPTY' => [new $class()];
            }
        }

        yield 'non-empty Point' => [new Point(1, 2)];
    }

    public function testStandardPayloadIsPreservedWithoutMutation(): void
    {
        $payload = ['severity' => 'warning', 'source' => 'consumer'];
        $constraint = new Spatial(payload: $payload);
        $violations = Validation::createValidator()->validate(new Point(), $constraint);

        self::assertSame($payload, $constraint->payload);
        self::assertInstanceOf(PointConstraint::class, $violations->get(0)->getConstraint());
        self::assertNull((new Spatial())->payload);
    }
}
