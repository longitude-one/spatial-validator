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

/**
 * Require a line string to be simple in its two-dimensional projection.
 *
 * Z and M ordinates are ignored, as specified for ISO/IEC 13249-3
 * ST_IsSimple().
 */
final class SimpleLineString extends Constraint
{
    public string $message = 'A line string must not self-intersect, self-touch, or overlap in two dimensions.';
}
