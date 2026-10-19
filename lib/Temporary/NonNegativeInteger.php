<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Temporary;

use Symfony\Component\Validator\Constraint;

/**
 * @internal temporary tooling fixture; removed with its tests in Story 2 (#3)
 */
final class NonNegativeInteger extends Constraint
{
    public string $message = 'The value must be a non-negative integer.';
}
