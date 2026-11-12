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

/** Validate Polygon members and their filled-surface relationships. */
final class MultiPolygon extends Constraint
{
    public const string INTERSECTION_ERROR = 'multipolygon-intersection';
    public const string UNAVAILABLE_ERROR = 'multipolygon-unavailable';

    /**
     * @param array|null $groups  Validation groups
     * @param mixed      $payload Consumer metadata; unavailable interactions default to warning
     *
     * @phpstan-param string[]|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
