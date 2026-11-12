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

use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\LineString as ModelLineString;
use LongitudeOne\SpatialTypes\Types\Dimension2\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use LongitudeOne\SpatialValidator\Validator\Constraints\SpatialValidator;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use ReflectionMethod as MethodReflection;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpatialDispatchTest extends TestCase
{
    /**
     * @param GeometryTypeEnum $type      type
     * @param string           $interface interface
     *
     * @phpstan-param class-string<SpatialInterface> $interface
     */
    #[DataProviderExternal(SpecializedValidationTest::class, 'types')]
    public function testEveryTypeDelegatesInTheOriginalContext(GeometryTypeEnum $type, string $interface): void
    {
        $implemented = [GeometryTypeEnum::POINT, GeometryTypeEnum::LINESTRING,
            GeometryTypeEnum::CIRCULARSTRING, GeometryTypeEnum::COMPOUNDCURVE, GeometryTypeEnum::POLYGON,
            GeometryTypeEnum::TRIANGLE,
            GeometryTypeEnum::GEOMETRYCOLLECTION, GeometryTypeEnum::MULTIPOINT, GeometryTypeEnum::MULTILINESTRING,
            GeometryTypeEnum::MULTIPOLYGON, GeometryTypeEnum::POLYHEDRALSURFACE];
        if (\in_array($type, $implemented, true)) {
            $value = match ($type) {
                GeometryTypeEnum::POLYHEDRALSURFACE => PolyhedralSurfaceValidationTest::surface([]),
                GeometryTypeEnum::POINT => new Point(),
                GeometryTypeEnum::POLYGON => PolygonStructureTest::polygon([]),
                GeometryTypeEnum::TRIANGLE => PolygonStructureTest::triangle([]),
                GeometryTypeEnum::GEOMETRYCOLLECTION,
                GeometryTypeEnum::MULTIPOINT,
                GeometryTypeEnum::MULTILINESTRING,
                GeometryTypeEnum::MULTIPOLYGON => CollectionValidationTest::aggregate($type->value, []),
                GeometryTypeEnum::CIRCULARSTRING => CircularStringValidationTest::curve([]),
                GeometryTypeEnum::COMPOUNDCURVE => CompoundCurveValidationTest::curve([]),
                default => new ModelLineString([]),
            };
            self::assertCount(0, Validation::createValidator()->validate($value, new Spatial()));

            return;
        }
        $value = self::createStub($interface);
        $value->method('getType')->willReturn($type);
        $root = ['shape' => $value];
        $payload = ['source' => 'consumer'];
        $constraint = new Spatial(groups: ['custom'], payload: $payload);
        $collection = new Collection(fields: ['shape' => $constraint], groups: ['custom']);
        $validator = Validation::createValidator();
        self::assertCount(0, $validator->validate($value, $constraint));
        $violations = $validator->validate($root, $collection, ['custom']);

        self::assertCount(1, $violations);
        $violation = $violations->get(0);
        $specialized = $violation->getConstraint();
        self::assertNotNull($specialized);
        self::assertSame(SpecializedValidationTest::constraint($type)::class, $specialized::class);
        self::assertSame($type->value.' validation is not implemented yet.', $violation->getMessage());
        self::assertSame(['severity' => 'warning'], $specialized->payload);
        self::assertSame(['custom'], $specialized->groups);
        self::assertSame($payload, $constraint->payload);
        self::assertSame($root, $violation->getRoot());
        self::assertSame('[shape]', $violation->getPropertyPath());
        self::assertSame($value, $violation->getInvalidValue());
    }

    /**
     * Verify triangle dispatch retains its interface precondition.
     */
    public function testTriangleDispatchRetainsItsInterfacePrecondition(): void
    {
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::TRIANGLE);
        $violations = Validation::createValidator()->validate($polygon, new Spatial());

        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
        self::assertSame(
            'LongitudeOne\SpatialTypes\Interfaces\TriangleInterface',
            $violations->get(0)->getParameters()['{{ type }}']
        );
    }

    /**
     * Verify unknown type name has an explicit defensive failure.
     */
    public function testUnknownTypeNameHasAnExplicitDefensiveFailure(): void
    {
        // SpatialInterface currently returns a closed enum: exercise the future-proof mapping fallback directly.
        $method = new MethodReflection(SpatialValidator::class, 'constraintClass');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Unsupported spatial type: UnknownType');
        $method->invoke(new SpatialValidator(), 'UnknownType');
    }
}
