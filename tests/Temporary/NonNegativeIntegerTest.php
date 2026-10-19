<?php

declare(strict_types=1);

namespace LongitudeOne\SpatialValidator\Tests\Temporary;

use LongitudeOne\SpatialValidator\Temporary\NonNegativeInteger;
use LongitudeOne\SpatialValidator\Temporary\NonNegativeIntegerValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validation;

/**
 * @internal removed together with the temporary constraint in Story 2 (#3)
 */
#[CoversClass(NonNegativeInteger::class)]
#[CoversClass(NonNegativeIntegerValidator::class)]
final class NonNegativeIntegerTest extends TestCase
{
    /** @return iterable<string, array{int, int}> */
    public static function integers(): iterable
    {
        yield 'positive' => [42, 0];
        yield 'zero' => [0, 0];
        yield 'negative' => [-1, 1];
        yield 'minimum integer' => [PHP_INT_MIN, 1];
        yield 'maximum integer' => [PHP_INT_MAX, 0];
    }

    #[DataProvider('integers')]
    public function testThroughSymfonyEngine(int $value, int $expectedViolations): void
    {
        $violations = Validation::createValidator()->validate($value, new NonNegativeInteger());

        self::assertCount($expectedViolations, $violations);
        if (0 !== $expectedViolations) {
            $violation = $violations[0];
            self::assertNotNull($violation);
            self::assertSame('The value must be a non-negative integer.', $violation->getMessage());
        }
    }

    public function testNonIntegerIsRejectedByEngine(): void
    {
        $violations = Validation::createValidator()->validate('42', new NonNegativeInteger());

        self::assertCount(1, $violations);
    }

    public function testWrongConstraintIsRejected(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new NonNegativeIntegerValidator())->validate(0, new NotNull());
    }
}
