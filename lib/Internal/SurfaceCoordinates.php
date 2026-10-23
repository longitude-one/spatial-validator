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
 * @internal exact rational vector operations; never rounds intersection positions to floats
 *
 * @phpstan-import-type Rational from ExactRationals
 */
final class SurfaceCoordinates
{
    /** @param Rational $first
     * @param Rational $second
     */
    public static function compare(array $first, array $second): int
    {
        return ExactRationals::rationalSubtract($first, $second)[0][0];
    }

    /** @param list<Rational> $first
     * @param list<Rational> $second
     *
     * @return list<Rational>
     */
    public static function cross(array $first, array $second): array
    {
        $result = [];
        foreach ([[1, 2], [2, 0], [0, 1]] as [$left, $right]) {
            $result[] = ExactRationals::rationalSubtract(
                ExactRationals::rationalMultiply($first[$left], $second[$right]),
                ExactRationals::rationalMultiply($first[$right], $second[$left])
            );
        }

        return $result;
    }

    /** @param list<Rational> $first
     * @param list<Rational> $second
     *
     * @return Rational
     */
    public static function dot(array $first, array $second): array
    {
        return array_reduce(
            array_map(ExactRationals::rationalMultiply(...), $first, $second),
            ExactRationals::rationalAdd(...),
            ExactRationals::number(0)
        );
    }

    /** @param list<Rational> $first
     * @param list<Rational> $second
     * @param Rational       $parameter
     *
     * @return list<Rational>
     */
    public static function interpolate(array $first, array $second, array $parameter): array
    {
        $result = [];
        foreach (self::subtract($second, $first) as $axis => $difference) {
            $result[] = ExactRationals::rationalAdd(
                $first[$axis],
                ExactRationals::rationalMultiply($parameter, $difference)
            );
        }

        return $result;
    }

    /** @param list<float> $point
     * @return list<Rational>
     */
    public static function point(array $point): array
    {
        return array_map(ExactRationals::number(...), $point);
    }

    /** @param list<Rational> $first
     * @param list<Rational> $second
     *
     * @return list<Rational>
     */
    public static function subtract(array $first, array $second): array
    {
        return array_map(ExactRationals::rationalSubtract(...), $first, $second);
    }
}
