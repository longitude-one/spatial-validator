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

use Symfony\Component\Validator\Constraint;

/** Validate a Triangle using shared Polygon boundaries and triangle restrictions. */
final class Triangle extends Constraint
{
    public const string DISTINCT_ERROR = 'triangle-distinct';
    public const string INTERIOR_ERROR = 'triangle-interior';
    public const string POSITION_COUNT_ERROR = 'triangle-position-count';
    public const string TOPOLOGY_ERROR = 'triangle-topology';

    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
