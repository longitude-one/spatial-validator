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
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;
use Symfony\Component\Validator\Validation;

/** Validates rings when a spatial type requires a valid ring member. */
final class RingValidation
{
    /**
     * Reject a line string that does not meet the ring constraint.
     *
     * @param LineStringInterface $ring Line string to validate
     *
     * @throws InvalidValueException when the line string is not a valid ring
     */
    public static function assertValid(LineStringInterface $ring): void
    {
        $violations = Validation::createValidator()->validate($ring, new Ring());
        if (0 !== count($violations)) {
            throw new InvalidValueException((string) $violations->get(0)->getMessage());
        }
    }
}
