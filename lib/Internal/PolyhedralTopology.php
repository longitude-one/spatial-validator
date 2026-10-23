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

/** @internal complete validated-face topology, without changing coordinates */
final class PolyhedralTopology
{
    /** @param list<list<list<list<float>>>> $patches */
    public static function closed(array $patches): bool
    {
        if ([] === $patches) {
            return false;
        }
        foreach ((new PolyhedralEdges($patches))->segments as $uses) {
            if (2 !== count($uses)) {
                return false;
            }
        }

        return self::volume($patches);
    }

    /** @param list<list<list<list<float>>>> $patches
     * @return list<string>
     */
    public static function errors(array $patches): array
    {
        $edges = new PolyhedralEdges($patches);
        $errors = [];
        $adjacency = array_fill(0, count($patches), []);
        foreach ($edges->segments as $uses) {
            if (2 < count($uses)) {
                $errors[] = 'incidence';
            }
            if (2 === count($uses)) {
                [$left, $right] = $uses;
                $first = $left[0];
                $second = $right[0];
                $adjacency[$first][] = $second;
                $adjacency[$second][] = $first;
                if ($left[3] === $right[3]) {
                    $errors[] = 'orientation';
                }
            }
        }
        if (!self::connected($adjacency)) {
            $errors[] = 'connected';
        }
        if (!self::manifold($edges)) {
            $errors[] = 'manifold';
        }
        if (self::intersections($patches)) {
            $errors[] = 'intersection';
        }

        return array_values(array_unique($errors));
    }

    /** @param array<int, list<int>> $graph */
    private static function connected(array $graph): bool
    {
        $pending = array_slice(array_keys($graph), 0, 1);
        $seen = [];
        while ([] !== $pending) {
            $face = array_pop($pending);
            if (isset($seen[$face])) {
                continue;
            }
            $seen[$face] = true;
            $pending = [...$pending, ...$graph[$face]];
        }

        return count($seen) === count($graph);
    }

    /** @param list<list<list<list<float>>>> $patches */
    private static function intersections(array $patches): bool
    {
        foreach ($patches as $second => $patch) {
            foreach ($patches as $first => $other) {
                if ($first >= $second) {
                    break;
                }
                if (PolyhedralIntersections::intersects($other, $patch)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function manifold(PolyhedralEdges $edges): bool
    {
        foreach (array_keys($edges->vertices) as $vertex) {
            $fan = [];
            $boundary = 0;
            foreach ($edges->segments as $uses) {
                if ($vertex !== $uses[0][1] && $vertex !== $uses[0][2]) {
                    continue;
                }
                foreach ($uses as [$face]) {
                    $fan[$face] ??= [];
                }
                if (1 === count($uses)) {
                    ++$boundary;
                }
                if (2 === count($uses)) {
                    $fan[$uses[0][0]][] = $uses[1][0];
                    $fan[$uses[1][0]][] = $uses[0][0];
                }
            }
            if (!in_array($boundary, [0, 2], true) || !self::connected($fan)) {
                return false;
            }
        }

        return true;
    }

    /** Exact oriented tetrahedron sum; nonzero volume is independent of origin and scale.
     * @param list<list<list<list<float>>>> $patches
     */
    private static function volume(array $patches): bool
    {
        $sum = ExactRationals::number(0);
        foreach ($patches as $rings) {
            foreach ($rings as $index => $ring) {
                $factor = ExactRationals::number(PolyhedralEdges::ringDirection($rings, $index));
                $origin = SurfaceCoordinates::point($ring[0]);
                foreach (PlanarRing::edges($ring) as [$first, $second]) {
                    $sum = ExactRationals::rationalAdd(
                        $sum,
                        ExactRationals::rationalMultiply($factor, SurfaceCoordinates::dot(
                            $origin,
                            SurfaceCoordinates::cross(
                                SurfaceCoordinates::point($first),
                                SurfaceCoordinates::point($second)
                            )
                        ))
                    );
                }
            }
        }

        return 0 !== $sum[0][0];
    }
}
