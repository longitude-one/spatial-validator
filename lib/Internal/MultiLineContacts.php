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

/** @internal Exact segment interactions; only actual shared open-line boundaries are allowed. */
final class MultiLineContacts
{
    /**
     * @param list<list<float>> $first
     * @param list<list<float>> $second
     */
    public static function permitted(array $first, array $second): bool
    {
        $leftCount = count($first) - 1;
        $rightCount = count($second) - 1;
        for ($left = 0; $left < $leftCount; ++$left) {
            for ($right = 0; $right < $rightCount; ++$right) {
                $kind = SegmentIntersections::kind(
                    $first[$left],
                    $first[$left + 1],
                    $second[$right],
                    $second[$right + 1]
                );
                if (0 === $kind) {
                    continue;
                }
                if (2 === $kind || !self::boundaryContact($first, $second, $left, $right)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param list<list<float>> $first
     * @param list<list<float>> $second
     */
    private static function boundaryContact(array $first, array $second, int $left, int $right): bool
    {
        $firstEnds = [$first[0], $first[count($first) - 1]];
        $secondEnds = [$second[0], $second[count($second) - 1]];
        if (PointCoordinates::same(...$firstEnds) || PointCoordinates::same(...$secondEnds)) {
            return false;
        }
        foreach ($firstEnds as $endpoint) {
            if (
                in_array($endpoint, $secondEnds, true)
                && in_array($endpoint, [$first[$left], $first[$left + 1]], true)
                && in_array($endpoint, [$second[$right], $second[$right + 1]], true)
            ) {
                return true;
            }
        }

        return false;
    }
}
