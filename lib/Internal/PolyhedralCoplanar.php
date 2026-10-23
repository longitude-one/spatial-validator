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

/** @internal coincident patch interiors along common coplanar boundaries */
final class PolyhedralCoplanar
{
    /** Detect coincident interiors even when every vertex lies on a shared boundary.
     * @param list<list<list<float>>> $first
     * @param list<list<list<float>>> $second
     * @param array{int, int}         $axes
     */
    public static function sameSide(array $first, array $second, array $axes): bool
    {
        foreach ($first as $index => $ring) {
            $projected = self::project($ring, $axes);
            $side = PlanarRing::direction($projected) * (0 === $index ? 1 : -1);
            foreach ($second as $otherIndex => $other) {
                $otherProjected = self::project($other, $axes);
                $otherSide = PlanarRing::direction($otherProjected) * (0 === $otherIndex ? 1 : -1);
                foreach (PlanarRing::edges($projected) as [$start, $end]) {
                    foreach (PlanarRing::edges($otherProjected) as [$left, $right]) {
                        $axis = $start[0] !== $end[0] ? 0 : 1;
                        if (
                            2 === SegmentIntersections::kind($start, $end, $left, $right)
                            && 0 < (($end[$axis] <=> $start[$axis]) * $side)
                            * (($right[$axis] <=> $left[$axis]) * $otherSide)
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /** @param list<list<float>> $ring
     * @param array{int, int} $axes
     *
     * @return list<list<float>>
     */
    private static function project(array $ring, array $axes): array
    {
        return array_map(static fn (array $point): array => [$point[$axes[0]], $point[$axes[1]]], $ring);
    }
}
