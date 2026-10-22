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

namespace LongitudeOne\SpatialValidator\Internal;

use Symfony\Component\Validator\Constraint;

/** @internal Structural building block for complete Polygon validation; never a public validity claim. */
final class PolygonStructure extends Constraint
{
    public const string DEGENERATE_ERROR = 'polygonstructure-degenerate';
    public const string EMPTY_ERROR = 'polygonstructure-empty';
    public const string PLANE_ERROR = 'polygonstructure-plane';
    public const string UNAVAILABLE_ERROR = 'polygonstructure-unavailable';

    /** @param null|string[] $groups Validation groups */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
