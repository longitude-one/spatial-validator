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

use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/** Check Geography longitude and latitude bounds in degrees. */
final class GeographyBoundsValidator extends ConstraintValidator
{
    /** Validate a point independently of model constructor guarantees. */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof GeographyBounds) {
            throw new UnexpectedTypeException($constraint, GeographyBounds::class);
        }
        if (null === $value) {
            return;
        }
        if (!$value instanceof PointInterface) {
            throw new UnexpectedValueException($value, PointInterface::class);
        }
        if (SpatialModelEnum::GEOGRAPHY !== $value->getFamily()) {
            return;
        }
        foreach (['X' => [$value->getX(), 180], 'Y' => [$value->getY(), 90]] as $axis => [$ordinate, $limit]) {
            if (null !== $ordinate && is_finite((float) $ordinate) && ($ordinate < -$limit || $ordinate > $limit)) {
                $this->context->buildViolation('The {{ axis }} ordinate must be within [{{ min }}, {{ max }}] degrees.')
                    ->setParameter('{{ axis }}', $axis)->setParameter('{{ min }}', (string) -$limit)
                    ->setParameter(
                        '{{ max }}',
                        (string) $limit
                    )->setCode(GeographyBounds::INVALID_ERROR)->addViolation()
                ;
            }
        }
    }
}
