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

namespace LongitudeOne\SpatialValidator\Tests\Unit\Validator\Constraints;

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialTypes\Types\Dimension3m\Geometry\Point as MeasuredPoint;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\Point as ElevatedPoint;
use LongitudeOne\SpatialTypes\Types\Dimension4zm\Geometry\Point as FourDimensionalPoint;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameDimension;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameFamily;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameSpatialReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class CompatibilityTest extends TestCase
{
    public function testCustomMessageGroupsAndPayload(): void
    {
        $constraint = new SameFamily(SpatialModelEnum::GEOGRAPHY, 'custom family', ['consumer'], ['source' => 1]);
        $validator = Validation::createValidator();
        $point = new Point(1, 2);
        self::assertCount(0, $validator->validate($point, $constraint));
        $violations = $validator->validate($point, $constraint, ['consumer']);
        self::assertCount(1, $violations);
        self::assertSame('custom family', $violations->get(0)->getMessage());
        self::assertSame(['source' => 1], $constraint->payload);
        self::assertNull($violations->get(0)->getCode());
    }

    #[DataProvider('dimensions')]
    public function testDimensionCompatibility(
        PointInterface $point,
        CoordinateDimensionEnum $dimension,
        bool $valid,
    ): void {
        $constraint = new SameDimension($dimension);
        $violations = Validation::createValidator()->validate($point, $constraint);
        self::assertCount($valid ? 0 : 1, $violations);
        if (!$valid) {
            self::assertSame($constraint->message, $violations->get(0)->getMessage());
            self::assertNull($violations->get(0)->getCode());
        }
    }

    /** @return iterable<string, array{PointInterface, CoordinateDimensionEnum, bool}> */
    public static function dimensions(): iterable
    {
        $points = [new Point(1, 2), new MeasuredPoint(1, 2, 4), new ElevatedPoint(1, 2, 3),
            new FourDimensionalPoint(1, 2, 3, 4)];
        foreach ($points as $point) {
            foreach (CoordinateDimensionEnum::cases() as $dimension) {
                yield $point->getDimension()->name . '/' . $dimension->name => [
                    $point, $dimension, $point->getDimension() === $dimension,
                ];
            }
        }
    }

    public function testSpatialReferenceMessageRetainsMemberParameter(): void
    {
        $constraint = new SameSpatialReference(SpatialReference::fromSrid(4326), 'point');
        $violations = Validation::createValidator()->validate(new Point(1, 2), $constraint);
        self::assertCount(1, $violations);
        self::assertSame(
            'The point spatial reference is not compatible with the spatial reference of this spatial value.',
            $violations->get(0)->getMessage()
        );
        self::assertSame(['{{ member }}' => 'point'], $violations->get(0)->getParameters());
        self::assertNull($violations->get(0)->getCode());
    }
}
