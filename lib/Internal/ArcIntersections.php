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

use LongitudeOne\SpatialValidator\Internal\RobustPredicates as Exact;

/**
 * @internal exact support intersections followed by actual-arc membership tests
 *
 * @phpstan-import-type Rational from CircularArcs
 * @phpstan-import-type Vector from CircularArcs
 * @phpstan-import-type Algebraic from CircularArcs
 * @phpstan-import-type Arc from CircularArcs
 */
final class ArcIntersections
{
    /**
     * @param Arc          $first
     * @param Arc          $second
     * @param list<Vector> $permitted
     */
    public static function conflict(array $first, array $second, array $permitted): ?bool
    {
        if (($first['full'] && null === $first['normal']) || ($second['full'] && null === $second['normal'])) {
            return null;
        }

        return self::knownConflict($first, $second, $permitted);
    }

    /**
     * @param Arc $first
     * @param Arc $second
     *
     * @return list<Algebraic>
     */
    private static function candidates(array $first, array $second): array
    {
        if ($first['segment']) {
            return self::segmentCircle($first, $second);
        }
        if ($second['segment']) {
            return self::segmentCircle($second, $first);
        }
        $points = self::circles($first, $second);
        if (null !== $points) {
            return $points;
        }

        return array_values(array_map(
            CircularArcs::rationalPoint(...),
            [$first['start'], $first['middle'], $first['end'],
                $second['start'], $second['middle'], $second['end']]
        ));
    }

    /**
     * @param Arc $first
     * @param Arc $second
     *
     * @return null|list<Algebraic>
     */
    private static function circles(array $first, array $second): ?array
    {
        $normal = $first['normal'] ?? [];
        $other = $second['normal'] ?? [];
        $direction = CircularArcs::cross($normal, $other);
        $constant = CircularArcs::dot($normal, $first['start']);
        $otherConstant = CircularArcs::dot($other, $second['start']);
        if (0 === CircularArcs::dot($direction, $direction)[0][0]) {
            $planeOffset = Exact::rationalSubtract(CircularArcs::dot($normal, $second['start']), $constant);
            if (0 !== $planeOffset[0][0]) {
                return [];
            }
            $center = $first['center'] ?? [];
            $otherCenter = $second['center'] ?? [];
            $other = CircularArcs::scale(CircularArcs::difference($otherCenter, $center), Exact::number(2));
            if (0 === CircularArcs::dot($other, $other)[0][0]) {
                return $first['radius'] === $second['radius'] ? null : [];
            }
            $otherConstant = Exact::rationalSubtract(
                Exact::rationalSubtract(CircularArcs::dot($otherCenter, $otherCenter), $second['radius']),
                Exact::rationalSubtract(CircularArcs::dot($center, $center), $first['radius'])
            );
            $direction = CircularArcs::cross($normal, $other);
        }
        $numerator = CircularArcs::sum(
            CircularArcs::scale(CircularArcs::cross($other, $direction), $constant),
            CircularArcs::scale(CircularArcs::cross($direction, $normal), $otherConstant)
        );
        $position = CircularArcs::scale(
            $numerator,
            Exact::rationalDivide(Exact::number(1), CircularArcs::dot($direction, $direction))
        );
        $points = self::lineCircle($position, $direction, $first);

        return array_values(array_filter($points, static fn (array $point): bool => self::onCircle($point, $second)));
    }

