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

/** @internal Classify finite, non-degenerate spatial segments: none=0, point=1, interval=2. */
final class SegmentIntersections
{
    /**
     * @param list<float> $first
     * @param list<float> $second
     * @param list<float> $third
     * @param list<float> $fourth
     */
    public static function kind(array $first, array $second, array $third, array $fourth): int
    {
        if (2 === count($first)) {
            return self::planar($first, $second, $third, $fourth);
        }
        if (!RobustPredicates::coplanar($first, $second, $third, $fourth)) {
            return 0;
        }
        foreach ([[0, 1], [0, 2], [1, 2]] as $axes) {
            $firstTurn = RobustPredicates::orientation($first, $second, $third, $axes);
            $secondTurn = RobustPredicates::orientation($first, $second, $fourth, $axes);
            if (0 !== $firstTurn || 0 !== $secondTurn) {
                $projected = [];
                foreach ([$first, $second, $third, $fourth] as $point) {
                    $projected[] = [$point[$axes[0]], $point[$axes[1]]];
                }

                return self::planar(...$projected);
            }
        }
        foreach ([0, 1, 2] as $axis) {
            if ($first[$axis] !== $second[$axis]) {
                return self::interval($first[$axis], $second[$axis], $third[$axis], $fourth[$axis]);
            }
        }

        return 0;
    }

    /** @param list<list<float>> $positions */
    public static function simple(array $positions): bool
    {
        $segments = count($positions) - 1;
        for ($first = 0; $first < $segments; ++$first) {
            for ($second = $first + 1; $second < $segments; ++$second) {
                $kind = self::kind(
                    $positions[$first],
                    $positions[$first + 1],
                    $positions[$second],
                    $positions[$second + 1]
                );
                if (0 === $kind) {
                    continue;
                }
                if (2 === $kind || !self::permittedContact($first, $second, $positions)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function interval(float $first, float $second, float $third, float $fourth): int
    {
        $lower = max(min($first, $second), min($third, $fourth));
        $upper = min(max($first, $second), max($third, $fourth));

        return $lower > $upper ? 0 : ($lower === $upper ? 1 : 2);
    }

    /** @param list<list<float>> $positions */
    private static function permittedContact(int $first, int $second, array $positions): bool
    {
        if ($second === $first + 1) {
            return true;
        }

        return 0 === $first && $second === count($positions) - 2
            && PointCoordinates::same($positions[0], $positions[count($positions) - 1]);
    }

    /**
     * @param list<float> $first
     * @param list<float> $second
     * @param list<float> $third
     * @param list<float> $fourth
     */
    private static function planar(array $first, array $second, array $third, array $fourth): int
    {
        $left = RobustPredicates::orientation($first, $second, $third);
        $right = RobustPredicates::orientation($first, $second, $fourth);
        if (0 === $left && 0 === $right) {
            $axis = $first[0] !== $second[0] ? 0 : 1;

            return self::interval($first[$axis], $second[$axis], $third[$axis], $fourth[$axis]);
        }
        foreach ([0, 1] as $axis) {
            $lower = max(min($first[$axis], $second[$axis]), min($third[$axis], $fourth[$axis]));
            $upper = min(max($first[$axis], $second[$axis]), max($third[$axis], $fourth[$axis]));
            if ($lower > $upper) {
                return 0;
            }
        }
        $otherLeft = RobustPredicates::orientation($third, $fourth, $first);
        $otherRight = RobustPredicates::orientation($third, $fourth, $second);

        return 0 >= $left * $right && 0 >= $otherLeft * $otherRight ? 1 : 0;
    }
}
