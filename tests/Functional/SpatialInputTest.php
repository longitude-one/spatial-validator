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

namespace LongitudeOne\SpatialValidator\Tests\Functional;

use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
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
    #[DataProvider('unsupportedValues')]
    public function testDirectInvocationRejectsUnsupportedValues(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains(SpatialInterface::class);
        (new SpatialValidator())->validate($value, new Spatial());
    }

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

    public function testNullIsIgnored(): void
    {
        (new SpatialValidator())->validate(null, new Spatial());
        self::assertCount(0, Validation::createValidator()->validate(null, new Spatial()));
    }

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
    public function testServiceConvertsUnsupportedValuesToTypeViolations(mixed $value): void
    {
        $violations = Validation::createValidator()->validate($value, new Spatial());

        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        self::assertSame(SpatialInterface::class, $violations->get(0)->getParameters()['{{ type }}']);
        self::assertSame($value, $violations->get(0)->getInvalidValue());
    }

    /** @return iterable<string, array{mixed}> */
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

    #[DataProvider('constraintCheckValues')]
    public function testWrongConstraintIsRejectedBeforeValueHandling(mixed $value): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageIsOrContains(Spatial::class);
        (new SpatialValidator())->validate($value, new NotNull());
    }

    /** @return iterable<string, array{mixed}> */
    public static function constraintCheckValues(): iterable
    {
        yield 'null' => [null];

        yield 'empty string' => [''];
    }
}
