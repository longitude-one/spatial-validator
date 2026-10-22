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

/** Explicitly validate simplicity of the actual circular arcs. */
final class SimpleCircularString extends Constraint
{
    public const string INVALID_ERROR = 'simplecircularstring-invalid';
    public const string UNAVAILABLE_ERROR = 'simplecircularstring-unavailable';

    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