    /**
     * @param Arc             $first
     * @param Arc             $second
     * @param list<Algebraic> $points
     * @param list<Vector>    $permitted
     */
    private static function contacts(array $first, array $second, array $points, array $permitted): bool
    {
        foreach ($points as $point) {
            if (0 > CircularArcs::location($first, $point) || 0 > CircularArcs::location($second, $point)) {
                continue;
            }
            foreach ($permitted as $position) {
                if (CircularArcs::equals($point, $position)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param Arc          $first
     * @param Arc          $second
     * @param list<Vector> $permitted
     */
    private static function knownConflict(array $first, array $second, array $permitted): bool
    {
        if ($first['segment'] && $second['segment']) {
            $kind = SegmentIntersections::kind(
                $first['raw'][0],
                $first['raw'][2],
                $second['raw'][0],
                $second['raw'][2]
            );

            return 0 !== $kind && (2 === $kind || [] === $permitted);
        }
        if (!$first['segment'] && !$second['segment'] && null === self::circles($first, $second)) {
            return self::overlap($first, $second)
                || self::contacts($first, $second, self::candidates($first, $second), $permitted);
        }

        return self::contacts($first, $second, self::candidates($first, $second), $permitted);
    }

    /**
     * @param Vector $position
     * @param Vector $direction
     * @param Arc    $circle
     *
     * @return list<Algebraic>
     */
    private static function lineCircle(array $position, array $direction, array $circle): array
    {
        $offset = CircularArcs::difference($position, $circle['center'] ?? []);
        $quadratic = CircularArcs::dot($direction, $direction);
        $linear = Exact::rationalMultiply(Exact::number(2), CircularArcs::dot($offset, $direction));
        $constant = Exact::rationalSubtract(CircularArcs::dot($offset, $offset), $circle['radius']);
        $discriminant = Exact::rationalSubtract(
            Exact::rationalMultiply($linear, $linear),
            Exact::rationalMultiply(Exact::number(4), Exact::rationalMultiply($quadratic, $constant))
        );
        if (0 > $discriminant[0][0]) {
            return [];
        }
        $denominator = Exact::rationalMultiply(Exact::number(2), $quadratic);
        $base = CircularArcs::difference(
            $position,
            CircularArcs::scale($direction, Exact::rationalDivide($linear, $denominator))
        );
        $coefficient = CircularArcs::scale($direction, Exact::rationalDivide(Exact::number(1), $denominator));
        $points = [[$base, $coefficient, $discriminant]];
        if (0 !== $discriminant[0][0]) {
            $points[] = [$base, CircularArcs::scale($coefficient, Exact::number(-1)), $discriminant];
        }

        return $points;
    }

    /**
     * @param Algebraic $point
     * @param Arc       $circle
     */
    private static function onCircle(array $point, array $circle): bool
    {
        // Candidates already satisfy both support planes by construction.
        $offset = CircularArcs::difference($point[0], $circle['center'] ?? []);
        $base = Exact::rationalSubtract(Exact::rationalAdd(
            CircularArcs::dot($offset, $offset),
            Exact::rationalMultiply(CircularArcs::dot($point[1], $point[1]), $point[2])
        ), $circle['radius']);
        $coefficient = Exact::rationalMultiply(Exact::number(2), CircularArcs::dot($offset, $point[1]));

        return 0 === CircularArcs::radicalSign($base, $coefficient, $point[2]);
    }

    /**
     * @param Arc $first
     * @param Arc $second
     */
    private static function overlap(array $first, array $second): bool
    {
        if ($first['full'] || $second['full']) {
            return true;
        }
        foreach ([[$first, $second], [$second, $first]] as [$source, $target]) {
            foreach (['start', 'middle', 'end'] as $key) {
                if (1 === CircularArcs::location($target, CircularArcs::rationalPoint($source[$key]))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param Arc $segment
     * @param Arc $circle
     *
     * @return list<Algebraic>
     */
    private static function segmentCircle(array $segment, array $circle): array
    {
        $direction = CircularArcs::difference($segment['end'], $segment['start']);
        $normal = $circle['normal'] ?? [];
        $denominator = CircularArcs::dot($normal, $direction);
        $numerator = CircularArcs::dot($normal, CircularArcs::difference($circle['start'], $segment['start']));
        if (0 !== $denominator[0][0]) {
            $position = CircularArcs::sum(
                $segment['start'],
                CircularArcs::scale($direction, Exact::rationalDivide($numerator, $denominator))
            );
            $point = CircularArcs::rationalPoint($position);

            return self::onCircle($point, $circle) ? [$point] : [];
        }
        if (0 !== $numerator[0][0]) {
            return [];
        }

        return self::lineCircle($segment['start'], $direction, $circle);
    }
}
