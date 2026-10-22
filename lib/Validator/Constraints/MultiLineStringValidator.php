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
use LongitudeOne\SpatialTypes\Interfaces\MultiLineStringInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Report unavailable validation without claiming geometric validity. */
final class MultiLineStringValidator extends ConstraintValidator
{
    /**
     * @param mixed      $value      Spatial object to validate
     * @param Constraint $constraint Applied constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MultiLineString) {
            throw new UnexpectedTypeException($constraint, MultiLineString::class);
        }

        // Allow optional spatial values; use NotNull separately when a value is required.
        if (null === $value) {
            return;
        }

        if (!$value instanceof MultiLineStringInterface) {
            throw new UnexpectedValueException($value, MultiLineStringInterface::class);
        }

        if (GeometryTypeEnum::MULTILINESTRING !== $value->getType()) {
            throw new UnexpectedValueException($value, GeometryTypeEnum::MULTILINESTRING->value);
        }

        $this->context->buildViolation('MultiLineString validation is not implemented yet.')->addViolation();
        $this->context->getValidator()->inContext($this->context)
            ->validate($value, new Components(groups: $constraint->groups), $this->context->getGroup())
        ;
    }
}
