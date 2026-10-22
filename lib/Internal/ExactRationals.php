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
 * @internal reduced exact rational arithmetic over the shared binary-integer engine
 *
 * @phpstan-type Magnitude array{int, list<int>}
 * @phpstan-type Rational array{Magnitude, Magnitude}
 */
final class ExactRationals
{
    /**
     * @return Rational
     */
    public static function number(float $number): array
    {
        return self::reduceRational(RobustPredicates::integer($number), [1, RobustPredicates::shift([1], 1074)]);
    }

    /**
     * @param Rational $first
     * @param Rational $second
     *
     * @return Rational
     */
    public static function rationalAdd(array $first, array $second): array
    {
        if ($first[1] === $second[1]) {
            return self::reduceRational(RobustPredicates::add($first[0], $second[0]), $first[1]);
        }

        return self::reduceRational(RobustPredicates::add(
            RobustPredicates::multiply($first[0], $second[1]),
            RobustPredicates::multiply($second[0], $first[1])
        ), RobustPredicates::multiply($first[1], $second[1]));
    }

    /**
     * @param Rational $first
     * @param Rational $second
     *
     * @return Rational
     */
    public static function rationalDivide(array $first, array $second): array
    {
        return self::reduceRational(
            RobustPredicates::multiply($first[0], $second[1]),
            RobustPredicates::multiply($first[1], $second[0])
        );
    }

    /**
     * @param Rational $first
     * @param Rational $second
     *
     * @return Rational
     */
    public static function rationalMultiply(array $first, array $second): array
    {
        return self::reduceRational(
            RobustPredicates::multiply($first[0], $second[0]),
            RobustPredicates::multiply($first[1], $second[1])
        );
    }

    /**
     * @param Rational $first
     * @param Rational $second
     *
     * @return Rational
     */
    public static function rationalSubtract(array $first, array $second): array
    {
        $second[0][0] *= -1;

        return self::rationalAdd($first, $second);
    }

    /**
     * Divide positive magnitudes known to divide exactly.
     *
     * @param list<int> $value
     * @param list<int> $divisor
     *
     * @return list<int>
     */
    private static function divide(array $value, array $divisor): array
    {
        $shift = max(0, (count($value) - count($divisor)) * 15 + 15);
        $result = array_fill(0, intdiv($shift, 15) + 1, 0);
        for ($bit = $shift; 0 <= $bit; --$bit) {
            $candidate = RobustPredicates::shift($divisor, $bit);
            if (0 <= RobustPredicates::compare($value, $candidate)) {
                $value = RobustPredicates::signed(1, RobustPredicates::difference($value, $candidate))[1];
                $result[intdiv($bit, 15)] |= 1 << ($bit % 15);
            }
        }

        return RobustPredicates::signed(1, array_values($result))[1];
    }

    /**
     * @param list<int> $first
     * @param list<int> $second
     *
     * @return list<int>
     */
    private static function gcd(array $first, array $second): array
    {
        [$first, $left] = self::odd($first);
        [$second, $right] = self::odd($second);
        while ([] !== $second) {
            $comparison = RobustPredicates::compare($first, $second);
            if (0 < $comparison) {
                [$first, $second] = [$second, $first];
            }
            [$second] = self::odd(RobustPredicates::signed(1, RobustPredicates::difference($second, $first))[1]);
        }

        return RobustPredicates::shift($first, min($left, $right));
    }

    /**
     * @param list<int> $digits
     *
     * @return array{list<int>, int}
     */
    private static function odd(array $digits): array
    {
        if ([] === $digits) {
            return [[], 0];
        }
        $bits = 0;
        while (0 === $digits[0]) {
            array_shift($digits);
            $bits += 15;
        }
        $low = $digits[0];
        $shift = 0;
        while (0 === $low % 2) {
            $low = intdiv($low, 2);
            ++$shift;
        }
        $carry = 0;
        for ($index = count($digits) - 1; 0 <= $index; --$index) {
            $total = $digits[$index] + $carry * RobustPredicates::BASE;
            $digits[$index] = $total >> $shift;
            $carry = $total % (1 << $shift);
        }

        return [RobustPredicates::signed(1, array_values($digits))[1], $bits + $shift];
    }

    /**
     * @param Magnitude $numerator
     * @param Magnitude $denominator
     *
     * @return Rational
     */
    private static function reduceRational(array $numerator, array $denominator): array
    {
        if (0 === $numerator[0]) {
            return [[0, []], [1, [1]]];
        }
        $common = self::gcd($numerator[1], $denominator[1]);

        return [[$numerator[0] * $denominator[0], self::divide($numerator[1], $common)],
            [1, self::divide($denominator[1], $common)]];
    }
}
