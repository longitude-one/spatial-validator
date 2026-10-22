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

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use LongitudeOne\Core\Enum\SpatialModelEnum;
use Symfony\Component\Validator\Constraint;

/** Require a spatial value to belong to the supplied spatial family. */
final class SameFamily extends Constraint
{
    /**
     * @param SpatialModelEnum $family  Required spatial family
     * @param string           $message Violation message
     * @param null|string[]    $groups  Validation groups
     * @param mixed            $payload Constraint payload
     */
    public function __construct(
        public SpatialModelEnum $family,
        public string $message = 'The spatial family is not compatible with the family of this spatial value.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
