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
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Exception\InvalidFamilyException;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\SameFamily;
use Symfony\Component\Validator\Validation;

/** Validates family compatibility when adding a spatial member. */
final class FamilyValidation
{
    /**
     * Reject a spatial value that belongs to a different family.
     *
     * @param SpatialModelEnum $family  Required spatial family
     * @param SpatialInterface $spatial Spatial value to validate
     * @param string           $message Exception message
     *
     * @throws InvalidFamilyException when families are different
     */
    public static function assertSame(SpatialModelEnum $family, SpatialInterface $spatial, string $message): void
    {
        $violations = Validation::createValidator()->validate($spatial, new SameFamily($family, $message));
        if (0 !== count($violations)) {
            $message = DiagnosticValueFormatter::format((string) $violations->get(0)->getMessage());

            throw new InvalidFamilyException($message);
        }
    }
}
