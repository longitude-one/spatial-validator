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

/** Validate complete patch validity and open-allowed manifold surface topology. */
final class PolyhedralSurface extends Constraint
{
    public const string CONNECTED_ERROR = 'polyhedralsurface-connected';
    public const string DIMENSION_ERROR = 'polyhedralsurface-dimension';
    public const string EMPTY_PATCH_ERROR = 'polyhedralsurface-empty-patch';
    public const string INCIDENCE_ERROR = 'polyhedralsurface-incidence';
    public const string INTERSECTION_ERROR = 'polyhedralsurface-intersection';
    public const string MANIFOLD_ERROR = 'polyhedralsurface-manifold';
    public const string ORIENTATION_ERROR = 'polyhedralsurface-orientation';
    public const string UNAVAILABLE_ERROR = 'polyhedralsurface-unavailable';

    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata; unavailable interactions default to warning
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
