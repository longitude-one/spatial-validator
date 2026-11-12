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

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use LongitudeOne\SpatialValidator\Validator\Constraints\SpatialValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpatialInputTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function constraintCheckValues(): iterable
    {
        yield 'null' => [null];

        yield 'empty string' => [''];
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unsupportedValues(): iterable
    {
        yield 'integer' => [42];

        yield 'false' => [false];

        yield 'string' => ['POINT EMPTY'];

        yield 'empty string' => [''];

        yield 'whitespace string' => [" \t\n"];

        yield 'numeric string' => ['0'];

        yield 'array' => [[]];

        yield 'object' => [(object) []];
    }

    #[DataProvider('unsupportedValues')]
    /**
     * Verify direct invocation rejects unsupported values.
     *
     * @param mixed $value spatial value to validate
     */
    public function testDirectInvocationRejectsUnsupportedValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains(SpatialInterface::class);
        new SpatialValidator()->validate($value, new Spatial());
    }

    /**
     * Verify not null does not allow empty strings.
     */
    public function testNotNullDoesNotAllowEmptyStrings(): void
    {
        $constraint = new Sequentially([new NotNull(), new Spatial()]);
        $validator = Validation::createValidator();
        $absent = $validator->validate(null, $constraint);
        $string = $validator->validate('', $constraint);
        $empty = $validator->validate(new Point(), $constraint);

        self::assertCount(1, $absent);
        self::assertInstanceOf(NotNull::class, $absent->get(0)->getConstraint());
        self::assertCount(1, $string);
        self::assertInstanceOf(Spatial::class, $string->get(0)->getConstraint());
        self::assertSame('This value should be of type {{ type }}.', $string->get(0)->getMessageTemplate());
        self::assertCount(0, $empty);
    }

    /**
     * Verify null is ignored.
     */
    public function testNullIsIgnored(): void
    {
        new SpatialValidator()->validate(null, new Spatial());
        self::assertCount(0, Validation::createValidator()->validate(null, new Spatial()));
    }

    /**
     * Verify presence can run before spatial validation.
     */
    public function testPresenceCanRunBeforeSpatialValidation(): void
    {
        $constraint = new Sequentially([new NotBlank(), new Spatial()]);
        $validator = Validation::createValidator();
        $absent = $validator->validate(null, $constraint);
        $empty = $validator->validate(new Point(), $constraint);

        self::assertCount(1, $absent);
        self::assertInstanceOf(NotBlank::class, $absent->get(0)->getConstraint());
        self::assertCount(0, $empty);
    }

    #[DataProvider('unsupportedValues')]
    /**
     * Verify service converts unsupported values to type violations.
     *
     * @param mixed $value spatial value to validate
     */
    public function testServiceConvertsUnsupportedValuesToTypeViolations(mixed $value): void
    {
        $violations = Validation::createValidator()->validate($value, new Spatial());

        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        self::assertSame(SpatialInterface::class, $violations->get(0)->getParameters()['{{ type }}']);
        self::assertSame($value, $violations->get(0)->getInvalidValue());
    }

    #[DataProvider('constraintCheckValues')]
    /**
     * Verify wrong constraint is rejected before value handling.
     *
     * @param mixed $value spatial value to validate
     */
    public function testWrongConstraintIsRejectedBeforeValueHandling(mixed $value): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageIsOrContains(Spatial::class);
        new SpatialValidator()->validate($value, new NotNull());
    }
}
