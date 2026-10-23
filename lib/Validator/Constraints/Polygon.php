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

/** Validate complete Polygon structure, topology and relative ring orientation. */
final class Polygon extends Constraint
{
    public const string DISCONNECTED_ERROR = 'polygon-disconnected';
    public const string INTERSECTION_ERROR = 'polygon-intersection';
    public const string NESTED_ERROR = 'polygon-nested';
    public const string ORIENTATION_ERROR = 'polygon-orientation';
    public const string OUTSIDE_ERROR = 'polygon-outside';
    public const string UNAVAILABLE_ERROR = 'polygon-unavailable';

    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata; unavailable/orientation diagnostics default to warning
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
