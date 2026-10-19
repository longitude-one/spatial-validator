<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Temporary;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @internal temporary tooling fixture; removed with its tests in Story 2 (#3)
 */
final class NonNegativeIntegerValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NonNegativeInteger) {
            throw new UnexpectedTypeException($constraint, NonNegativeInteger::class);
        }

        if (!is_int($value)) {
            throw new UnexpectedValueException($value, 'int');
        }

        if ($value < 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
