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

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString;
use LongitudeOne\SpatialValidator\Validator\Constraints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class ConstraintContractTest extends TestCase
{
    public function testEmptyRingPreservesBothMessagesAndParameters(): void
    {
        $violations = Validation::createValidator()->validate(new LineString([]), new Constraints\Ring());
        self::assertCount(2, $violations);
        self::assertSame('A linear ring must contain at least 4 points.', $violations->get(0)->getMessage());
        self::assertSame(['{{ limit }}' => '4'], $violations->get(0)->getParameters());
        self::assertSame('A linear ring must have equal first and last points.', $violations->get(1)->getMessage());
        self::assertNull($violations->get(0)->getCode());
        self::assertNull($violations->get(1)->getCode());
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProvider('constraints')]
    public function testRejectsWrongConstraint(Constraint $constraint, string $validator): void
    {
        $wrong = $constraint instanceof Constraints\Ring ? new Constraints\Triangle() : new Constraints\Ring();
        $this->expectException(UnexpectedTypeException::class);
        (new $validator())->validate(null, $wrong);
    }

    /** @param class-string<ConstraintValidator> $validator */
    #[DataProvider('constraints')]
    public function testRejectsWrongValue(Constraint $constraint, string $validator): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new $validator())->validate(42, $constraint);
    }

    /** @return iterable<string, array{Constraint, class-string<ConstraintValidator>}> */
    public static function constraints(): iterable
    {
        $constraints = [
            new Constraints\FirstPointEqualsLastPoint(), new Constraints\MinimumPointCount(),
            new Constraints\NoConsecutiveDuplicatePoints(), new Constraints\Ring(),
            new Constraints\SameDimension(CoordinateDimensionEnum::XY),
            new Constraints\SameFamily(SpatialModelEnum::GEOMETRY),
            new Constraints\SameSpatialReference(SpatialReference::fromSrid(4326)),
            new Constraints\SimpleLineString(), new Constraints\SimpleThreeDimensionalLineString(),
            new Constraints\Triangle(), new Constraints\PolyhedralSurface(),
        ];
        foreach ($constraints as $constraint) {
            /** @var class-string<ConstraintValidator> $validator */
            $validator = $constraint->validatedBy();

            yield $constraint::class => [$constraint, $validator];
        }
    }

    public function testThreeDimensionalConstraintRejectsMissingZ(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new Constraints\Simple3dLineStringValidator())->validate(
            new LineString([]),
            new Constraints\SimpleThreeDimensionalLineString()
        );
    }
}
