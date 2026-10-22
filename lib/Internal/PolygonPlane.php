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

/** @internal Exact supporting plane selected from the exterior; no coordinate changes or tolerance. */
final class PolygonPlane
{
    /**
     * @param list<list<float>> $positions
     *
     * @return null|array{list<float>, list<float>, list<float>}
     */
    public static function basis(array $positions): ?array
    {
        if (3 > count($positions)) {
            return null;
        }
        $origin = $positions[0];
        foreach (array_slice($positions, 1) as $second) {
            foreach (array_slice($positions, 2) as $third) {
                foreach (self::axes(count($origin)) as $axes) {
                    if (0 !== RobustPredicates::orientation($origin, $second, $third, $axes)) {
                        return [$origin, $second, $third];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array{list<float>, list<float>, list<float>} $basis
     * @param list<float>                                  $position
     */
    public static function contains(array $basis, array $position): bool
    {
        return 2 === count($position) || RobustPredicates::coplanar($basis[0], $basis[1], $basis[2], $position);
    }

    /** @return list<array{int, int}> */
    private static function axes(int $dimension): array
    {
        return 2 === $dimension ? [[0, 1]] : [[0, 1], [0, 2], [1, 2]];
    }
}
