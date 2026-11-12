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
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Verify both protocols independently of the installed Symfony version.
 *
 * @internal
 */
final class ConstraintValidatorCompatibilityTest extends CompatibleConstraintValidatorTestCase
{
    /**
     * Validators without the modern method must initialize before validation.
     */
    public function testLegacyProtocolInitializesBeforeValidation(): void
    {
        $constraint = new NotNull();
        $validator = new class implements ConstraintValidatorInterface {
            /** @var list<string> */
            public array $calls = [];

            public ?ExecutionContextInterface $context = null;

            /** @var list<mixed> */
            public array $arguments = [];

            /**
             * Record legacy initialization.
             *
             * @param ExecutionContextInterface $context execution context
             */
            public function initialize(ExecutionContextInterface $context): void
            {
                $this->calls[] = 'initialize';
                $this->context = $context;
            }

            /**
             * Record legacy validation.
             *
             * @param mixed      $value      validated value
             * @param Constraint $constraint applied constraint
             */
            public function validate(mixed $value, Constraint $constraint): void
            {
                $this->calls[] = 'validate';
                $this->arguments = [$value, $constraint];
            }
        };

        self::validateConstraint($validator, 'value', $constraint);
        self::assertSame(['initialize', 'validate'], $validator->calls);
        self::assertSame(['value', $constraint], $validator->arguments);
        self::assertNotNull($validator->context);
        self::assertSame('value', $validator->context->getRoot());
    }

    /**
     * The modern protocol must receive the original arguments without initialization.
     */
    public function testModernProtocolAvoidsLegacyCalls(): void
    {
        $context = self::createStub(ExecutionContextInterface::class);
        $constraint = new NotNull();
        $validator = new class implements ConstraintValidatorInterface {
            /** @var list<mixed> */
            public array $arguments = [];

            /**
             * Legacy initialization must not run.
             *
             * @param ExecutionContextInterface $context execution context
             */
            public function initialize(ExecutionContextInterface $context): void
            {
                TestCase::fail('The modern protocol must not initialize separately: '.$context::class);
            }

            /**
             * Legacy validation must not run.
             *
             * @param mixed      $value      validated value
             * @param Constraint $constraint applied constraint
             */
            public function validate(mixed $value, Constraint $constraint): void
            {
                TestCase::fail('The modern protocol must not call validate directly.');
            }

            /**
             * Record the modern invocation.
             *
             * @param mixed                     $value      validated value
             * @param Constraint                $constraint applied constraint
             * @param ExecutionContextInterface $context    execution context
             */
            public function validateInContext(mixed $value, Constraint $constraint, ExecutionContextInterface $context): void
            {
                $this->arguments = [$value, $constraint, $context];
            }
        };

        self::validateConstraint($validator, 'value', $constraint, $context);
        self::assertSame(['value', $constraint, $context], $validator->arguments);
    }
}
