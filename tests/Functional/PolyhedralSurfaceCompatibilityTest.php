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

use LongitudeOne\SpatialTypes\Types\Dimension3z\Geometry\PolyhedralSurfaceImmutable as ImmutableSurface;
use LongitudeOne\SpatialTypes\Types\Dimension3z\PolyhedralSurface as MutableSurface;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class PolyhedralSurfaceCompatibilityTest extends TestCase
{
    /** Verify current mutable and immutable patch models retain geometric validation. */
    public function testCurrentPatchModels(): void
    {
        $face = [[[0, 0, 0], [4, 0, 0], [0, 4, 0], [0, 0, 0]]];
        $service = Validation::createValidator();
        foreach ([new MutableSurface([$face]), new ImmutableSurface([$face])] as $valid) {
            self::assertCount(0, $service->validate($valid, new Rules\Spatial()));
        }
        foreach ([new MutableSurface([[]]), new ImmutableSurface([[]])] as $invalid) {
            $violations = $service->validate($invalid, new Rules\Spatial());
            self::assertGreaterThan(0, \count($violations));
            foreach ($violations as $violation) {
                self::assertNotNull($violation->getCode());
                self::assertNull($violation->getConstraint()?->payload);
            }
        }
    }
}
