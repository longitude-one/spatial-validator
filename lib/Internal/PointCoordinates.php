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

use LongitudeOne\SpatialTypes\Interfaces\PointInterface;

/** @internal Shared coordinate access and finite spatial comparison. */
final class PointCoordinates
{
    /** Compare finite coordinates without changing input or applying a proximity tolerance. */
    public static function equal(float $first, float $second): bool
    {
        return is_finite($first) && is_finite($second)
            && abs($first - $second) <= PHP_FLOAT_EPSILON * max(1.0, abs($first), abs($second));
    }

    /** @return array<string, null|float|int> */
    public static function ordinates(PointInterface $point): array
    {
        $ordinates = ['X' => $point->getX(), 'Y' => $point->getY()];
        if ($point->getDimension()->hasZ()) {
            $ordinates['Z'] = $point->getZ();
        }
        if ($point->getDimension()->hasM()) {
            $ordinates['M'] = $point->getM();
        }

        return $ordinates;
    }
}
