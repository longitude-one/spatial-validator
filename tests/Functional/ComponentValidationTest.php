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

use LongitudeOne\Core\Enum\CoordinateDimensionEnum;
use LongitudeOne\Core\Enum\GeometryTypeEnum;
use LongitudeOne\Core\Enum\SpatialModelEnum;
use LongitudeOne\SpatialTypes\Interfaces\CollectionInterface;
use LongitudeOne\SpatialTypes\Interfaces\MutablePointInterface;
use LongitudeOne\SpatialTypes\Interfaces\PointInterface;
use LongitudeOne\SpatialTypes\Interfaces\SpatialInterface;
use LongitudeOne\SpatialTypes\Reference\SpatialReference;
use LongitudeOne\SpatialTypes\Types\Dimension2\Point;
use LongitudeOne\SpatialTypes\Value\Coordinates;
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException as AccessorFailure;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/**
 * @internal
 *
 * @coversNothing
 */
final class ComponentValidationTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function aggregatePaths(): iterable
    {
        yield 'lines' => ['MultiLineString', 'lineStrings[0]'];

        yield 'polygons' => ['MultiPolygon', 'polygons[0]'];

        yield 'polygon rings' => ['Polygon', 'rings[0]'];

        yield 'triangle rings' => ['Triangle', 'rings[0]'];

        yield 'curves' => ['CompoundCurve', 'curves[0]'];

        yield 'patches' => ['PolyhedralSurface', 'patches[0]'];
    }

    /**
     * @return iterable<string, array{bool, list<string>}>
     */
    public static function cycles(): iterable
    {
        yield 'direct' => [false, ['geometries[0]', 'geometries[1]']];

        yield 'indirect' => [true, ['geometries[0].geometries[0]', 'geometries[1]']];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function permittedMembers(): iterable
    {
        yield 'line collection' => ['MultiLineString', 'LineString', 'lineStrings[0]'];

        yield 'polygon collection' => ['MultiPolygon', 'Polygon', 'polygons[0]'];

        yield 'triangle subtype in polygon collection' => ['MultiPolygon', 'Triangle', 'polygons[0]'];

        yield 'polygon ring' => ['Polygon', 'LineString', 'rings[0]'];

        yield 'triangle ring' => ['Triangle', 'LineString', 'rings[0]'];

        yield 'linear curve' => ['CompoundCurve', 'LineString', 'curves[0]'];

        yield 'circular curve' => ['CompoundCurve', 'CircularString', 'curves[0]'];

        yield 'polygon patch' => ['PolyhedralSurface', 'Polygon', 'patches[0]'];

        yield 'triangle patch' => ['PolyhedralSurface', 'Triangle', 'patches[0]'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pointAggregates(): iterable
    {
        yield 'line' => ['LineString'];

        yield 'arc' => ['CircularString'];

        yield 'multi-point' => ['MultiPoint'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function variants(): iterable
    {
        foreach (['Dimension2', 'Dimension3z', 'Dimension3m', 'Dimension4zm'] as $dimension) {
            foreach (['Geometry', 'Geography'] as $family) {
                yield $dimension.' '.$family => [$dimension, $family];
            }
        }
    }

    /**
     * Verify ancestor state is released after an exception.
     */
    public function testAncestorStateIsReleasedAfterAnException(): void
    {
        $fail = true;
        $failure = new AccessorFailure('Broken third-party accessor');
        $bad = $this->invalidPoint();
        $value = $this->aggregate('GeometryCollection', static function () use (&$fail, $bad, $failure): array {
            if ($fail) {
                $fail = false;

                throw $failure;
            }

            return [$bad];
        });
        $service = Validation::createValidator();

        try {
            $service->validate($value, new SpatialAssert\Components());
            self::fail('The third-party accessor must throw.');
        } catch (AccessorFailure $exception) {
            self::assertSame('Broken third-party accessor', $exception->getMessage());
        }
        $violations = $service->validate($value, new SpatialAssert\Components());
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
        self::assertSame('geometries[0]', $violations->get(0)->getPropertyPath());
        self::assertSame($value, $violations->get(0)->getRoot());
    }

    /**
     * Verify cycle is reported even for a forbidden member type.
     */
    public function testCycleIsReportedEvenForForbiddenMemberType(): void
    {
        $members = [];
        $value = $this->aggregate('CompoundCurve', static function () use (&$members): array {
            return $members;
        });
        $members = [$value];
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Components());
        self::assertCount(2, $violations);
        self::assertSame(SpatialAssert\Components::CYCLE_ERROR, $violations->get(0)->getCode());
        self::assertSame(SpatialAssert\Components::TYPE_ERROR, $violations->get(1)->getCode());
        self::assertSame('curves[0]', $violations->get(0)->getPropertyPath());
        self::assertSame('curves[0]', $violations->get(1)->getPropertyPath());
    }

    /**
     * @param bool  $indirect indirect
     * @param array $paths    paths
     *
     * @phpstan-param list<string> $paths
     */
    #[DataProvider('cycles')]
    public function testCyclesStopOnlyTheirBranch(bool $indirect, array $paths): void
    {
        $members = [];
        $first = $this->aggregate('GeometryCollection', static function () use (&$members): array {
            return $members;
        });
        $members = [$first, $this->invalidPoint()];
        if ($indirect) {
            $members[0] = $this->aggregate('GeometryCollection', [$first]);
        }
        $violations = Validation::createValidator()->validate($first, new SpatialAssert\Spatial());
        $actual = [];
        foreach ($violations as $violation) {
            if (null !== $violation->getCode()) {
                $actual[] = $violation->getPropertyPath();
                self::assertNull($violation->getConstraint()?->payload);
            }
        }
        self::assertSame($paths, $actual);
        $codes = array_map(static fn ($violation): ?string => $violation->getCode(), iterator_to_array($violations));
        self::assertContains(SpatialAssert\Components::CYCLE_ERROR, $codes);
        self::assertContains(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $codes);
    }

    /**
     * Verify direct aggregate requires its interface.
     */
    public function testDirectAggregateRequiresItsInterface(): void
    {
        $value = self::createStub(SpatialInterface::class);
        $value->method('getType')->willReturn(GeometryTypeEnum::LINESTRING);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Components());
        self::assertCount(1, $violations);
        self::assertSame(
            'LongitudeOne\SpatialTypes\Interfaces\LineStringInterface',
            $violations->get(0)->getParameters()['{{ type }}']
        );
    }

    /**
     * Verify each metadata mismatch is an error and payload does not change warnings.
     */
    public function testEachMetadataMismatchIsAnErrorAndPayloadDoesNotChangeWarnings(): void
    {
        $member = $this->aggregate(
            'LineString',
            [],
            CoordinateDimensionEnum::XYM,
            SpatialModelEnum::GEOGRAPHY,
            new SpatialReference(4326, 'OTHER')
        );
        $parent = $this->aggregate(
            'GeometryCollection',
            [$member, $member],
            CoordinateDimensionEnum::XYZ,
            SpatialModelEnum::GEOMETRY,
            SpatialReference::epsg(4326)
        );
        $payload = ['severity' => 'error', 'source' => 'consumer'];
        $constraint = new SpatialAssert\Components(payload: $payload);
        $violations = Validation::createValidator()->validate($parent, $constraint);
        $codes = [];
        foreach ($violations as $violation) {
            $codes[] = $violation->getCode();
            if (null === $violation->getCode()) {
                self::assertSame(['severity' => 'warning'], $violation->getConstraint()?->payload);

                continue;
            }
            self::assertSame($payload, $violation->getConstraint()?->payload);
            self::assertSame($constraint, $violation->getConstraint());
            self::assertSame($member, $violation->getInvalidValue());
        }
        self::assertSame([
            SpatialAssert\Components::FAMILY_ERROR, SpatialAssert\Components::DIMENSION_ERROR,
            SpatialAssert\Components::REFERENCE_ERROR,
            SpatialAssert\Components::FAMILY_ERROR, SpatialAssert\Components::DIMENSION_ERROR,
            SpatialAssert\Components::REFERENCE_ERROR,
        ], $codes);
        self::assertSame($payload, $constraint->payload);
    }

    /**
     * Verify empty parent still validates members without mutation.
     */
    public function testEmptyParentStillValidatesMembersWithoutMutation(): void
    {
        $empty = new Point();
        $members = [$empty, $empty];
        $value = $this->aggregate('GeometryCollection', $members);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, new SpatialAssert\Components()));
        self::assertInstanceOf(CollectionInterface::class, $value);
        self::assertSame($members, $value->getElements());
        $invalid = $this->aggregate('GeometryCollection', [$this->invalidPoint()]);
        $violations = $service->validate($invalid, new SpatialAssert\Components());
        self::assertCount(1, $violations);
        self::assertSame('geometries[0]', $violations->get(0)->getPropertyPath());
        self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
        $noMembers = $this->aggregate('GeometryCollection');
        self::assertCount(0, $service->validate($noMembers, new SpatialAssert\Components()));
    }

    #[DataProvider('variants')]
    /**
     * Verify matching family and coordinate variant are accepted.
     *
     * @param string $dimensionName dimension name
     * @param string $familyName    family name
     */
    public function testMatchingFamilyAndCoordinateVariantAreAccepted(string $dimensionName, string $familyName): void
    {
        $class = 'LongitudeOne\SpatialTypes\Types\\'.$dimensionName.'\Point';
        $dimension = match ($dimensionName) {
            'Dimension2' => CoordinateDimensionEnum::XY,
            'Dimension3z' => CoordinateDimensionEnum::XYZ,
            'Dimension3m' => CoordinateDimensionEnum::XYM,
            default => CoordinateDimensionEnum::XYZM,
        };

        /** @var MutablePointInterface $member */
        $member = new $class(...array_fill(0, $dimension->coordinateDimension(), 0));
        $member->setFamily(SpatialModelEnum::from($familyName));
        $parent = $this->aggregate(
            'MultiPoint',
            [$member],
            $dimension,
            $member->getFamily(),
            $member->getSpatialReference()
        );
        self::assertCount(0, Validation::createValidator()->validate($parent, new SpatialAssert\Components()));
    }

    #[DataProvider('aggregatePaths')]
    /**
     * Verify member type and literal paths.
     *
     * @param string $type type
     * @param string $path property path for the diagnostic
     */
    public function testMemberTypeAndLiteralPaths(string $type, string $path): void
    {
        $value = $this->aggregate($type, [new Point()]);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Components());
        self::assertCount(1, $violations);
        self::assertSame(SpatialAssert\Components::TYPE_ERROR, $violations->get(0)->getCode());
        self::assertSame($path, $violations->get(0)->getPropertyPath());
    }

    /**
     * Verify nested paths context groups and independent failures.
     */
    public function testNestedPathsContextGroupsAndIndependentFailures(): void
    {
        $bad = $this->invalidPoint();
        $ring = $this->aggregate('LineString', [new Point(0, 0), new Point(1, 0), new Point(2, 0), $bad]);
        $polygon = $this->aggregate('Polygon', [$this->aggregate('LineString'), $ring]);
        $value = $this->aggregate('GeometryCollection', [new Point(), new Point(), $polygon, $bad]);
        $root = ['shape' => $value];
        $constraint = new SpatialAssert\Spatial(groups: ['custom'], payload: ['source' => 'consumer']);
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate($value, $constraint));
        $violations = $service->validate(
            $root,
            new Assert\Collection(fields: ['shape' => $constraint], groups: ['custom']),
            ['custom']
        );
        $paths = [];
        foreach ($violations as $violation) {
            self::assertSame($root, $violation->getRoot());
            self::assertSame(['custom'], $violation->getConstraint()?->groups);
            if (SpatialAssert\FiniteCoordinates::INVALID_ERROR === $violation->getCode()) {
                $paths[] = $violation->getPropertyPath();
                self::assertInstanceOf(SpatialAssert\FiniteCoordinates::class, $violation->getConstraint());
                self::assertNull($violation->getConstraint()->payload);
                self::assertSame($bad, $violation->getInvalidValue());

                continue;
            }
            $expectedPayload = null === $violation->getCode() ? ['severity' => 'warning'] : null;
            self::assertSame($expectedPayload, $violation->getConstraint()->payload);
        }
        self::assertSame(['[shape].geometries[2].rings[1].points[3]', '[shape].geometries[3]'], $paths);
        self::assertSame(['source' => 'consumer'], $constraint->payload);
    }

    /**
     * Verify null and leaf have no components.
     */
    public function testNullAndLeafHaveNoComponents(): void
    {
        $service = Validation::createValidator();
        self::assertCount(0, $service->validate(null, new SpatialAssert\Components()));
        self::assertCount(0, $service->validate(new Point(), new SpatialAssert\Components()));
    }

    #[DataProvider('permittedMembers')]
    /**
     * Verify permitted members keep their specialized route.
     *
     * @param string $parentType parent type
     * @param string $memberType member type
     * @param string $path       property path for the diagnostic
     */
    public function testPermittedMembersKeepTheirSpecializedRoute(
        string $parentType,
        string $memberType,
        string $path,
    ): void {
        if (\in_array($memberType, ['LineString', 'CircularString'], true)) {
            $member = $this->aggregate($memberType, [$this->invalidPoint(), new Point(1, 1)]);
            $parent = $this->aggregate($parentType, [$member]);
            $violations = Validation::createValidator()->validate($parent, new SpatialAssert\Components());
            self::assertCount(2, $violations);
            self::assertSame(SpatialAssert\FiniteCoordinates::INVALID_ERROR, $violations->get(0)->getCode());
            self::assertSame($path.'.points[0]', $violations->get(0)->getPropertyPath());

            return;
        }
        $member = $this->aggregate($memberType, [LineStringValidationTest::line([])]);
        $parent = $this->aggregate($parentType, [$member]);
        $violations = Validation::createValidator()->validate($parent, new SpatialAssert\Components());
        self::assertCount('Triangle' === $memberType ? 2 : 1, $violations);
        if (\in_array($memberType, ['Polygon', 'Triangle'], true)) {
            self::assertSame(SpatialAssert\Ring::EMPTY_ERROR, $violations->get(0)->getCode());
            self::assertNull($violations->get(0)->getConstraint()?->payload);
            self::assertSame($path.'.rings[0]', $violations->get(0)->getPropertyPath());

            return;
        }
        self::assertSame($memberType.' validation is not implemented yet.', $violations->get(0)->getMessage());
        self::assertSame($path, $violations->get(0)->getPropertyPath());
        self::assertSame(['severity' => 'warning'], $violations->get(0)->getConstraint()?->payload);
    }

    #[DataProvider('pointAggregates')]
    /**
     * Verify point members require both interface and type.
     *
     * @param string $type type
     */
    public function testPointMembersRequireBothInterfaceAndType(string $type): void
    {
        $fake = self::createStub(SpatialInterface::class);
        $fake->method('getType')->willReturn(GeometryTypeEnum::POINT);
        $wrongType = self::createStub(PointInterface::class);
        $wrongType->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        foreach ([$fake, $wrongType] as $candidate) {
            $candidate->method('getFamily')->willReturn(SpatialModelEnum::GEOMETRY);
            $candidate->method('getDimension')->willReturn(CoordinateDimensionEnum::XY);
            $candidate->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        }
        $value = $this->aggregate($type, [$fake, $wrongType, 'not spatial']);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Components());
        self::assertCount(3, $violations);
        foreach ($violations as $index => $violation) {
            self::assertSame(SpatialAssert\Components::TYPE_ERROR, $violation->getCode());
            self::assertSame('points['.$index.']', $violation->getPropertyPath());
        }
    }

    /**
     * Verify service converts unsupported value into type violation.
     */
    public function testServiceConvertsUnsupportedValueIntoTypeViolation(): void
    {
        $violations = Validation::createValidator()->validate('', new SpatialAssert\Components());
        self::assertCount(1, $violations);
        self::assertSame('This value should be of type {{ type }}.', $violations->get(0)->getMessageTemplate());
    }

    /**
     * Verify shared aggregate is validated along each independent path.
     */
    public function testSharedAggregateIsValidatedAlongEachIndependentPath(): void
    {
        $line = $this->aggregate('LineString', [$this->invalidPoint()]);
        $value = $this->aggregate('GeometryCollection', [$line, $line]);
        $violations = Validation::createValidator()->validate($value, new SpatialAssert\Components());
        $paths = [];
        foreach ($violations as $violation) {
            self::assertNotSame(SpatialAssert\Components::CYCLE_ERROR, $violation->getCode());
            if (SpatialAssert\FiniteCoordinates::INVALID_ERROR === $violation->getCode()) {
                $paths[] = $violation->getPropertyPath();
            }
        }
        self::assertSame(['geometries[0].points[0]', 'geometries[1].points[0]'], $paths);
    }

    /**
     * Verify unsupported value throws directly.
     */
    public function testUnsupportedValueThrowsDirectly(): void
    {
        $this->expectException(UnexpectedValueException::class);
        new SpatialAssert\ComponentsValidator()->validate('', new SpatialAssert\Components());
    }

    /**
     * Verify wrong constraint takes precedence.
     */
    public function testWrongConstraintTakesPrecedence(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        new SpatialAssert\ComponentsValidator()->validate(null, new Assert\NotNull());
    }

    /**
     * Verify wrong member type still reports every metadata mismatch.
     */
    public function testWrongMemberTypeStillReportsEveryMetadataMismatch(): void
    {
        $line = $this->aggregate(
            'LineString',
            [],
            CoordinateDimensionEnum::XYZ,
            SpatialModelEnum::GEOGRAPHY,
            SpatialReference::epsg(4326)
        );
        $parent = $this->aggregate('MultiPolygon', [$line]);
        $violations = Validation::createValidator()->validate($parent, new SpatialAssert\Components());
        $codes = [];
        foreach ($violations as $violation) {
            $codes[] = $violation->getCode();
            self::assertSame('polygons[0]', $violation->getPropertyPath());
        }
        self::assertSame([
            SpatialAssert\Components::FAMILY_ERROR, SpatialAssert\Components::DIMENSION_ERROR,
            SpatialAssert\Components::REFERENCE_ERROR, SpatialAssert\Components::TYPE_ERROR,
        ], $codes);
    }

    /**
     * @param string                  $type      type
     * @param array|\Closure          $members   spatial members to inspect
     * @param CoordinateDimensionEnum $dimension dimension
     * @param SpatialModelEnum        $family    family
     * @param SpatialReference|null   $reference reference
     *
     * @phpstan-param array<mixed>|\Closure(): array<mixed> $members
     */
    private function aggregate(
        string $type,
        array|\Closure $members = [],
        CoordinateDimensionEnum $dimension = CoordinateDimensionEnum::XY,
        SpatialModelEnum $family = SpatialModelEnum::GEOMETRY,
        ?SpatialReference $reference = null,
    ): SpatialInterface&Stub {
        $name = 'GeometryCollection' === $type ? 'Collection' : $type;

        /** @var class-string<SpatialInterface> $interface */
        $interface = 'LongitudeOne\SpatialTypes\Interfaces\\'.$name.'Interface';
        $value = self::createStub($interface);
        $value->method('getType')->willReturn(GeometryTypeEnum::from($type));
        $value->method('getFamily')->willReturn($family);
        $value->method('getDimension')->willReturn($dimension);
        $value->method('getSpatialReference')->willReturn($reference ?? SpatialReference::fromSrid(0));
        $getter = match ($type) {
            'Polygon', 'Triangle' => 'getRings',
            'CompoundCurve' => 'getCurves',
            'PolyhedralSurface' => 'getPatches',
            'LineString', 'CircularString', 'MultiPoint' => 'getPoints',
            default => 'getElements',
        };
        if ($members instanceof \Closure) {
            $value->method($getter)->willReturnCallback($members);

            return $value;
        }
        $value->method($getter)->willReturn($members);

        return $value;
    }

    /**
     * Invalid point.
     */
    private function invalidPoint(): PointInterface
    {
        $point = self::createStub(PointInterface::class);
        $point->method('getType')->willReturn(GeometryTypeEnum::POINT);
        $point->method('getFamily')->willReturn(SpatialModelEnum::GEOMETRY);
        $point->method('getDimension')->willReturn(CoordinateDimensionEnum::XY);
        $point->method('getSpatialReference')->willReturn(SpatialReference::fromSrid(0));
        $point->method('getX')->willReturn(\INF);
        $point->method('getY')->willReturn(0.0);
        $point->method('getCoordinates')->willReturn(Coordinates::xy(\INF, 0.0));

        return $point;
    }
}
