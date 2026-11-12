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

use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\MutablePointInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpatialValidationTest extends TestCase
{
    /**
     * @return iterable<string, array{SpatialInterface}>
     */
    public static function spatialValues(): iterable
    {
        foreach (['Dimension2', 'Dimension3z', 'Dimension3m', 'Dimension4zm'] as $dimension) {
            foreach (['Geometry', 'Geography'] as $family) {
                $model = SpatialModelEnum::from($family);
                $mutable = 'LongitudeOne\\SpatialTypes\\Types\\'.$dimension.'\\Point';
                $immutable = 'LongitudeOne\\SpatialTypes\\Types\\'.$dimension.'\\'.$family.'\\PointImmutable';
                foreach ([$mutable, $immutable] as $class) {
                    $point = $class === $mutable ? new $class(family: $model) : new $class();

                    yield $class.' '.$family.' EMPTY' => [$point];

                    $ordinates = array_fill(0, match ($dimension) {
                        'Dimension2' => 2,
                        'Dimension4zm' => 4,
                        default => 3,
                    }, 0);
                    $point = new $class(...$ordinates);
                    if ($point instanceof MutablePointInterface) {
                        $point->setFamily($model);
                    }

                    yield $class.' '.$family.' zero' => [$point];
                }
            }
        }

        yield 'non-empty Point' => [new Point(1, 2)];
    }

    /**
     * Verify standard payload is preserved without mutation.
     */
    public function testStandardPayloadIsPreservedWithoutMutation(): void
    {
        $payload = ['severity' => 'warning', 'source' => 'consumer'];
        $constraint = new Spatial(payload: $payload);
        $violations = Validation::createValidator()->validate(new Point(), $constraint);

        self::assertSame($payload, $constraint->payload);
        self::assertCount(0, $violations);
        self::assertNull(new Spatial()->payload);
    }

    #[DataProvider('spatialValues')]
    /**
     * Verify valid points pass through spatial.
     *
     * @param SpatialInterface $value spatial value to validate
     */
    public function testValidPointsPassThroughSpatial(SpatialInterface $value): void
    {
        self::assertCount(0, Validation::createValidator()->validate($value, new Spatial()));
    }
}
