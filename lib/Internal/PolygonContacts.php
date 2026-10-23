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

/**
 * @internal Bipartite incidence forest of rings and distinct contact positions.
 *
 * A cycle separates the polygon interior. Using contact nodes (rather than pairwise ring edges)
 * preserves valid fans of several boundaries tangent at the same single position.
 */
final class PolygonContacts
{
    /** @var array<string, true> */
    private array $incidences = [];

    /** @var array<string, string> */
    private array $parents = [];

    /** @var list<list<float>> */
    private array $points = [];

    /**
     * @param list<list<float>> $points
     */
    public function add(int $first, int $second, array $points): bool
    {
        $connected = true;
        foreach ($points as $point) {
            $index = array_search($point, $this->points, true);
            if (false === $index) {
                $index = count($this->points);
                $this->points[] = $point;
            }
            foreach ([$first, $second] as $ring) {
                $connected = $this->join('ring' . $ring, 'point' . $index) && $connected;
            }
        }

        return $connected;
    }

    private function join(string $ring, string $point): bool
    {
        $edge = $ring . ':' . $point;
        if (isset($this->incidences[$edge])) {
            return true;
        }
        $this->incidences[$edge] = true;
        $first = $this->root($ring);
        $second = $this->root($point);
        if ($first === $second) {
            return false;
        }
        $this->parents[$first] = $second;

        return true;
    }

    private function root(string $node): string
    {
        while (isset($this->parents[$node])) {
            $node = $this->parents[$node];
        }

        return $node;
    }
}
