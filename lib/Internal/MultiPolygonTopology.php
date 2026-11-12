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
 * @internal pairwise filled-surface relationships after complete member validation
 *
 * @phpstan-import-type Rational from ExactRationals
 */
final class MultiPolygonTopology
{
    /**
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param list<list<list<float>>> $first
     * @phpstan-param list<list<list<float>>> $second
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

    /**
     * @param array           $rings   rings
     * @param array           $other   other
     * @param RationalSurface $surface surface
     * @param array           $axes    axes
     *
     * @phpstan-param list<list<list<float>>> $rings
     * @phpstan-param list<list<list<float>>> $other
     * @phpstan-param array{int, int} $axes
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
                $count = \count($split);
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

    /**
     * @param array           $first  first operand
     * @param array           $second second operand
     * @param RationalSurface $left   left
     * @param RationalSurface $right  right
     *
     * @phpstan-param list<list<list<float>>> $first
     * @phpstan-param list<list<list<float>>> $second
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

    /**
     * @param RationalSurface $first     first operand
     * @param RationalSurface $second    second operand
     * @param array           $direction direction
     *
     * @phpstan-param list<Rational> $direction
     */
    private static function differentPlanes(RationalSurface $first, RationalSurface $second, array $direction): bool
    {
        $axis = 0 !== $direction[0][0][0] ? 0 : (0 !== $direction[1][0][0] ? 1 : 2);
        $cuts = [...$first->cuts($second), ...$second->cuts($first)];
        usort($cuts, static fn (array $left, array $right): int => SurfaceCoordinates::compare(
            $left[$axis],
            $right[$axis]
        ));
        // Interior/interior intersections contain an open interval on the planes' intersection line.
        // Isolated cut points touch at least one boundary and are permitted by the approved profile.
        $previous = null;
        foreach ($cuts as $point) {
            if (null !== $previous && 0 !== SurfaceCoordinates::compare($previous[$axis], $point[$axis])) {
                $midpoint = SurfaceCoordinates::interpolate($previous, $point, ExactRationals::number(0.5));
                if (self::forbidden($first->location($midpoint), $second->location($midpoint))) {
                    return true;
                }
            }
            $previous = $point;
        }

        return false;
    }

    /**
     * Forbidden.
     *
     * @param int $first  first operand
     * @param int $second second operand
     */
    private static function forbidden(int $first, int $second): bool
    {
        // Alexandre, 2026-10-23: isolated boundary/interior contacts are permitted; contact segments are not.
        return 0 <= $first && 0 <= $second;
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
