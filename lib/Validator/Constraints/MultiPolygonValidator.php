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

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\MultiPolygonInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Report unavailable validation without claiming geometric validity. */
final class MultiPolygonValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MultiPolygon) {
            throw new UnexpectedTypeException($constraint, MultiPolygon::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof MultiPolygonInterface) {
            throw new UnexpectedValueException($value, MultiPolygonInterface::class);
        }

        if (GeometryTypeEnum::MULTIPOLYGON !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::MULTIPOLYGON->value);
        }

        $this->context->buildViolation('MultiPolygon validation is not implemented yet.')->addViolation();
    }
}
