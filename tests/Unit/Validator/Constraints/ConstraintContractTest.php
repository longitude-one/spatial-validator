<?php

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

/** @internal */
final class ConstraintContractTest extends TestCase
{
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
        (new $validator())->validate(null, $constraint);
    }

    public function testThreeDimensionalConstraintRejectsMissingZ(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new Constraints\SimpleThreeDimensionalLineStringValidator())->validate(
            new LineString([]),
            new Constraints\SimpleThreeDimensionalLineString()
        );
    }

    public function testEmptyRingPreservesBothMessagesAndParameters(): void
    {
        $violations = Validation::createValidator()->validate(new LineString([]), new Constraints\Ring());
        static::assertCount(2, $violations);
        static::assertSame('A linear ring must contain at least 4 points.', $violations->get(0)->getMessage());
        static::assertSame(['{{ limit }}' => '4'], $violations->get(0)->getParameters());
        static::assertSame('A linear ring must have equal first and last points.', $violations->get(1)->getMessage());
        static::assertNull($violations->get(0)->getCode());
        static::assertNull($violations->get(1)->getCode());
    }
}
