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

namespace LongitudeOne\SpatialValidator\Internal;

/** @internal detect transverse unsplit boundary crossings */
final class PolyhedralBoundary
{
    /**
     * Boundary crossings at unsupplied positions cannot form a valid local face fan.
     *
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param list<list<list<float>>> $first
     * @phpstan-param list<list<list<float>>> $second
     */
    public static function crossing(array $first, array $second): bool
    {
        foreach ($first as $ring) {
            foreach ($second as $other) {
                foreach (PlanarRing::edges($ring) as [$start, $end]) {
                    foreach (PlanarRing::edges($other) as [$left, $right]) {
                        if (1 === SegmentIntersections::kind($start, $end, $left, $right)
                            && !self::endpointContact($start, $end, $left, $right)
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array $start start
     * @param array $end   end
     * @param array $left  left
     * @param array $right right
     *
     * @phpstan-param list<float> $start
     * @phpstan-param list<float> $end
     * @phpstan-param list<float> $left
     * @phpstan-param list<float> $right
     */
    private static function endpointContact(array $start, array $end, array $left, array $right): bool
    {
        foreach ([$start, $end, $left, $right] as $point) {
            if (PolyhedralEdges::onSegment($point, $start, $end) && PolyhedralEdges::onSegment($point, $left, $right)) {
                return true;
            }
        }

        return false;
    }
}
