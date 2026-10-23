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
 * @internal a validated planar filled surface, including its holes, in exact rational coordinates
 *
 * @phpstan-import-type Rational from ExactRationals
 */
final class RationalSurface
{
    /** @var list<Rational> */
    public readonly array $normal;

    /** @var list<Rational> */
    public readonly array $origin;

    /** @var list<list<list<Rational>>> */
    public readonly array $rings;

    /** @var array{int, int} */
    private readonly array $axes;

    /** @param list<list<list<float>>> $rings
     * @param array{list<float>, list<float>, list<float>} $basis
     */
    public function __construct(array $rings, array $basis)
    {
        $this->rings = array_map(
            static fn (array $ring): array => array_map(SurfaceCoordinates::point(...), $ring),
            $rings
        );
        [$this->origin, $second, $third] = array_map(SurfaceCoordinates::point(...), $basis);
        $this->normal = SurfaceCoordinates::cross(
            SurfaceCoordinates::subtract($second, $this->origin),
            SurfaceCoordinates::subtract($third, $this->origin)
        );
        $this->axes = 0 !== $this->normal[2][0][0] ? [0, 1]
            : (0 !== $this->normal[1][0][0] ? [0, 2] : [1, 2]);
    }

    /** @param list<list<Rational>> $ring
     * @return list<array{list<Rational>, list<Rational>}>
     */
    private static function edges(array $ring): array
    {
        $edges = [];
        $previous = $ring[count($ring) - 1];
        foreach ($ring as $point) {
            $edges[] = [$previous, $point];
            $previous = $point;
        }

        return $edges;
    }

    /** Boundary intersections with another plane, including endpoints of intervals lying in it.
     * @return list<list<Rational>>
     */
    public function cuts(self $other): array
    {
        $cuts = [];
        foreach ($this->rings as $ring) {
            foreach (self::edges($ring) as [$first, $second]) {
                $start = $other->side($first);
                $end = $other->side($second);
                if (0 === $start[0][0]) {
                    $cuts[] = $first;
                }
                if (0 > $start[0][0] * $end[0][0]) {
                    $parameter = ExactRationals::rationalDivide($start, ExactRationals::rationalSubtract($start, $end));
                    $cuts[] = SurfaceCoordinates::interpolate($first, $second, $parameter);
                }
            }
        }

        return $cuts;
    }

    /** Inside=1, boundary=0, outside=-1. Point is known to lie in this plane.
     * @param list<Rational> $point
     */
    public function location(array $point): int
    {
        $outside = false;
        foreach ($this->rings as $index => $ring) {
            $location = $this->ringLocation($point, $ring);
            if (0 === $location) {
                return 0;
            }
            $outside = $outside || (0 === $index ? -1 === $location : 1 === $location);
        }

        return $outside ? -1 : 1;
    }

    /** @param list<Rational> $point
     * @return Rational
     */
    public function side(array $point): array
    {
        return SurfaceCoordinates::dot($this->normal, SurfaceCoordinates::subtract($point, $this->origin));
    }

    /** @param list<Rational> $start
     * @param list<Rational> $end
     */
    private function onSegment(int $turn, array $start, array $end): bool
    {
        [$horizontal, $vertical] = $this->axes;

        return 0 === $turn && 0 >= $start[$horizontal][0][0] * $end[$horizontal][0][0]
            && 0 >= $start[$vertical][0][0] * $end[$vertical][0][0];
    }

    /** @param list<Rational> $point
     * @param list<list<Rational>> $ring
     */
    private function ringLocation(array $point, array $ring): int
    {
        [$horizontal, $vertical] = $this->axes;
        $winding = 0;
        foreach (self::edges($ring) as [$first, $second]) {
            $start = SurfaceCoordinates::subtract($first, $point);
            $end = SurfaceCoordinates::subtract($second, $point);
            $turn = ExactRationals::rationalSubtract(
                ExactRationals::rationalMultiply($start[$horizontal], $end[$vertical]),
                ExactRationals::rationalMultiply($start[$vertical], $end[$horizontal])
            )[0][0];
            if ($this->onSegment($turn, $start, $end)) {
                return 0;
            }
            if (0 >= $start[$vertical][0][0] && 0 < $end[$vertical][0][0] && 0 < $turn) {
                ++$winding;
            }
            if (0 < $start[$vertical][0][0] && 0 >= $end[$vertical][0][0] && 0 > $turn) {
                --$winding;
            }
        }

        return 0 === $winding ? -1 : 1;
    }
}
