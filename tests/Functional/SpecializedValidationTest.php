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
use LongitudeOne\SpatialTypes\Interfaces\PolygonInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString as ModelLineString;
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\Point;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use LongitudeOne\SpatialValidator\Validator\Constraints\Components;
use LongitudeOne\SpatialValidator\Validator\Constraints\Triangle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class SpecializedValidationTest extends TestCase
{
    public static function constraint(GeometryTypeEnum $type): Constraint
    {
        $class = 'LongitudeOne\SpatialValidator\Validator\Constraints\\' . $type->value;
        $constraint = new $class();
        self::assertInstanceOf(Constraint::class, $constraint);

        return $constraint;
    }

    /** @return iterable<string, array{GeometryTypeEnum}> */
    public static function enumTypes(): iterable
    {
        foreach (GeometryTypeEnum::cases() as $type) {
            yield $type->value => [$type];
        }
    }

    public static function validator(Constraint $constraint): ConstraintValidator
    {
        $class = $constraint->validatedBy();
        $validator = new $class();
        self::assertInstanceOf(ConstraintValidator::class, $validator);

        return $validator;
    }

    /** @param class-string<SpatialInterface> $interface */
    #[DataProvider('types')]
    public function testDirectInvocationReportsUnavailableValidation(GeometryTypeEnum $type, string $interface): void
    {
        $implemented = [GeometryTypeEnum::POINT, GeometryTypeEnum::LINESTRING,
            GeometryTypeEnum::CIRCULARSTRING, GeometryTypeEnum::COMPOUNDCURVE, GeometryTypeEnum::POLYGON,
            GeometryTypeEnum::GEOMETRYCOLLECTION, GeometryTypeEnum::MULTIPOINT, GeometryTypeEnum::MULTILINESTRING];
        if (in_array($type, $implemented, true)) {
            $value = match ($type) {
                GeometryTypeEnum::POINT => new Point(),
                GeometryTypeEnum::POLYGON => PolygonStructureTest::polygon([]),
                GeometryTypeEnum::GEOMETRYCOLLECTION,
                GeometryTypeEnum::MULTIPOINT,
                GeometryTypeEnum::MULTILINESTRING => CollectionValidationTest::aggregate($type->value, []),
                GeometryTypeEnum::CIRCULARSTRING => CircularStringValidationTest::curve([]),
                GeometryTypeEnum::COMPOUNDCURVE => CompoundCurveValidationTest::curve([]),
                default => new ModelLineString([]),
            };
            self::assertCount(0, Validation::createValidator()->validate($value, self::constraint($type)));

            return;
        }
        $value = self::createStub($interface);
        $value->method('getType')->willReturn($type);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('addViolation');
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')
            ->with($type->value . ' validation is not implemented yet.')->willReturn($builder)
        ;
        // Symfony 5.4's untyped methods do not get automatic PHPUnit return stubs.
        $service = $this->createMock(ValidatorInterface::class);
        $contextual = $this->createMock(ContextualValidatorInterface::class);
        $delegations = in_array($type, [
            GeometryTypeEnum::CIRCULARSTRING,
            GeometryTypeEnum::COMPOUNDCURVE,
            GeometryTypeEnum::GEOMETRYCOLLECTION,
            GeometryTypeEnum::LINESTRING,
            GeometryTypeEnum::MULTILINESTRING,
            GeometryTypeEnum::MULTIPOINT,
            GeometryTypeEnum::MULTIPOLYGON,
            GeometryTypeEnum::POLYGON,
            GeometryTypeEnum::POLYHEDRALSURFACE,
            GeometryTypeEnum::TRIANGLE,
        ], true) ? 1 : 0;
        $context->expects(self::exactly($delegations))->method('getValidator')->willReturn($service);
        $context->method('getGroup')->willReturn('direct');
        $service->expects(self::exactly($delegations))->method('inContext')->with($context)->willReturn($contextual);
        $contextual->expects(self::exactly($delegations))->method('validate')
            ->with($value, new Components(groups: ['Default']), 'direct')->willReturnSelf()
        ;
        $validator = self::validator(self::constraint($type));
        $validator->initialize($context);
        $validator->validate($value, self::constraint($type));
    }

    public function testEveryEnumCaseHasAnExplicitTestedPair(): void
    {
        $tested = [];
        foreach (self::types() as [$type]) {
            $tested[] = $type;
        }
        self::assertSame(GeometryTypeEnum::cases(), $tested);
    }

    /** @param class-string<SpatialInterface> $interface */
    #[DataProvider('types')]
    public function testGroupsPayloadAndNestedPath(GeometryTypeEnum $type, string $interface): void
    {
        $implemented = [GeometryTypeEnum::POINT, GeometryTypeEnum::LINESTRING,
            GeometryTypeEnum::CIRCULARSTRING, GeometryTypeEnum::COMPOUNDCURVE, GeometryTypeEnum::POLYGON,
            GeometryTypeEnum::GEOMETRYCOLLECTION, GeometryTypeEnum::MULTIPOINT, GeometryTypeEnum::MULTILINESTRING];
        if (in_array($type, $implemented, true)) {
            $value = match ($type) {
                GeometryTypeEnum::POINT => new Point(),
                GeometryTypeEnum::POLYGON => PolygonStructureTest::polygon([]),
                GeometryTypeEnum::GEOMETRYCOLLECTION,
                GeometryTypeEnum::MULTIPOINT,
                GeometryTypeEnum::MULTILINESTRING => CollectionValidationTest::aggregate($type->value, []),
                GeometryTypeEnum::CIRCULARSTRING => CircularStringValidationTest::curve([]),
                GeometryTypeEnum::COMPOUNDCURVE => CompoundCurveValidationTest::curve([]),
                default => new ModelLineString([]),
            };
            self::assertCount(0, Validation::createValidator()->validate($value, self::constraint($type)));

            return;
        }
        $value = self::createStub($interface);
        $value->method('getType')->willReturn($type);
        $class = self::constraint($type)::class;
        $payload = ['severity' => 'warning', 'source' => 'consumer'];
        $constraint = new $class(groups: ['consumer'], payload: $payload);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $root = ['shape' => $value];
        $collection = new Collection(fields: ['shape' => $constraint], groups: ['consumer']);
        $violations = $service->validate($root, $collection, ['consumer']);

        self::assertCount(1, $violations);
        self::assertSame($root, $violations->get(0)->getRoot());
        self::assertSame('[shape]', $violations->get(0)->getPropertyPath());
        self::assertSame($constraint, $violations->get(0)->getConstraint());
        self::assertSame($payload, $constraint->payload);
    }

    public function testRingDirectCompositionUsesOriginalContext(): void
    {
        $value = LineStringValidationTest::line([[0, 0], [1, 0], [0, 1], [0, 0]]);
        $constraint = new SpatialAssert\Ring(groups: ['direct'], payload: ['source' => 'consumer']);
        $context = $this->createMock(ExecutionContextInterface::class);
        $service = $this->createMock(ValidatorInterface::class);
        $contextual = $this->createMock(ContextualValidatorInterface::class);
        $context->method('getGroup')->willReturn('direct');
        $context->expects(self::once())->method('getValidator')->willReturn($service);
        $service->expects(self::once())->method('inContext')
            ->with(self::identicalTo($context))->willReturn($contextual)
        ;
        $rules = [
            new SpatialAssert\MinimumPointCount(limit: 4, groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\FirstPointEqualsLastPoint(groups: ['direct'], payload: $constraint->payload),
            new SpatialAssert\SimpleLineString(groups: ['direct'], payload: $constraint->payload),
        ];
        $contextual->expects(self::once())->method('validate')
            ->with(self::identicalTo($value), $rules, 'direct')->willReturnSelf()
        ;
        $validator = new SpatialAssert\RingValidator();
        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }

    /** @param class-string<SpatialInterface> $interface */
    #[DataProvider('types')]
    public function testServiceReportsWarningAndPreservesContext(GeometryTypeEnum $type, string $interface): void
    {
        $implemented = [GeometryTypeEnum::POINT, GeometryTypeEnum::LINESTRING,
            GeometryTypeEnum::CIRCULARSTRING, GeometryTypeEnum::COMPOUNDCURVE, GeometryTypeEnum::POLYGON,
            GeometryTypeEnum::GEOMETRYCOLLECTION, GeometryTypeEnum::MULTIPOINT, GeometryTypeEnum::MULTILINESTRING];
        if (in_array($type, $implemented, true)) {
            $value = match ($type) {
                GeometryTypeEnum::POINT => new Point(),
                GeometryTypeEnum::POLYGON => PolygonStructureTest::polygon([]),
                GeometryTypeEnum::GEOMETRYCOLLECTION,
                GeometryTypeEnum::MULTIPOINT,
                GeometryTypeEnum::MULTILINESTRING => CollectionValidationTest::aggregate($type->value, []),
                GeometryTypeEnum::CIRCULARSTRING => CircularStringValidationTest::curve([]),
                GeometryTypeEnum::COMPOUNDCURVE => CompoundCurveValidationTest::curve([]),
                default => new ModelLineString([]),
            };
            self::assertCount(0, Validation::createValidator()->validate($value, self::constraint($type)));

            return;
        }
        $value = self::createStub($interface);
        $value->method('getType')->willReturn($type);
        $constraint = self::constraint($type);
        $violations = Validation::createValidator()->validate($value, $constraint);

        self::assertCount(1, $violations);
        self::assertSame($type->value . ' validation is not implemented yet.', $violations->get(0)->getMessage());
        self::assertSame(['severity' => 'warning'], $constraint->payload);
        self::assertSame($constraint, $violations->get(0)->getConstraint());
        self::assertSame($value, $violations->get(0)->getRoot());
        self::assertSame($value, $violations->get(0)->getInvalidValue());
        self::assertInstanceOf(ConstraintValidator::class, self::validator($constraint));
    }

    /** @return iterable<string, array{GeometryTypeEnum, class-string<SpatialInterface>}> */
    public static function types(): iterable
    {
        $interfaces = [
            'BRepSolid' => 'SpatialInterface',
            'Circle' => 'SpatialInterface',
            'CircularString' => 'CircularStringInterface',
            'Clothoid' => 'SpatialInterface',
            'CompoundCurve' => 'CompoundCurveInterface',
            'CompoundSurface' => 'SpatialInterface',
            'Curve' => 'CurveInterface',
            'CurvePolygon' => 'SpatialInterface',
            'EllipticalCurve' => 'SpatialInterface',
            'GeodesicString' => 'SpatialInterface',
            'Geometry' => 'SpatialInterface',
            'GeometryCollection' => 'CollectionInterface',
            'LineString' => 'LineStringInterface',
            'MultiCurve' => 'SpatialInterface',
            'MultiLineString' => 'MultiLineStringInterface',
            'MultiPoint' => 'MultiPointInterface',
            'MultiPolygon' => 'MultiPolygonInterface',
            'MultiSurface' => 'SpatialInterface',
            'NURBSCurve' => 'SpatialInterface',
            'Point' => 'PointInterface',
            'Polygon' => 'PolygonInterface',
            'PolyhedralSurface' => 'PolyhedralSurfaceInterface',
            'Solid' => 'SpatialInterface',
            'SpiralCurve' => 'SpatialInterface',
            'Surface' => 'SpatialInterface',
            'TIN' => 'SpatialInterface',
            'Triangle' => 'TriangleInterface',
        ];
        foreach ($interfaces as $name => $interface) {
            $type = GeometryTypeEnum::from($name);

            /** @var class-string<SpatialInterface> $interface */
            $interface = 'LongitudeOne\SpatialTypes\Interfaces\\' . $interface;

            yield $name => [$type, $interface];
        }
    }

    public function testTriangleDoesNotAcceptAPolygonDeclaringTriangleType(): void
    {
        $polygon = self::createStub(PolygonInterface::class);
        $polygon->method('getType')->willReturn(GeometryTypeEnum::TRIANGLE);
        $violations = Validation::createValidator()->validate($polygon, new Triangle());

        self::assertCount(1, $violations);
        self::assertSame(
            'LongitudeOne\SpatialTypes\Interfaces\TriangleInterface',
            $violations->get(0)->getParameters()['{{ type }}']
        );
    }
}
