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

use Symfony\Component\Validator\Constraint;

/** Configure the unavailable MultiPoint validation warning. */
final class MultiPoint extends Constraint
{
    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata; warnings are the default severity
     */
    public function __construct(?array $groups = null, mixed $payload = ['severity' => 'warning'])
    {
        parent::__construct(null, $groups, $payload);
    }
}
