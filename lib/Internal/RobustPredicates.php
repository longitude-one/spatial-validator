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

use InvalidArgumentException as InvalidCoordinate;

/**
 * @phpstan-type Magnitude array{int, list<int>}
 * @phpstan-type Rational array{Magnitude, Magnitude}
 *
 * @internal Exact signs for finite IEEE-754 inputs, without a geometric tolerance.
 *
 * Every double is an integer multiple of 2^-1074. Signed magnitudes store that
 * integer in little-endian base 2^15 limbs. Products of limbs and carries fit
 * in PHP's integers, including 32-bit builds. Only signs are returned; coordinates
 * are never rounded, snapped or reconstructed from this representation.
 */
final class RobustPredicates
{
    private const int BASE = 32768;

    /**
     * @param list<float> $first
     * @param list<float> $second
     * @param list<float> $third
     * @param list<float> $fourth
     */
    public static function coplanar(array $first, array $second, array $third, array $fourth): bool
    {
        $vectors = [];
        foreach ([$second, $third, $fourth] as $point) {
            $vector = [];
            foreach ([0, 1, 2] as $axis) {
                $vector[] = self::subtract(self::integer($point[$axis]), self::integer($first[$axis]));
            }
            $vectors[] = $vector;
        }
        $determinant = [0, []];
        foreach ([[0, 1, 2], [1, 2, 0], [2, 0, 1]] as [$axis, $next, $last]) {
            $cross = self::subtract(
                self::multiply($vectors[0][$next], $vectors[1][$last]),
                self::multiply($vectors[0][$last], $vectors[1][$next])
            );
            $determinant = self::add($determinant, self::multiply($cross, $vectors[2][$axis]));
        }

        return 0 === $determinant[0];
    }

    /**
     * @return Rational
     */
    public static function number(float $number): array
    {
        return self::reduceRational(self::integer($number), [1, self::shift([1], 1074)]);
    }

    /**
     * @param list<float>     $first
     * @param list<float>     $second
     * @param list<float>     $third
     * @param array{int, int} $axes
     */
    public static function orientation(array $first, array $second, array $third, array $axes = [0, 1]): int
    {
        [$horizontal, $vertical] = $axes;
        $firstHorizontal = self::subtract(self::integer($second[$horizontal]), self::integer($first[$horizontal]));
        $firstVertical = self::subtract(self::integer($second[$vertical]), self::integer($first[$vertical]));
        $secondHorizontal = self::subtract(self::integer($third[$horizontal]), self::integer($first[$horizontal]));
        $secondVertical = self::subtract(self::integer($third[$vertical]), self::integer($first[$vertical]));

        $determinant = self::subtract(
            self::multiply($firstHorizontal, $secondVertical),
            self::multiply($firstVertical, $secondHorizontal)
        );

        return $determinant[0];
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
            return self::reduceRational(self::add($first[0], $second[0]), $first[1]);
        }

        return self::reduceRational(self::add(
            self::multiply($first[0], $second[1]),
            self::multiply($second[0], $first[1])
        ), self::multiply($first[1], $second[1]));
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
            self::multiply($first[0], $second[1]),
            self::multiply($first[1], $second[0])
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
            self::multiply($first[0], $second[0]),
            self::multiply($first[1], $second[1])
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
     * @param array{int, list<int>} $first
     * @param array{int, list<int>} $second
     *
     * @return array{int, list<int>}
     */
    private static function add(array $first, array $second): array
    {
        if (0 === $first[0]) {
            return $second;
        }
        if (0 === $second[0]) {
            return $first;
        }
        if ($first[0] === $second[0]) {
            return self::signed($first[0], self::sum($first[1], $second[1]));
        }
        $comparison = self::compare($first[1], $second[1]);
        if (0 > $comparison) {
            return self::signed($second[0], self::difference($second[1], $first[1]));
        }

        return self::signed($first[0], self::difference($first[1], $second[1]));
    }

    /**
     * @param list<int> $first
     * @param list<int> $second
     */
    private static function compare(array $first, array $second): int
    {
        $comparison = count($first) <=> count($second);
        if (0 !== $comparison) {
            return $comparison;
        }
        for ($index = count($first) - 1; 0 <= $index; --$index) {
            if ($first[$index] !== $second[$index]) {
                return $first[$index] <=> $second[$index];
            }
        }

        return 0;
    }

