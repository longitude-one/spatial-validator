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

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Invoke constraint validators directly across the supported Symfony versions.
 *
 * Symfony 8.1 introduced validateInContext() to scope the execution context to
 * one call and restore the previous context after nested calls or exceptions.
 * Symfony 5.4 through 8.0 still require initialize() followed by validate().
 * Keeping that fallback here avoids calling deprecated entry points on 8.1+.
 *
 * This helper is for direct-call tests only. Service-level validation and
 * inContext()->validate() must continue to use Symfony's validator service.
 * A supplied context is forwarded unchanged, preserving context expectations.
 * Without one, a real context is created for input-contract tests.
 *
 * Remove this helper, its call sites and its two PHPStan deprecation exceptions
 * once the minimum supported symfony/validator version is 8.1 or newer.
 * The removal condition is dependency support, not a calendar date.
 *
 * @internal
 */
abstract class CompatibleConstraintValidatorTestCase extends TestCase
{
    /**
     * Invoke the modern entry point or the legacy compatibility protocol.
     *
     * @param ConstraintValidatorInterface   $validator  validator under test
     * @param mixed                          $value      value to validate
     * @param Constraint                     $constraint applied constraint
     * @param ExecutionContextInterface|null $context    original context, when required
     */
    protected static function validateConstraint(
        ConstraintValidatorInterface $validator,
        mixed $value,
        Constraint $constraint,
        ?ExecutionContextInterface $context = null,
    ): void {
        $context ??= new ExecutionContext(Validation::createValidator(), $value, new class implements TranslatorInterface {
            use TranslatorTrait;
        });

        if (method_exists($validator, 'validateInContext')) {
            $validator->validateInContext($value, $constraint, $context);

            return;
        }

        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }
}
