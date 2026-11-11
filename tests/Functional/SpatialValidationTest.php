<?php

/**
 * This file is part of the spatial project.
 *
 * PHP 8.4 | 8.5
 *
 * Copyright Alexandre Tranchant <alexandre.tranchant@gmail.com> 2026
 * Copyright Longitude One 2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
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
                $class = 'LongitudeOne\SpatialTypes\Types\\'.$dimension.'\\'.$family.'\Point';

                yield $dimension.' '.$family.' EMPTY' => [new $class()];

                $ordinates = array_fill(0, match ($dimension) {
                    'Dimension2' => 2,
                    'Dimension4zm' => 4,
                    default => 3,
                }, 0);

                yield $dimension.' '.$family.' zero' => [new $class(...$ordinates)];
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
