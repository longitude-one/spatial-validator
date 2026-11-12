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

/** @internal coincident patch interiors along common coplanar boundaries */
final class PolyhedralCoplanar
{
    /**
     * Detect coincident interiors even when every vertex lies on a shared boundary.
     *
     * @param array $first  first operand
     * @param array $second second operand
     * @param array $axes   axes
     *
     * @phpstan-param list<list<list<float>>> $first
     * @phpstan-param list<list<list<float>>> $second
     * @phpstan-param array{int, int} $axes
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

    /**
     * @param array $ring ring
     * @param array $axes axes
     *
     * @phpstan-param list<list<float>> $ring
     * @phpstan-param array{int, int} $axes
     *
     * @return list<list<float>>
     */
    private static function project(array $ring, array $axes): array
    {
        return array_map(static fn (array $point): array => [$point[$axes[0]], $point[$axes[1]]], $ring);
    }
}
