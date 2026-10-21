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

namespace LongitudeOne\SpatialValidator\Tests\Unit\Validator\Constraints;

use LongitudeOne\SpatialTypes\Interfaces\LineStringInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class InputHandlingTest extends TestCase
{
    private static function expectedInterface(Constraint $constraint): string
    {
        return match (true) {
            $constraint instanceof Constraints\Triangle => PolygonInterface::class,
            $constraint instanceof Constraints\PolyhedralSurface => PolyhedralSurfaceInterface::class,
            $constraint instanceof Constraints\SameDimension,
            $constraint instanceof Constraints\SameFamily,
            $constraint instanceof Constraints\SameSpatialReference => SpatialInterface::class,
            default => LineStringInterface::class,
        };
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testConstraintCheckPrecedesMissingValueCheck(Constraint $constraint, string $validator): void
    {
        $wrong = new NotNull();
        foreach ([null, '', 42] as $value) {
            try {
                (new $validator())->validate($value, $wrong);
                self::fail('An unexpected constraint must be rejected.');
            } catch (UnexpectedTypeException $exception) {
                self::assertStringContainsString($constraint::class, $exception->getMessage());
            }
        }
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testMissingValuesAreIgnored(Constraint $constraint, string $validator): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');
        $context->expects(self::never())->method('getValidator');
        $direct = new $validator();
        $direct->initialize($context);
        foreach ([null, ''] as $value) {
            $direct->validate($value, $constraint);
            self::assertCount(0, Validation::createValidator()->validate($value, $constraint));
        }
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testPresenceConstraintsCompose(Constraint $constraint, string $validator): void
    {
        self::assertInstanceOf(ConstraintValidator::class, new $validator());
        $service = Validation::createValidator();
        $nullViolations = $service->validate(null, [$constraint, new NotNull()]);
        self::assertCount(1, $nullViolations);
        self::assertSame(NotNull::IS_NULL_ERROR, $nullViolations->get(0)->getCode());
        $blankViolations = $service->validate('', [$constraint, new NotBlank()]);
        self::assertCount(1, $blankViolations);
        self::assertSame(NotBlank::IS_BLANK_ERROR, $blankViolations->get(0)->getCode());
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testSpatialObjectsStillRequireSpecializedInterfaces(Constraint $constraint, string $validator): void
    {
        $point = new Point(null, null, 4326);
        if (SpatialInterface::class === self::expectedInterface($constraint)) {
            self::assertCount(0, Validation::createValidator()->validate($point, $constraint));

            return;
        }
        $this->assertUnsupportedValue($constraint, $validator, $point);
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProviderExternal(ConstraintContractTest::class, 'constraints')]
    public function testUnsupportedValuesFollowSymfonyTypeContract(Constraint $constraint, string $validator): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);

        try {
            foreach ([42, 1.5, 'value', '0', true, false, [], [1], $resource, (object) ['value' => 1]] as $value) {
                $this->assertUnsupportedValue($constraint, $validator, $value);
            }
        } finally {
            fclose($resource);
        }
    }

    /** @param class-string<ConstraintValidator> $validator */
    private function assertUnsupportedValue(Constraint $constraint, string $validator, mixed $value): void
    {
        $expected = self::expectedInterface($constraint);

        try {
            (new $validator())->validate($value, $constraint);
            self::fail('Unsupported values must raise UnexpectedValueException directly.');
        } catch (UnexpectedValueException $exception) {
            self::assertSame($expected, $exception->getExpectedType());
        }
        $violations = Validation::createValidator()->validate($value, $constraint);
        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        self::assertSame($expected, $violations->get(0)->getParameters()['{{ type }}']);
        self::assertSame($constraint, $violations->get(0)->getConstraint());
    }
}
