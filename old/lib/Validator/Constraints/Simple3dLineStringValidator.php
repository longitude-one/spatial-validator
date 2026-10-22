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

/** Validates the three-dimensional simplicity of a line string. */
final class Simple3dLineStringValidator extends ConstraintValidator
{
    /**
     * Validate that non-neighbouring segments do not meet in XYZ space.
     *
     * @param mixed      $value      Value to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleThreeDimensionalLineString) {
            throw new UnexpectedTypeException($constraint, SimpleThreeDimensionalLineString::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof LineStringInterface) {
            throw new UnexpectedValueException($value, LineStringInterface::class);
        }

        if (!$value->hasZ()) {
            throw new UnexpectedValueException($value, 'a line string with a Z ordinate');
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
                    || $this->isPermittedNeighbourIntersection($points, $firstSegment, $secondSegment)
                ) {
                    continue;
                }

                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }
        }
    }

    /**
     * Determine whether two collinear XYZ segments share at least one point.
     *
     * @param PointInterface $firstStart  First segment start
     * @param PointInterface $firstEnd    First segment end
     * @param PointInterface $secondStart Second segment start
     * @param PointInterface $secondEnd   Second segment end
     */
    private function collinearSegmentsOverlap(
        PointInterface $firstStart,
        PointInterface $firstEnd,
        PointInterface $secondStart,
        PointInterface $secondEnd,
    ): bool {
        $direction = $this->subtract($firstEnd, $firstStart);
        $lengthSquared = $this->dot($direction, $direction);
        if (0.0 === $lengthSquared) {
            return $this->samePosition($firstStart, $secondStart) || $this->samePosition($firstStart, $secondEnd);
        }

        $secondStartParameter = $this->dot($this->subtract($secondStart, $firstStart), $direction) / $lengthSquared;
        $secondEndParameter = $this->dot($this->subtract($secondEnd, $firstStart), $direction) / $lengthSquared;

        return max(
            0.0,
            min(
                $secondStartParameter,
                $secondEndParameter
            )
        ) <= min(
            1.0,
            max(
                $secondStartParameter,
                $secondEndParameter
            )
        );
    }

    /**
     * @param array{float, float, float} $first  First vector
     * @param array{float, float, float} $second Second vector
     *
     * @return array{float, float, float}
     */
    private function cross(array $first, array $second): array
    {
        return [$first[1] * $second[2] - $first[2] * $second[1],
            $first[2] * $second[0] - $first[0] * $second[2],
            $first[0] * $second[1] - $first[1] * $second[0]];
    }

    /**
     * @param array{float, float, float} $first  First vector
     * @param array{float, float, float} $second Second vector
     */
    private function dot(array $first, array $second): float
    {
        return $first[0] * $second[0] + $first[1] * $second[1] + $first[2] * $second[2];
    }

    /**
     * Determine whether collinear adjacent segments overlap beyond their common endpoint.
     *
     * @param PointInterface $beforeShared Point before the shared endpoint
     * @param PointInterface $shared       Shared endpoint
     * @param PointInterface $afterShared  Point after the shared endpoint
     */
    private function hasOverlapBeyondSharedEndpoint(
        PointInterface $beforeShared,
        PointInterface $shared,
        PointInterface $afterShared,
    ): bool {
        $direction = $this->subtract($shared, $beforeShared);
        $after = $this->subtract($afterShared, $beforeShared);
        $lengthSquared = $this->dot($direction, $direction);
        if (0.0 === $lengthSquared || !$this->isParallel($direction, $after)) {
            return false;
        }

        return $this->dot($after, $direction) / $lengthSquared < 1.0;
    }

    /**
     * @param array{float, float, float} $first  First vector
     * @param array{float, float, float} $second Second vector
     */
    private function isParallel(array $first, array $second): bool
    {
        return [0.0, 0.0, 0.0] === $this->cross($first, $second);
    }

    /**
     * @param PointInterface[] $points        Line-string points
     * @param int              $firstSegment  Index of the first segment
     * @param int              $secondSegment Index of the second segment
     */
    private function isPermittedNeighbourIntersection(array $points, int $firstSegment, int $secondSegment): bool
    {
        if ($secondSegment === $firstSegment + 1) {
            return !$this->hasOverlapBeyondSharedEndpoint(
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
            return !$this->hasOverlapBeyondSharedEndpoint($points[1], $points[0], $points[$lastSegment]);
        }

        return false;
    }

    /**
     * Determine whether two points have the same XYZ position; M is ignored.
     *
     * @param PointInterface $first  First point
     * @param PointInterface $second Second point
     */
    private function samePosition(PointInterface $first, PointInterface $second): bool
    {
        return $first->getX() === $second->getX()
            && $first->getY() === $second->getY()
            && $first->getZ() === $second->getZ();
    }

    /**
     * Determine whether two closed XYZ segments intersect.
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
        $firstDirection = $this->subtract($firstEnd, $firstStart);
        $secondDirection = $this->subtract($secondEnd, $secondStart);
        $betweenStarts = $this->subtract($secondStart, $firstStart);
        $crossDirections = $this->cross($firstDirection, $secondDirection);
        $denominator = $this->dot($crossDirections, $crossDirections);

        if (0.0 === $denominator) {
            return $this->isParallel(
                $betweenStarts,
                $firstDirection
            ) && $this->collinearSegmentsOverlap(
                $firstStart,
                $firstEnd,
                $secondStart,
                $secondEnd
            );
        }

        if (0.0 !== $this->dot($betweenStarts, $crossDirections)) {
            return false;
        }

        $firstParameter = $this->dot($this->cross($betweenStarts, $secondDirection), $crossDirections) / $denominator;
        $secondParameter = $this->dot($this->cross($betweenStarts, $firstDirection), $crossDirections) / $denominator;

        return 0.0 <= $firstParameter && 1.0 >= $firstParameter && 0.0 <= $secondParameter && 1.0 >= $secondParameter;
    }

    /**
     * @param PointInterface $minuend    Point from which to subtract
     * @param PointInterface $subtrahend Point to subtract
     *
     * @return array{float, float, float}
     */
    private function subtract(PointInterface $minuend, PointInterface $subtrahend): array
    {
        return [(float) $minuend->getX() - (float) $subtrahend->getX(),
            (float) $minuend->getY() - (float) $subtrahend->getY(),
            (float) $minuend->getZ() - (float) $subtrahend->getZ()];
    }
}
