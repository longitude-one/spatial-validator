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
