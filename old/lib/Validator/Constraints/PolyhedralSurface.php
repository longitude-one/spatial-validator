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

/** Require connected, consistently oriented polygon patches with a Z ordinate. */
final class PolyhedralSurface extends Constraint
{
    public string $message = 'A polyhedral surface must have connected, consistently oriented 3D patches '
        . 'with at most two faces along each edge.';
}
