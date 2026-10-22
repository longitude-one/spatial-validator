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

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
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
        self::assertSame($constraint, $violations->get(0)->getConstraint());
    }

    public function testSpatialContractDoesNotRequireGeometryInspection(): void
    {
        $value = $this->createMock(SpatialInterface::class);
        $value->expects(self::never())->method('isEmpty');
        $value->expects(self::never())->method('getType');
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
        self::assertSame('Spatial validation is not implemented yet.', $violation->getMessage());
        self::assertSame($constraint, $violation->getConstraint());
        self::assertSame(['severity' => 'warning'], $constraint->payload);
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
        self::assertSame($constraint, $violations->get(0)->getConstraint());
        self::assertSame(['severity' => 'warning'], (new Spatial())->payload);
    }
}
