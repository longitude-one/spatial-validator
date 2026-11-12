<?php
/*
 * This file is part of the LongitudeOne Spatial project.
 *
 * Copyright (c) 2026 Alexandre Tranchant <alexandre.tranchant@gmail.com>
 *
 * See LICENSE for copyright and license information.
 *
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** Configure the unavailable CompoundSurface validation warning. */
final class CompoundSurface extends Constraint
{
    /**
     * @param array|null $groups  Validation groups
     * @param mixed      $payload Consumer metadata; warnings are the default severity
     *
     * @phpstan-param string[]|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = ['severity' => 'warning'])
    {
        parent::__construct(null, $groups, $payload);
    }
}
