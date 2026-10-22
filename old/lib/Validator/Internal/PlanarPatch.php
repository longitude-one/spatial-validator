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

namespace LongitudeOne\SpatialValidator\Validator\Internal;

use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;

/**
 * Checks that a polygon has a non-degenerate plane and finite XYZ coordinates.
 *
 * @internal
 *
 * @phpstan-type Vector array{float, float, float}
 */
final class PlanarPatch
{
    /**
     * @param PolygonInterface $patch Polygon patch
     */
    public static function isValid(PolygonInterface $patch): bool
    {
        $rings = [];
        foreach ($patch->getRings() as $ring) {
            $positions = [];
            foreach ($ring->getPoints() as $point) {
                $position = [(float) $point->getX(), (float) $point->getY(), (float) $point->getZ()];
                if (!self::isFinite($position)) {
                    return false;
                }
                $positions[] = $position;
            }
            if (4 > count($positions)) {
                return false;
            }
            $rings[] = $positions;
        }
        if ([] === $rings) {
            return false;
        }
        $origin = $rings[0][0];
        $normal = self::normal($rings[0]);
        if ([0.0, 0.0, 0.0] === $normal) {
            return false;
        }

        return self::isCoplanar($rings, $origin, $normal);
    }

    /**
     * @param list<list<Vector>> $rings  Ring positions
     * @param Vector             $origin Plane origin
     * @param Vector             $normal Plane normal
     */
    private static function isCoplanar(array $rings, array $origin, array $normal): bool
    {
        foreach ($rings as $ring) {
            foreach ($ring as $position) {
                $offset = self::subtract($position, $origin);
                if (0.0 !== $offset[0] * $normal[0] + $offset[1] * $normal[1] + $offset[2] * $normal[2]) {
                    return false;
                }
            }
        }

        return true;
    }

    /** @param Vector $position XYZ position */
    private static function isFinite(array $position): bool
    {
        return is_finite($position[0]) && is_finite($position[1]) && is_finite($position[2]);
    }

    /**
     * @param list<array{float, float, float}> $positions Ring positions
     *
     * @return Vector
     */
    private static function normal(array $positions): array
    {
        $direction = self::subtract($positions[1], $positions[0]);
        foreach (array_slice($positions, 2) as $position) {
            $offset = self::subtract($position, $positions[0]);
            $normal = [$direction[1] * $offset[2] - $direction[2] * $offset[1],
                $direction[2] * $offset[0] - $direction[0] * $offset[2],
                $direction[0] * $offset[1] - $direction[1] * $offset[0]];
            if ([0.0, 0.0, 0.0] !== $normal) {
                return $normal;
            }
        }

        return [0.0, 0.0, 0.0];
    }

    /**
     * @param array{float, float, float} $first  First position
     * @param array{float, float, float} $second Second position
     *
     * @return Vector
     */
    private static function subtract(array $first, array $second): array
    {
        return [$first[0] - $second[0], $first[1] - $second[1], $first[2] - $second[2]];
    }
}
