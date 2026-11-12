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

/** Validate component validity and continuity without requiring simplicity or closure. */
final class CompoundCurve extends Constraint
{
    public const string EMPTY_ERROR = 'compoundcurve-empty-member';
    public const string JOIN_ERROR = 'compoundcurve-disconnected';
    public const string UNAVAILABLE_ERROR = 'compoundcurve-unavailable';

    /**
     * @param array|null $groups  Validation groups
     * @param mixed      $payload Consumer metadata
     *
     * @phpstan-param string[]|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
