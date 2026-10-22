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

/** Validate aggregate membership and delegate component geometry rules. */
final class Components extends Constraint
{
    public const string CYCLE_ERROR = 'components-cycle';
    public const string DIMENSION_ERROR = 'components-dimension';
    public const string FAMILY_ERROR = 'components-family';
    public const string REFERENCE_ERROR = 'components-reference';
    public const string TYPE_ERROR = 'components-type';

    /**
     * @param null|string[] $groups  Validation groups
     * @param mixed         $payload Consumer metadata
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
