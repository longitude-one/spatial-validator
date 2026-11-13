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

/** Validate aggregate membership and delegate component geometry rules. */
final class Components extends Constraint
{
    public const string CYCLE_ERROR = 'components-cycle';
    public const string DIMENSION_ERROR = 'components-dimension';
    public const string FAMILY_ERROR = 'components-family';
    public const string REFERENCE_ERROR = 'components-reference';
    public const string TYPE_ERROR = 'components-type';

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
