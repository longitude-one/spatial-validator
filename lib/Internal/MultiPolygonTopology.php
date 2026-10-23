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
 * @internal pairwise filled-surface relationships after complete member validation
 *
 * @phpstan-import-type Rational from ExactRationals
 */
final class MultiPolygonTopology
{
    /** @param list<list<list<float>>> $first
     * @param list<list<list<float>>> $second
     */
    public static function intersects(array $first, array $second): bool
    {
        $firstBasis = PolygonPlane::basis($first[0]);
        $secondBasis = PolygonPlane::basis($second[0]);
        if (null === $firstBasis || null === $secondBasis) {
            return false;
        }
        $left = new RationalSurface($first, $firstBasis);
        $right = new RationalSurface($second, $secondBasis);
        $cross = SurfaceCoordinates::cross($left->normal, $right->normal);
        foreach ($cross as $coordinate) {
            if (0 !== $coordinate[0][0]) {
                return self::differentPlanes($left, $right, $cross);
            }
        }
        if (0 !== $left->side($right->origin)[0][0]) {
            return false;
        }

        return self::coplanar($first, $second, $left, $right);
    }

    /** @param list<list<list<float>>> $rings
     * @param list<list<list<float>>> $other
     * @param array{int, int}         $axes
     */
    private static function boundaryInside(array $rings, array $other, RationalSurface $surface, array $axes): bool
    {
        $project = static fn (array $point): array => [$point[$axes[0]], $point[$axes[1]]];
        foreach ($rings as $ring) {
            foreach (PlanarRing::edges($ring) as [$start, $end]) {
                $split = [$start, $end];
                foreach ($other as $boundary) {
                    foreach ($boundary as $point) {
                        if (PlanarRing::onSegment($project($point), $project($start), $project($end))) {
                            $split[] = $point;
                        }
                    }
                }
                usort($split, static fn (array $first, array $second): int => $first <=> $second);
                $count = count($split);
                for ($index = 1; $index < $count; ++$index) {
                    [$first, $second] = [$split[$index - 1], $split[$index]];
                    if ($first === $second) {
                        continue;
                    }
                    $midpoint = SurfaceCoordinates::interpolate(
                        SurfaceCoordinates::point($first),
                        SurfaceCoordinates::point($second),
                        ExactRationals::number(0.5)
                    );
                    if (1 === $surface->location($midpoint)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /** @param list<list<list<float>>> $first
     * @param list<list<list<float>>> $second
     */
    private static function coplanar(array $first, array $second, RationalSurface $left, RationalSurface $right): bool
    {
        $axes = 0 !== $left->normal[2][0][0] ? [0, 1] : (0 !== $left->normal[1][0][0] ? [0, 2] : [1, 2]);
        foreach ($first as $ring) {
            foreach ($second as $other) {
                $contacts = PolygonRingRelations::contacts(self::project($ring, $axes), self::project($other, $axes));
                if (null === $contacts) {
                    return true;
                }
            }
        }

        return self::boundaryInside($first, $second, $right, $axes)
            || self::boundaryInside($second, $first, $left, $axes);
    }

    /** @param list<Rational> $direction */
    private static function differentPlanes(RationalSurface $first, RationalSurface $second, array $direction): bool
    {
        $axis = 0 !== $direction[0][0][0] ? 0 : (0 !== $direction[1][0][0] ? 1 : 2);
        $cuts = [...$first->cuts($second), ...$second->cuts($first)];
        usort($cuts, static fn (array $left, array $right): int => SurfaceCoordinates::compare(
            $left[$axis],
            $right[$axis]
        ));
        $previous = null;
        foreach ($cuts as $point) {
            if (self::forbidden($first->location($point), $second->location($point), false)) {
                return true;
            }
            if (null !== $previous && 0 !== SurfaceCoordinates::compare($previous[$axis], $point[$axis])) {
                $midpoint = SurfaceCoordinates::interpolate($previous, $point, ExactRationals::number(0.5));
                if (self::forbidden($first->location($midpoint), $second->location($midpoint), true)) {
                    return true;
                }
            }
            $previous = $point;
        }

        return false;
    }

    private static function forbidden(int $first, int $second, bool $interval): bool
    {
        return 0 <= $first && 0 <= $second && ($interval || 0 < $first || 0 < $second);
    }

    /** @param list<list<float>> $ring
     * @param array{int, int} $axes
     *
     * @return list<list<float>>
     */
    private static function project(array $ring, array $axes): array
    {
        return array_map(static fn (array $point): array => [$point[$axes[0]], $point[$axes[1]]], $ring);
    }
}
