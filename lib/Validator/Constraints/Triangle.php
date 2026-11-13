<?php

/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
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
     * @param array|null $groups  Validation groups
     * @param mixed      $payload Consumer metadata
     *
     * @phpstan-param string[]|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
