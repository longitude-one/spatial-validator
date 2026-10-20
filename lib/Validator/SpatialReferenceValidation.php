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

use LongitudeOne\SpatialTypes\Exception\InvalidSridException;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameSpatialReference;
use Symfony\Component\Validator\Validation;

/** Validates spatial-reference compatibility when adding a spatial member. */
final class SpatialReferenceValidation
{
    /**
     * Reject a spatial value declared in a different spatial reference.
     *
     * @param SpatialReference $reference Required spatial reference
     * @param SpatialInterface $spatial   Spatial value to validate
     * @param string           $member    Member type for the violation message
     *
     * @throws InvalidSridException when spatial references are different
     */
    public static function assertSame(SpatialReference $reference, SpatialInterface $spatial, string $member): void
    {
        $violations = Validation::createValidator()->validate($spatial, new SameSpatialReference($reference, $member));
        if (0 !== count($violations)) {
            throw new InvalidSridException((string) $violations->get(0)->getMessage());
        }
    }
}
