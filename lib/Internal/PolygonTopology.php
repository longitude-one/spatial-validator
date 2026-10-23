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

use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\Polygon;

/** @internal Complete inter-ring checks after successful structural Geometry validation. */
final class PolygonTopology
{
    /**
     * @param list<list<list<float>>> $rings
     *
     * @return list<array{int, string}>
     */
    public static function errors(array $rings): array
    {
        $errors = self::closingEdges($rings);
        if ([] !== $errors) {
            return $errors;
        }
        $contacts = new PolygonContacts();
        $count = count($rings);
        for ($second = 1; $second < $count; ++$second) {
            if (PlanarRing::direction($rings[0]) === PlanarRing::direction($rings[$second])) {
                $errors[] = [$second, Polygon::ORIENTATION_ERROR];
            }
            for ($first = 0; $first < $second; ++$first) {
                $points = PolygonRingRelations::contacts($rings[$first], $rings[$second]);
                if (null === $points) {
                    $errors[] = [$second, Polygon::INTERSECTION_ERROR];

                    continue;
                }
                $location = self::relationship($rings[$first], $rings[$second], $points, 0 === $first);
                if (null !== $location) {
                    $errors[] = [$second, $location];
                }
                if (!$contacts->add($first, $second, $points)) {
                    $errors[] = [$second, Polygon::DISCONNECTED_ERROR];
                }
            }
        }

        return $errors;
    }

    /** @return list<list<list<float>>> */
    public static function project(PolygonInterface $polygon): array
    {
        $rings = [];
        foreach (array_values($polygon->getRings()) as $ring) {
            $positions = [];
            foreach ($ring->getPoints() as $point) {
                $position = PointCoordinates::position($point);
                if (null === $position) {
                    return [];
                }
                $positions[] = $position;
            }
            $rings[] = $positions;
        }
        $basis = PolygonPlane::basis($rings[0] ?? []);
        if (null === $basis) {
            return [];
        }
        $axes = [0, 1];
        if (0 === RobustPredicates::orientation($basis[0], $basis[1], $basis[2])) {
            $axes = 0 !== RobustPredicates::orientation($basis[0], $basis[1], $basis[2], [0, 2]) ? [0, 2] : [1, 2];
        }

        return array_map(static fn (array $ring): array => array_map(
            static fn (array $point): array => [$point[$axes[0]], $point[$axes[1]]],
            $ring
        ), $rings);
    }

    /**
     * Q02 closure retains both supplied positions: inspect the resulting closing interval too.
     *
     * @param list<list<list<float>>> $rings
     *
     * @return list<array{int, string}>
     */
    private static function closingEdges(array $rings): array
    {
        $errors = [];
        foreach ($rings as $index => $ring) {
            if ($ring[0] === $ring[count($ring) - 1]) {
                continue;
            }
            $ring[] = $ring[0];
            if (!SegmentIntersections::simple($ring)) {
                $errors[] = [$index, Polygon::INTERSECTION_ERROR];
            }
        }

        return $errors;
    }

    /**
     * @param list<list<float>> $first
     * @param list<list<float>> $second
     * @param list<list<float>> $contacts
     */
    private static function relationship(array $first, array $second, array $contacts, bool $exterior): ?string
    {
        $secondLocation = PolygonRingRelations::locations($second, $first, $contacts);
        if ($exterior) {
            return 1 === $secondLocation ? null : Polygon::OUTSIDE_ERROR;
        }
        $firstLocation = PolygonRingRelations::locations($first, $second, $contacts);

        return 2 === $secondLocation && 2 === $firstLocation ? null : Polygon::NESTED_ERROR;
    }
}
