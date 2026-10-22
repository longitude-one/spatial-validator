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

use LongitudeOne\SpatialTypes\Interfaces\CircularStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\CompoundCurveInterface;
use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;

/**
 * @internal preserve actual arcs and represent linear segments as exact degenerate arcs
 *
 * @phpstan-import-type Arc from CircularArcs
 */
final class CompoundCurveArcs
{
    /** @return array{list<Arc>, list<string>} */
    public static function build(CompoundCurveInterface $value): array
    {
        $arcs = [];
        $paths = [];
        foreach (array_values($value->getCurves()) as $index => $curve) {
            $points = $curve instanceof LineStringInterface || $curve instanceof CircularStringInterface
                ? $curve->getPoints() : [];
            $positions = array_values(array_filter(array_map(PointCoordinates::position(...), $points)));
            $parts = $curve instanceof CircularStringInterface
                ? CircularArcs::build($positions) : self::segments($positions);
            foreach ($parts as $part => $arc) {
                $arcs[] = $arc;
                $paths[] = 'curves[' . $index . '].' . ($curve instanceof CircularStringInterface ? 'arcs' : 'segments')
                    . '[' . $part . ']';
            }
        }

        return [$arcs, $paths];
    }

    /**
     * @param list<list<float>> $positions
     *
     * @return list<Arc>
     */
    private static function segments(array $positions): array
    {
        $parts = [];
        $count = count($positions);
        for ($index = 1; $index < $count; ++$index) {
            $parts[] = CircularArcs::build([$positions[$index - 1], $positions[$index - 1], $positions[$index]])[0];
        }

        return $parts;
    }
}
