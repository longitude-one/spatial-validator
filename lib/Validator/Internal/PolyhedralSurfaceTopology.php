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
 * Checks the incidence graph of polygon edges in XYZ, independently of M.
 *
 * @internal
 *
 * @phpstan-type Position array{float, float, float}
 * @phpstan-type Edge array{Position, Position}
 */
final class PolyhedralSurfaceTopology
{
    /**
     * Split edges at existing vertices before checking incidence and connectivity.
     *
     * @param PolygonInterface[] $patches Surface patches
     */
    public static function isValid(array $patches): bool
    {
        $edges = self::edges($patches);
        $vertices = [];
        foreach ($edges as $patchEdges) {
            foreach ($patchEdges as [$start, $end]) {
                $vertices[] = $start;
                $vertices[] = $end;
            }
        }

        $incidence = [];
        foreach ($edges as $patch => $patchEdges) {
            foreach ($patchEdges as [$start, $end]) {
                foreach (self::split($start, $end, $vertices) as [$first, $last]) {
                    $forward = strcmp(serialize($first), serialize($last)) < 0;
                    $key = serialize($forward ? [$first, $last] : [$last, $first]);
                    $incidence[$key][] = [$patch, $forward];
                }
            }
        }

        return self::validIncidence($incidence, count($patches));
    }

    /**
     * @param PolygonInterface[] $patches Surface patches
     *
     * @return array<int, list<Edge>>
     */
    private static function edges(array $patches): array
    {
        $edges = [];
        foreach (array_values($patches) as $index => $patch) {
            $edges[$index] = [];
            foreach ($patch->getRings() as $ring) {
                $points = array_values($ring->getPoints());
                $pointCount = count($points);
                for ($point = 1; $point < $pointCount; ++$point) {
                    $start = $points[$point - 1];
                    $end = $points[$point];
                    $edges[$index][] = [
                        [(float) $start->getX() + 0.0, (float) $start->getY() + 0.0, (float) $start->getZ() + 0.0],
                        [(float) $end->getX() + 0.0, (float) $end->getY() + 0.0, (float) $end->getZ() + 0.0],
                    ];
                }
            }
        }

        return $edges;
    }

    /**
     * @param array<int, list<int>> $neighbours Edge-connected patch graph
     */
    private static function isConnected(array $neighbours): bool
    {
        if ([] === $neighbours) {
            return true;
        }
        $pending = [0];
        $visited = [];
        while ([] !== $pending) {
            $patch = array_pop($pending);
            if (isset($visited[$patch])) {
                continue;
            }
            $visited[$patch] = true;
            foreach ($neighbours[$patch] as $neighbour) {
                $pending[] = $neighbour;
            }
        }

        return count($visited) === count($neighbours);
    }

    /**
     * @param array{float, float, float} $start    Edge start
     * @param array{float, float, float} $end      Edge end
     * @param array{float, float, float} $position Candidate split point
     */
    private static function parameter(array $start, array $end, array $position): ?float
    {
        $direction = [$end[0] - $start[0], $end[1] - $start[1], $end[2] - $start[2]];
        $offset = [$position[0] - $start[0], $position[1] - $start[1], $position[2] - $start[2]];
        $axis = array_search(max(array_map(abs(...), $direction)), array_map(abs(...), $direction), true);
        if (false === $axis || 0.0 === $direction[$axis]) {
            return null;
        }
        $parameter = $offset[$axis] / $direction[$axis];
        foreach ([0, 1, 2] as $index) {
            if ($offset[$index] * $direction[$axis] !== $offset[$axis] * $direction[$index]) {
                return null;
            }
        }

        return 0.0 <= $parameter && 1.0 >= $parameter ? $parameter : null;
    }

    /**
     * @param array{float, float, float}       $start    Edge start
     * @param array{float, float, float}       $end      Edge end
     * @param list<array{float, float, float}> $vertices All boundary vertices
     *
     * @return list<Edge>
     */
    private static function split(array $start, array $end, array $vertices): array
    {
        $positions = [];
        foreach ($vertices as $vertex) {
            $parameter = self::parameter($start, $end, $vertex);
            if (null !== $parameter) {
                $positions[serialize($vertex)] = [$parameter, $vertex];
            }
        }
        usort($positions, static fn (array $first, array $second): int => $first[0] <=> $second[0]);
        $edges = [];
        $positionCount = count($positions);
        for ($index = 1; $index < $positionCount; ++$index) {
            if ($positions[$index - 1][1] !== $positions[$index][1]) {
                $edges[] = [$positions[$index - 1][1], $positions[$index][1]];
            }
        }

        return $edges;
    }

    /**
     * @param array<string, list<array{int, bool}>> $incidence  Edge uses
     * @param int                                   $patchCount Number of faces
     */
    private static function validIncidence(array $incidence, int $patchCount): bool
    {
        $neighbours = array_fill(0, $patchCount, []);
        foreach ($incidence as $uses) {
            if (2 < count($uses)) {
                return false;
            }
            if (2 === count($uses)) {
                [[$first, $forward], [$second, $otherForward]] = $uses;
                if ($first === $second || $forward === $otherForward) {
                    return false;
                }
                $neighbours[$first][] = $second;
                $neighbours[$second][] = $first;
            }
        }

        return self::isConnected($neighbours);
    }
}
