<?php

/**
 * This file is part of the spatial project.
 *
 * PHP 8.4 | 8.5
 *
 * Copyright Alexandre Tranchant <alexandre.tranchant@gmail.com> 2026
 * Copyright Longitude One 2026
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Validator\Constraints;

use InvalidArgumentException as InvalidLimit;
use Symfony\Component\Validator\Constraint;

/** Require a minimum point count, with a separate EMPTY allowance. */
final class MinimumPointCount extends Constraint
{
    public const string INVALID_ERROR = 'minimumpointcount-invalid';

    /**
     * @param int        $limit   minimum required number of positions
     * @param array|null $groups  Validation groups
     * @param mixed      $payload Consumer metadata
     *
     * @phpstan-param string[]|null $groups
     */
    public function __construct(public int $limit = 2, ?array $groups = null, mixed $payload = null)
    {
        if (1 > $limit) {
            throw new InvalidLimit('The minimum point count must be positive.');
        }
        parent::__construct(null, $groups, $payload);
    }
}
