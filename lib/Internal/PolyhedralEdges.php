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

/**
 * @internal split exact common boundaries before incidence and local manifold checks
 *
 * @phpstan-type Edge array{int, int, int, int}
 */
final class PolyhedralEdges
{
    /** @var array<string, list<Edge>> */
    public readonly array $segments;

    /** @var list<list<float>> */
    public readonly array $vertices;

    /**
     * @param array $patches patches
     *
     * @phpstan-param list<list<list<list<float>>>> $patches
     */
    public function __construct(array $patches)
    {
        $vertices = [];
        $edges = [];
        foreach ($patches as $face => $rings) {
            foreach ($rings as $index => $ring) {
                $factor = self::ringDirection($rings, $index);
                foreach (PlanarRing::edges($ring) as [$start, $end]) {
                    $edges[] = [$face, $start, $end, $factor];
                    foreach ([$start, $end] as $point) {
                        if (!\in_array($point, $vertices, true)) {
                            $vertices[] = $point;
                        }
                    }
                }
            }
        }
        $this->vertices = $vertices;
        $segments = [];
        foreach ($edges as [$face, $start, $end, $factor]) {
            $split = [];
            foreach ($vertices as $index => $point) {
                if (self::onSegment($point, $start, $end)) {
                    $split[] = $index;
                }
            }
            usort($split, static fn (int $left, int $right): int => $vertices[$left] <=> $vertices[$right]);
            $direction = ($start <=> $end) * $factor;
            $count = \count($split);
            for ($index = 1; $index < $count; ++$index) {
                [$first, $second] = [$split[$index - 1], $split[$index]];
                $key = min($first, $second).':'.max($first, $second);
                $segments[$key][] = [$face, $first, $second, $direction];
            }
        }
        $this->segments = $segments;
    }

    /**
     * @param array $point point
     * @param array $start start
     * @param array $end   end
     *
     * @phpstan-param list<float> $point
     * @phpstan-param list<float> $start
     * @phpstan-param list<float> $end
     */
    public static function onSegment(array $point, array $start, array $end): bool
    {
        foreach ([[0, 1], [0, 2], [1, 2]] as [$first, $second]) {
            $project = static fn (array $value): array => [$value[$first], $value[$second]];
            if (!PlanarRing::onSegment($project($point), $project($start), $project($end))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalize only the direction used in incidence/volume algebra, preserving source rings and warnings.
     *
     * @param array $rings rings
     * @param int   $index component index
     *
     * @phpstan-param list<list<list<float>>> $rings
     * @phpstan-param int<0, max> $index
     */
    public static function ringDirection(array $rings, int $index): int
    {
        if (0 === $index) {
            return 1;
        }
        $axes = [0, 1];
        if (0 === PlanarRing::direction(self::project($rings[0], $axes))) {
            $axes = 0 !== PlanarRing::direction(self::project($rings[0], [0, 2])) ? [0, 2] : [1, 2];
        }

        return PlanarRing::direction(self::project($rings[0], $axes))
            === PlanarRing::direction(self::project($rings[$index], $axes)) ? -1 : 1;
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
