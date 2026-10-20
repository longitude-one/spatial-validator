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

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Validates the two-dimensional simplicity of a line string. */
final class SimpleLineStringValidator extends ConstraintValidator
{
    /** Determine whether signed areas lie on opposite sides of a segment. */
    private static function oppositeSigns(float $first, float $second): bool
    {
        return 0.0 < $first && 0.0 > $second || 0.0 > $first && 0.0 < $second;
    }

    /**
     * Validate that non-neighbouring segments do not meet.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleLineString) {
            throw new UnexpectedTypeException($constraint, SimpleLineString::class);
        }

        if (!$value instanceof LineStringInterface) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }

        $points = array_values($value->getPoints());
        $segmentCount = count($points) - 1;
        for ($firstSegment = 0; $firstSegment < $segmentCount; ++$firstSegment) {
            for ($secondSegment = $firstSegment + 1; $secondSegment < $segmentCount; ++$secondSegment) {
                if (
                    !$this->segmentsIntersect(
                        $points[$firstSegment],
                        $points[$firstSegment + 1],
                        $points[$secondSegment],
                        $points[$secondSegment + 1]
                    )
                ) {
                    continue;
                }

                if ($this->isPermittedNeighbourIntersection($points, $firstSegment, $secondSegment)) {
                    continue;
                }

                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }
        }
    }

    /**
     * Determine whether two adjacent segments meet only at their common point.
     *
     * @param PointInterface $beforeShared Point before the shared endpoint
     * @param PointInterface $shared       Shared endpoint
     * @param PointInterface $afterShared  Point after the shared endpoint
     */
    private function intersectsOnlyAtSharedEndpoint(
        PointInterface $beforeShared,
        PointInterface $shared,
        PointInterface $afterShared,
    ): bool {
        if (0.0 !== $this->orientation($beforeShared, $shared, $afterShared)) {
            return true;
        }

        return !$this->isOnSegment($shared, $afterShared, $beforeShared)
            && !$this->isOnSegment($beforeShared, $shared, $afterShared);
    }

    /**
     * Determine whether a collinear point lies on a closed segment.
     *
     * @param PointInterface $start Segment start
     * @param PointInterface $end   Segment end
     * @param PointInterface $point Point to locate
     */
    private function isOnSegment(PointInterface $start, PointInterface $end, PointInterface $point): bool
    {
        return min((float) $start->getX(), (float) $end->getX()) <= (float) $point->getX()
            && (float) $point->getX() <= max((float) $start->getX(), (float) $end->getX())
            && min((float) $start->getY(), (float) $end->getY()) <= (float) $point->getY()
            && (float) $point->getY() <= max((float) $start->getY(), (float) $end->getY());
    }

    /**
     * Determine whether two intersecting segments only meet at their shared,
     * permitted endpoint.
     *
     * @param PointInterface[] $points        Line-string points
     * @param int              $firstSegment  Index of the first segment
     * @param int              $secondSegment Index of the second segment
     */
    private function isPermittedNeighbourIntersection(array $points, int $firstSegment, int $secondSegment): bool
    {
        if ($secondSegment === $firstSegment + 1) {
            return $this->intersectsOnlyAtSharedEndpoint(
                $points[$firstSegment],
                $points[$firstSegment + 1],
                $points[$secondSegment + 1]
            );
        }

        $lastSegment = count($points) - 2;
        if (
            0 === $firstSegment && $lastSegment === $secondSegment
            && $this->samePosition($points[0], $points[$lastSegment + 1])
        ) {
            return $this->intersectsOnlyAtSharedEndpoint(
                $points[1],
                $points[0],
                $points[$lastSegment]
            );
        }

        return false;
    }

    /**
     * Return the signed two-dimensional area determinant for three points.
     *
     * Z and M ordinates are intentionally ignored.
     *
     * @param PointInterface $first  First point
     * @param PointInterface $second Second point
     * @param PointInterface $third  Third point
     */
    private function orientation(PointInterface $first, PointInterface $second, PointInterface $third): float
    {
        return ((float) $second->getX() - (float) $first->getX()) * ((float) $third->getY() - (float) $first->getY())
            - ((float) $second->getY() - (float) $first->getY()) * ((float) $third->getX() - (float) $first->getX());
    }

    /**
     * Determine whether two points have the same two-dimensional position.
     *
     * @param PointInterface $first  First point
     * @param PointInterface $second Second point
     */
    private function samePosition(PointInterface $first, PointInterface $second): bool
    {
        return $first->getX() === $second->getX() && $first->getY() === $second->getY();
    }

    /**
     * Determine whether two closed two-dimensional segments intersect.
     *
     * @param PointInterface $firstStart  First segment start
     * @param PointInterface $firstEnd    First segment end
     * @param PointInterface $secondStart Second segment start
     * @param PointInterface $secondEnd   Second segment end
     */
    private function segmentsIntersect(
        PointInterface $firstStart,
        PointInterface $firstEnd,
        PointInterface $secondStart,
        PointInterface $secondEnd,
    ): bool {
        $firstStartSide = $this->orientation($firstStart, $firstEnd, $secondStart);
        $firstEndSide = $this->orientation($firstStart, $firstEnd, $secondEnd);
        $secondStartSide = $this->orientation($secondStart, $secondEnd, $firstStart);
        $secondEndSide = $this->orientation($secondStart, $secondEnd, $firstEnd);

        if (
            self::oppositeSigns($firstStartSide, $firstEndSide)
            && self::oppositeSigns($secondStartSide, $secondEndSide)
        ) {
            return true;
        }

        return $this->touchesSegment($firstStartSide, $firstStart, $firstEnd, $secondStart)
            || $this->touchesSegment($firstEndSide, $firstStart, $firstEnd, $secondEnd)
            || $this->touchesSegment($secondStartSide, $secondStart, $secondEnd, $firstStart)
            || $this->touchesSegment($secondEndSide, $secondStart, $secondEnd, $firstEnd);
    }

    /** Check a collinear endpoint against the closed segment. */
    private function touchesSegment(
        float $orientation,
        PointInterface $start,
        PointInterface $end,
        PointInterface $point,
    ): bool {
        return 0.0 === $orientation && $this->isOnSegment($start, $end, $point);
    }
}
