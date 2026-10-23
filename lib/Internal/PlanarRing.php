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

/** @internal Exact orientation and point location on a validated, projected linear ring. */
final class PlanarRing
{
    /** @param list<list<float>> $ring */
    public static function direction(array $ring): int
    {
        $area = [0, []];
        foreach (self::edges($ring) as [$first, $second]) {
            $positive = RobustPredicates::multiply(
                RobustPredicates::integer($first[0]),
                RobustPredicates::integer($second[1])
            );
            $negative = RobustPredicates::multiply(
                RobustPredicates::integer($first[1]),
                RobustPredicates::integer($second[0])
            );
            $negative[0] *= -1;
            $area = RobustPredicates::add($area, RobustPredicates::add($positive, $negative));
        }

        return $area[0];
    }

    /**
     * @param list<list<float>> $ring
     *
     * @return list<array{list<float>, list<float>}>
     */
    public static function edges(array $ring): array
    {
        $edges = [];
        $previous = $ring[count($ring) - 1];
        foreach ($ring as $point) {
            if ($previous !== $point) {
                $edges[] = [$previous, $point];
            }
            $previous = $point;
        }

        return $edges;
    }

    /**
     * Classify the exact midpoint of two supplied positions: inside=1, outside=2.
     * Callers split boundaries at every contact, so no sampled open interval lies on the other boundary.
     *
     * @param list<float>       $first
     * @param list<float>       $second
     * @param list<list<float>> $ring
     */
    public static function midpointLocation(array $first, array $second, array $ring): int
    {
        $midpoint = [];
        foreach ([0, 1] as $axis) {
            $midpoint[] = RobustPredicates::add(
                RobustPredicates::integer($first[$axis]),
                RobustPredicates::integer($second[$axis])
            );
        }
        $winding = 0;
        foreach (self::edges($ring) as [$start, $end]) {
            $startSide = self::relative($start, $midpoint)[1][0];
            $endSide = self::relative($end, $midpoint)[1][0];
            $turn = self::midpointTurn($start, $end, $midpoint);
            if (0 >= $startSide && 0 < $endSide && 0 < $turn) {
                ++$winding;
            }
            if (0 < $startSide && 0 >= $endSide && 0 > $turn) {
                --$winding;
            }
        }

        return 0 === $winding ? 2 : 1;
    }

    /**
     * @param list<float> $point
     * @param list<float> $first
     * @param list<float> $second
     */
    public static function onSegment(array $point, array $first, array $second): bool
    {
        return 0 === RobustPredicates::orientation($first, $second, $point)
            && $point[0] >= min($first[0], $second[0]) && $point[0] <= max($first[0], $second[0])
            && $point[1] >= min($first[1], $second[1]) && $point[1] <= max($first[1], $second[1]);
    }

    /**
     * @param list<float>                 $start
     * @param list<float>                 $end
     * @param list<array{int, list<int>}> $midpoint Doubled exact coordinates
     */
    private static function midpointTurn(array $start, array $end, array $midpoint): int
    {
        $first = self::relative($start, $midpoint);
        $second = self::relative($end, $midpoint);
        $left = RobustPredicates::multiply($first[0], $second[1]);
        $right = RobustPredicates::multiply($first[1], $second[0]);
        $right[0] *= -1;

        return RobustPredicates::add($left, $right)[0];
    }

    /**
     * @param list<float>                 $point
     * @param list<array{int, list<int>}> $midpoint
     *
     * @return list<array{int, list<int>}>
     */
    private static function relative(array $point, array $midpoint): array
    {
        $relative = [];
        foreach ([0, 1] as $axis) {
            $coordinate = RobustPredicates::integer($point[$axis]);
            $negative = $midpoint[$axis];
            $negative[0] *= -1;
            $relative[] = RobustPredicates::add(RobustPredicates::add($coordinate, $coordinate), $negative);
        }

        return $relative;
    }
}
