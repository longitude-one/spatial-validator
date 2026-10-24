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

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpecializedInputTest extends TestCase
{
    /** @param class-string<SpatialInterface> $interface */
    #[DataProviderExternal(SpecializedValidationTest::class, 'types')]
    public function testIncompatibleDeclaredTypeIsRejected(GeometryTypeEnum $type, string $interface): void
    {
        $value = self::createStub($interface);
        $different = GeometryTypeEnum::POINT === $type ? GeometryTypeEnum::LINESTRING : GeometryTypeEnum::POINT;
        $value->method('getType')->willReturn($different);
        $constraint = SpecializedValidationTest::constraint($type);
        $violations = Validation::createValidator()->validate($value, $constraint);

        self::assertCount(1, $violations);
        self::assertSame($type->value, $violations->get(0)->getParameters()['{{ type }}']);
        $this->expectException(UnexpectedValueException::class);
        SpecializedValidationTest::validator($constraint)->validate($value, $constraint);
    }

    #[DataProviderExternal(SpecializedValidationTest::class, 'enumTypes')]
    public function testNullIsIgnored(GeometryTypeEnum $type): void
    {
        $constraint = SpecializedValidationTest::constraint($type);
        SpecializedValidationTest::validator($constraint)->validate(null, $constraint);
        self::assertCount(0, Validation::createValidator()->validate(null, $constraint));
    }

    #[DataProviderExternal(SpecializedValidationTest::class, 'enumTypes')]
    public function testUnsupportedPhpValuesAreRejected(GeometryTypeEnum $type): void
    {
        $constraint = SpecializedValidationTest::constraint($type);
        $validator = SpecializedValidationTest::validator($constraint);
        $service = Validation::createValidator();
        foreach (['', " \t\n", '0', 'POINT EMPTY', 42, false, [], (object) []] as $value) {
            try {
                $validator->validate($value, $constraint);
                self::fail('Only null or an appropriate spatial value is accepted.');
            } catch (UnexpectedValueException $exception) {
                self::assertNotSame('', $exception->getExpectedType());
            }
            $violations = $service->validate($value, $constraint);
            self::assertCount(1, $violations);
            self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
            self::assertSame($constraint, $violations->get(0)->getConstraint());
        }
    }

    #[DataProviderExternal(SpecializedValidationTest::class, 'enumTypes')]
    public function testWrongConstraintPrecedesValueHandling(GeometryTypeEnum $type): void
    {
        $constraint = SpecializedValidationTest::constraint($type);
        $validator = SpecializedValidationTest::validator($constraint);
        foreach ([null, ''] as $value) {
            try {
                $validator->validate($value, new NotNull());
                self::fail('A wrong constraint must be rejected before input handling.');
            } catch (UnexpectedTypeException $exception) {
                self::assertStringContainsString($constraint::class, $exception->getMessage());
            }
        }
    }
}
