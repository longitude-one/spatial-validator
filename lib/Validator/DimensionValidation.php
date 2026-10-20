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

use LongitudeOne\Core\Diagnostic\DiagnosticValueFormatter;
use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\SpatialTypes\Exception\InvalidDimensionException;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameDimension;
use Symfony\Component\Validator\Validation;

/** Validates dimension compatibility when adding a spatial member. */
final class DimensionValidation
{
    /**
     * Reject a spatial value that uses a different coordinate dimension.
     *
     * @param CoordinateDimensionEnum $dimension Required coordinate dimension
     * @param SpatialInterface        $spatial   Spatial value to validate
     * @param string                  $message   Exception message
     *
     * @throws InvalidDimensionException when dimensions are different
     */
    public static function assertSame(
        CoordinateDimensionEnum $dimension,
        SpatialInterface $spatial,
        string $message,
    ): void {
        $violations = Validation::createValidator()->validate($spatial, new SameDimension($dimension, $message));
        if (0 !== count($violations)) {
            $message = DiagnosticValueFormatter::format((string) $violations->get(0)->getMessage());
            throw new InvalidDimensionException($message);
        }
    }
}