    /**
     * @param list<int> $first  Greater or equal magnitude
     * @param list<int> $second
     *
     * @return list<int>
     */
    private static function difference(array $first, array $second): array
    {
        $result = [];
        $borrow = 0;
        foreach ($first as $index => $digit) {
            $difference = $digit - ($second[$index] ?? 0) - $borrow;
            $borrow = 0 > $difference ? 1 : 0;
            $result[] = $difference + $borrow * self::BASE;
        }

        return $result;
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
            $candidate = self::shift($divisor, $bit);
            if (0 <= self::compare($value, $candidate)) {
                $value = self::signed(1, self::difference($value, $candidate))[1];
                $result[intdiv($bit, 15)] |= 1 << ($bit % 15);
            }
        }

        return self::signed(1, array_values($result))[1];
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
            $comparison = self::compare($first, $second);
            if (0 < $comparison) {
                [$first, $second] = [$second, $first];
            }
            [$second] = self::odd(self::signed(1, self::difference($second, $first))[1]);
        }

        return self::shift($first, min($left, $right));
    }

    /** @return array{int, list<int>} */
    private static function integer(float $value): array
    {
        if (!is_finite($value)) {
            throw new InvalidCoordinate('Robust predicates require finite coordinates.');
        }

        /** @var array{1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int} $bytes */
        $bytes = unpack('C8', pack('E', $value));
        $exponent = (($bytes[1] & 127) << 4) | ($bytes[2] >> 4);
        $leading = ($bytes[2] & 15) + (0 === $exponent ? 0 : 16);
        $digits = [$leading];
        foreach (array_slice($bytes, 2) as $byte) {
            $carry = $byte;
            foreach ($digits as $index => $digit) {
                $total = $digit * 256 + $carry;
                $digits[$index] = $total % self::BASE;
                $carry = intdiv($total, self::BASE);
            }
            if (0 !== $carry) {
                $digits[] = $carry;
            }
        }

        return self::signed(0 === ($bytes[1] & 128) ? 1 : -1, self::shift($digits, max(0, $exponent - 1)));
    }

    /**
     * @param array{int, list<int>} $first
     * @param array{int, list<int>} $second
     *
     * @return array{int, list<int>}
     */
    private static function multiply(array $first, array $second): array
    {
        $result = array_fill(0, count($first[1]) + count($second[1]), 0);
        foreach ($first[1] as $left => $digit) {
            if (0 === $digit) {
                continue;
            }
            $carry = 0;
            foreach ($second[1] as $right => $other) {
                $offset = $left + $right;
                $product = $result[$offset] + $digit * $other + $carry;
                $result[$offset] = $product % self::BASE;
                $carry = intdiv($product, self::BASE);
            }
            $result[$left + count($second[1])] = $carry;
        }

        return self::signed($first[0] * $second[0], array_values($result));
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
            $total = $digits[$index] + $carry * self::BASE;
            $digits[$index] = $total >> $shift;
            $carry = $total % (1 << $shift);
        }

        return [self::signed(1, array_values($digits))[1], $bits + $shift];
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

    /**
     * @param list<int> $digits
     *
     * @return list<int>
     */
    private static function shift(array $digits, int $bits): array
    {
        $carry = 0;
        foreach ($digits as $index => $digit) {
            $total = ($digit << ($bits % 15)) + $carry;
            $digits[$index] = $total % self::BASE;
            $carry = intdiv($total, self::BASE);
        }
        if (0 !== $carry) {
            $digits[] = $carry;
        }

        return array_merge(array_fill(0, intdiv($bits, 15), 0), $digits);
    }

    /**
     * @param list<int> $digits
     *
     * @return array{int, list<int>}
     */
    private static function signed(int $sign, array $digits): array
    {
        while ([] !== $digits && 0 === end($digits)) {
            array_pop($digits);
        }

        return [[] === $digits ? 0 : $sign, $digits];
    }

    /**
     * @param array{int, list<int>} $first
     * @param array{int, list<int>} $second
     *
     * @return array{int, list<int>}
     */
    private static function subtract(array $first, array $second): array
    {
        return self::add($first, [-$second[0], $second[1]]);
    }

    /**
     * @param list<int> $first
     * @param list<int> $second
     *
     * @return list<int>
     */
    private static function sum(array $first, array $second): array
    {
        $result = [];
        $carry = 0;
        $length = max(count($first), count($second));
        for ($index = 0; $index < $length; ++$index) {
            $total = ($first[$index] ?? 0) + ($second[$index] ?? 0) + $carry;
            $result[] = $total % self::BASE;
            $carry = intdiv($total, self::BASE);
        }
        $result[] = $carry;

        return $result;
    }
}
