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
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\NoConsecutiveDuplicatePoints;
use Symfony\Component\Validator\Validation;

/** Validates invariants that every line string must satisfy. */
final class LineStringValidation
{
    /**
     * Reject a line string containing adjacent duplicate points.
     *
     * @param LineStringInterface $lineString Line string to validate
     *
     * @throws InvalidValueException when consecutive points are equal
     */
    public static function assertNoConsecutiveDuplicatePoints(LineStringInterface $lineString): void
    {
        $violations = Validation::createValidator()->validate($lineString, new NoConsecutiveDuplicatePoints());
        if (0 !== count($violations)) {
            throw new InvalidValueException((string) $violations->get(0)->getMessage());
        }
    }
}
