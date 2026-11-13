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

use LongitudeOne\SpatialValidator\Internal\ExactRationals as Exact;

/**
 * @internal exact rational supports; algebraic points have form base + coefficient * sqrt(radicand)
 *
 * @phpstan-type Magnitude array{int, list<int>}
 * @phpstan-type Rational array{Magnitude, Magnitude}
 * @phpstan-type Vector list<Rational>
 * @phpstan-type Algebraic array{Vector, Vector, Rational}
 * @phpstan-type Arc array{
 *     start: Vector, middle: Vector, end: Vector, center: ?Vector, normal: ?Vector,
 *     radius: Rational, full: bool, segment: bool, raw: list<list<float>>
 * }
 */
final class CircularArcs
{
    /**
     * @param array $points coordinate positions to inspect
     *
     * @phpstan-param list<list<float>> $points
     *
     * @return list<Arc>
     */
    public static function build(array $points): array
    {
        $arcs = [];
        $count = \count($points);
        for ($index = 0; $index + 2 < $count; $index += 2) {
            $arcs[] = self::arc($points[$index], $points[$index + 1], $points[$index + 2]);
        }

        return $arcs;
    }

    /**
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param Vector $first
     * @phpstan-param Vector $second
     *
     * @return Vector
     */
    public static function cross(array $first, array $second): array
    {
        $result = [];
        foreach ([[1, 2], [2, 0], [0, 1]] as [$left, $right]) {
            $result[] = Exact::rationalSubtract(
                Exact::rationalMultiply($first[$left], $second[$right]),
                Exact::rationalMultiply($first[$right], $second[$left])
            );
        }

        return $result;
    }

    /**
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param Vector $first
     * @phpstan-param Vector $second
     *
     * @return Vector
     */
    public static function difference(array $first, array $second): array
    {
        return array_map(Exact::rationalSubtract(...), $first, $second);
    }

    /**
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param Vector $first
     * @phpstan-param Vector $second
     *
     * @return Rational
     */
    public static function dot(array $first, array $second): array
    {
        $result = Exact::number(0);
        foreach ($first as $axis => $ordinate) {
            $result = Exact::rationalAdd($result, Exact::rationalMultiply($ordinate, $second[$axis]));
        }

        return $result;
    }

