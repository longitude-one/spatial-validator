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

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use Symfony\Component\Validator\Constraint;

/** Require a spatial value to use the supplied coordinate dimension. */
final class SameDimension extends Constraint
{
    /**
     * @param CoordinateDimensionEnum $dimension Required coordinate dimension
     * @param string                  $message   Violation message
     * @param null|string[]           $groups    Validation groups
     * @param mixed                   $payload   Constraint payload
     */
    public function __construct(
        public CoordinateDimensionEnum $dimension,
        public string $message = 'The spatial dimension is not compatible with the dimension of this spatial value.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
