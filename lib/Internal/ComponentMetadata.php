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

namespace LongitudeOne\SpatialValidator\Internal;

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialValidator\Validator\Constraints\Components;

/** @internal Shared parent/member metadata checks, independent of component geometry. */
final class ComponentMetadata
{
    public const string TYPE_ERROR = Components::TYPE_ERROR;

    /**
     * @param SpatialInterface $parent parent
     * @param SpatialInterface $member member
     *
     * @return array<string, string> Violation code => message
     */
    public static function errors(SpatialInterface $parent, SpatialInterface $member): array
    {
        $errors = [];
        if ($parent->getFamily() !== $member->getFamily()) {
            $errors[Components::FAMILY_ERROR] = 'The component must have the same spatial family as its parent.';
        }
        if ($parent->getDimension() !== $member->getDimension()) {
            $errors[Components::DIMENSION_ERROR] = 'The component must have the same coordinate variant as its parent.';
        }
        if (!$parent->getSpatialReference()->equals($member->getSpatialReference())) {
            $errors[Components::REFERENCE_ERROR] = 'The component must have the same spatial reference as its parent.';
        }

        return $errors;
    }
}
