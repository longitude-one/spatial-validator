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

use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use Symfony\Component\Validator\Constraint;

/** Require a spatial value to use the supplied spatial reference. */
final class SameSpatialReference extends Constraint
{
    public string $message = 'The {{ member }} spatial reference is not compatible with the spatial '
        . 'reference of this spatial value.';

    /**
     * @param SpatialReference $reference Required spatial reference
     * @param string           $member    Member type for the violation message
     * @param null|string[]    $groups    Validation groups
     * @param mixed            $payload   Constraint payload
     */
    public function __construct(
        public SpatialReference $reference,
        public string $member = 'spatial value',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
