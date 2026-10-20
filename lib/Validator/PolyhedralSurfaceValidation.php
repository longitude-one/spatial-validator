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

namespace LongitudeOne\SpatialValidator\Validator;

use LongitudeOne\SpatialTypes\Exception\InvalidValueException;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\PolyhedralSurface;
use Symfony\Component\Validator\Validation;

/** Validates polygon boundaries when a spatial type requires a polyhedral surface. */
final class PolyhedralSurfaceValidation
{
    /**
     * Reject a polygon that does not meet the polyhedral surface constraint.
     *
     * @param PolyhedralSurfaceInterface $polygon Polygon to validate
     *
     * @throws InvalidValueException when the polygon is not a valid polyhedral surface
     */
    public static function assertValid(PolyhedralSurfaceInterface $polygon): void
    {
        $violations = Validation::createValidator()->validate($polygon, new PolyhedralSurface());
        if (0 !== count($violations)) {
            throw new InvalidValueException((string) $violations->get(0)->getMessage());
        }
    }
}
