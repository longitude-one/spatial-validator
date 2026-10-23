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

/** @internal Relations between individually simple coplanar rings, without coordinate tolerance. */
final class PolygonRingRelations
{
    /**
     * Return exact isolated contact positions, or null for a proper crossing/shared interval.
     *
     * @param list<list<float>> $first
     * @param list<list<float>> $second
     *
     * @return null|list<list<float>>
     */
    public static function contacts(array $first, array $second): ?array
    {
        $contacts = [];
        foreach (PlanarRing::edges($first) as [$start, $end]) {
            foreach (PlanarRing::edges($second) as [$otherStart, $otherEnd]) {
                $kind = SegmentIntersections::kind($start, $end, $otherStart, $otherEnd);
                if (0 === $kind) {
                    continue;
                }
                $endpoints = self::commonEndpoints($start, $end, $otherStart, $otherEnd);
                if (2 === $kind || [] === $endpoints) {
                    return null;
                }
                foreach ($endpoints as $point) {
                    if (!in_array($point, $contacts, true)) {
                        $contacts[] = $point;
                    }
                }
            }
        }

        return $contacts;
    }

    /**
     * Bitmask of inside=1/outside=2 open boundary intervals relative to the other ring.
     * Split at all contacts, including vertices of the other ring lying inside an edge.
     *
     * @param list<list<float>> $ring
     * @param list<list<float>> $other
     * @param list<list<float>> $contacts
     */
    public static function locations(array $ring, array $other, array $contacts): int
    {
        $locations = 0;
        foreach (PlanarRing::edges($ring) as [$start, $end]) {
            $split = [$start, $end];
            foreach ($contacts as $point) {
                if (PlanarRing::onSegment($point, $start, $end) && !in_array($point, $split, true)) {
                    $split[] = $point;
                }
            }
            usort($split, static fn (array $left, array $right): int => $left <=> $right);
            $count = count($split);
            for ($index = 1; $index < $count; ++$index) {
                $locations |= PlanarRing::midpointLocation($split[$index - 1], $split[$index], $other);
            }
        }

        return $locations;
    }

    /**
     * @param list<float> $start
     * @param list<float> $end
     * @param list<float> $otherStart
     * @param list<float> $otherEnd
     *
     * @return list<list<float>>
     */
    private static function commonEndpoints(array $start, array $end, array $otherStart, array $otherEnd): array
    {
        $points = [];
        foreach ([$start, $end, $otherStart, $otherEnd] as $point) {
            if (PlanarRing::onSegment($point, $start, $end) && PlanarRing::onSegment($point, $otherStart, $otherEnd)) {
                $points[] = $point;
            }
        }

        return $points;
    }
}
