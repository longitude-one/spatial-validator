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

/** Require an empty polygon or one four-position exterior ring without holes. */
final class Triangle extends Constraint
{
    public string $interiorRingsMessage = 'A triangle cannot have interior rings.';

    public string $pointCountMessage = 'A triangle exterior ring must contain exactly four points.';
}
