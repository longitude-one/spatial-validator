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

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;

/** @internal Shared coordinate access and finite spatial comparison. */
final class PointCoordinates
{
    /** Compare finite coordinates without changing input or applying a proximity tolerance. */
    public static function equal(float $first, float $second): bool
    {
        return is_finite($first) && is_finite($second)
            && abs($first - $second) <= PHP_FLOAT_EPSILON * max(1.0, abs($first), abs($second));
    }

    public static function isEmptyPoint(mixed $point): bool
    {
        return $point instanceof PointInterface && $point->isEmpty();
    }

    /** @return array<string, null|float|int> */
    public static function ordinates(PointInterface $point): array
    {
        $ordinates = ['X' => $point->getX(), 'Y' => $point->getY()];
        if ($point->getDimension()->hasZ()) {
            $ordinates['Z'] = $point->getZ();
        }
        if ($point->getDimension()->hasM()) {
            $ordinates['M'] = $point->getM();
        }

        return $ordinates;
    }

    /** @return null|list<float> */
    public static function position(mixed $point): ?array
    {
        if (!$point instanceof PointInterface || GeometryTypeEnum::POINT !== $point->getType()) {
            return null;
        }
        if ($point->isEmpty()) {
            return null;
        }
        foreach (self::ordinates($point) as $ordinate) {
            if (null === $ordinate || !is_finite((float) $ordinate)) {
                return null;
            }
        }
        $position = [(float) $point->getX(), (float) $point->getY()];
        if ($point->getDimension()->hasZ()) {
            $position[] = (float) $point->getZ();
        }

        return $position;
    }

    /**
     * @param list<float> $first
     * @param list<float> $second
     */
    public static function same(array $first, array $second): bool
    {
        if (count($first) !== count($second)) {
            return false;
        }
        foreach ($first as $index => $ordinate) {
            if (!self::equal($ordinate, $second[$index])) {
                return false;
            }
        }

        return true;
    }
}
