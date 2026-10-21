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

use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Polygon;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\LineString as ElevatedLineString;
use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\PolyhedralSurface;
use LongitudeOne\SpatialValidator\Validator\Constraints;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmptyInputHandlingTest extends TestCase
{
    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testEmptySpatialObjectsKeepGeometricContract(Constraint $constraint, string $validator): void
    {
        self::assertInstanceOf(ConstraintValidator::class, new $validator());
        $value = match (true) {
            $constraint instanceof Constraints\Triangle => new Polygon([]),
            $constraint instanceof Constraints\PolyhedralSurface => new PolyhedralSurface([]),
            $constraint instanceof Constraints\SimpleThreeDimensionalLineString => new ElevatedLineString([]),
            default => new LineString([]),
        };
        self::assertTrue($value->isEmpty());
        $count = match (true) {
            $constraint instanceof Constraints\Ring => 2,
            $constraint instanceof Constraints\FirstPointEqualsLastPoint,
            $constraint instanceof Constraints\MinimumPointCount,
            $constraint instanceof Constraints\SameSpatialReference => 1,
            default => 0,
        };
        self::assertCount($count, Validation::createValidator()->validate($value, $constraint));
    }
}