    /**
     * @param array $point    point
     * @param array $position position
     *
     * @phpstan-param Algebraic $point
     * @phpstan-param Vector $position
     */
    public static function equals(array $point, array $position): bool
    {
        foreach ($position as $axis => $ordinate) {
            $difference = Exact::rationalSubtract($point[0][$axis], $ordinate);
            if (0 !== self::radicalSign($difference, $point[1][$axis], $point[2])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Classify a support point: -1 outside, 0 endpoint, 1 interior.
     *
     * @param array $arc   arc
     * @param array $point point
     *
     * @phpstan-param Arc $arc
     * @phpstan-param Algebraic $point
     */
    public static function location(array $arc, array $point): int
    {
        if (self::equals($point, $arc['start']) || self::equals($point, $arc['end'])) {
            return 0;
        }
        if ($arc['full']) {
            return 1;
        }
        $chord = self::difference($arc['end'], $arc['start']);
        if ($arc['segment']) {
            foreach ($chord as $axis => $ordinate) {
                if (0 === $ordinate[0][0]) {
                    continue;
                }
                $start = self::radicalSign(
                    Exact::rationalSubtract($point[0][$axis], $arc['start'][$axis]),
                    $point[1][$axis],
                    $point[2]
                );
                $end = self::radicalSign(
                    Exact::rationalSubtract($point[0][$axis], $arc['end'][$axis]),
                    $point[1][$axis],
                    $point[2]
                );

                return $start * $end < 0 ? 1 : -1;
            }
        }
        $normal = $arc['normal'] ?? [];
        $middle = self::dot(self::cross($chord, self::difference($arc['middle'], $arc['start'])), $normal);
        $base = self::dot(self::cross($chord, self::difference($point[0], $arc['start'])), $normal);
        $coefficient = self::dot(self::cross($chord, $point[1]), $normal);

        return self::radicalSign($base, $coefficient, $point[2]) === $middle[0][0] ? 1 : -1;
    }

    /**
     * Sign of a + b sqrt(c), without rounding the algebraic point.
     *
     * @param array $first    first operand
     * @param array $second   second operand
     * @param array $radicand radicand
     *
     * @phpstan-param Rational $first
     * @phpstan-param Rational $second
     * @phpstan-param Rational $radicand
     */
    public static function radicalSign(array $first, array $second, array $radicand): int
    {
        $left = $first[0][0];
        $right = $second[0][0] * $radicand[0][0];
        if (0 === $left) {
            return $right;
        }
        if (0 === $right || $left === $right) {
            return $left;
        }
        $squares = Exact::rationalSubtract(
            Exact::rationalMultiply($first, $first),
            Exact::rationalMultiply(Exact::rationalMultiply($second, $second), $radicand)
        );

        return $left * $squares[0][0];
    }

    /**
     * @param array $position position
     *
     * @phpstan-param Vector $position
     *
     * @return Algebraic
     */
    public static function rationalPoint(array $position): array
    {
        return [$position, self::vector([0.0, 0.0, 0.0]), Exact::number(0)];
    }

    /**
     * @param array $vector vector
     * @param array $factor factor
     *
     * @phpstan-param Vector $vector
     * @phpstan-param Rational $factor
     *
     * @return Vector
     */
    public static function scale(array $vector, array $factor): array
    {
        return array_map(static fn (array $value): array => Exact::rationalMultiply($value, $factor), $vector);
    }

    /**
     * @param array $first  first operand
     * @param array $second second operand
     *
     * @phpstan-param Vector $first
     * @phpstan-param Vector $second
     *
     * @return Vector
     */
    public static function sum(array $first, array $second): array
    {
        return array_map(Exact::rationalAdd(...), $first, $second);
    }

    /**
     * @param array $first  first operand
     * @param array $middle middle
     * @param array $last   last
     *
     * @phpstan-param list<float> $first
     * @phpstan-param list<float> $middle
     * @phpstan-param list<float> $last
     *
     * @return Arc
     */
    private static function arc(array $first, array $middle, array $last): array
    {
        $start = self::vector($first);
        $intermediate = self::vector($middle);
        $end = self::vector($last);
        $left = self::difference($intermediate, $start);
        $right = self::difference($end, $start);
        $normal = self::cross($left, $right);
        $full = $first === $last;
        $center = null;
        $radius = Exact::number(0);
        $segment = !$full && 0 === self::dot($normal, $normal)[0][0];
        if ($full) {
            $center = self::scale(self::sum($start, $intermediate), Exact::number(0.5));
            $normal = 2 === \count($first) ? self::vector([0.0, 0.0, 1.0]) : null;
        } elseif (!$segment) {
            $numerator = self::sum(
                self::scale(self::cross($right, $normal), self::dot($left, $left)),
                self::scale(self::cross($normal, $left), self::dot($right, $right))
            );
            $denominator = Exact::rationalMultiply(Exact::number(2), self::dot($normal, $normal));
            $center = self::sum($start, self::scale($numerator, Exact::rationalDivide(Exact::number(1), $denominator)));
        }
        if (null !== $center) {
            $offset = self::difference($start, $center);
            $radius = self::dot($offset, $offset);
        }

        return ['start' => $start, 'middle' => $intermediate, 'end' => $end,
            'center' => $center, 'normal' => $normal, 'radius' => $radius,
            'full' => $full, 'segment' => $segment, 'raw' => [$first, $middle, $last]];
    }

    /**
     * @param array $point point
     *
     * @phpstan-param list<float> $point
     *
     * @return Vector
     */
    private static function vector(array $point): array
    {
        return array_map(Exact::number(...), [$point[0], $point[1], $point[2] ?? 0.0]);
    }
}
