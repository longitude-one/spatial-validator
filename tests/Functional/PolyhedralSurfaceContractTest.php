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
use LongitudeOne\SpatialTypes\Interfaces\PolyhedralSurfaceInterface;
use LongitudeOne\SpatialValidator\Internal\PolyhedralIntersections;
use LongitudeOne\SpatialValidator\Internal\PolyhedralTopology;
use LongitudeOne\SpatialValidator\Validator\Constraints as Rules;
use PHPUnit\Framework\TestCase;
use stdClass as GenericObject;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

/** @internal
 * @coversNothing
 */
final class PolyhedralSurfaceContractTest extends TestCase
{
    public function testClosedNullAndUnsupportedValues(): void
    {
        (new Rules\ClosedPolyhedralSurfaceValidator())->validate(null, new Rules\ClosedPolyhedralSurface());
        $validator = Validation::createValidator();
        self::assertCount(0, $validator->validate(null, new Rules\ClosedPolyhedralSurface()));
        foreach (['', 'POLYHEDRALSURFACE EMPTY', 42, new GenericObject()] as $value) {
            self::assertCount(1, $validator->validate($value, new Rules\ClosedPolyhedralSurface()));
        }
        $this->expectException(UnexpectedValueException::class);
        (new Rules\ClosedPolyhedralSurfaceValidator())->validate('', new Rules\ClosedPolyhedralSurface());
    }

    public function testClosedRejectsWrongDeclaredType(): void
    {
        $candidate = self::createStub(PolyhedralSurfaceInterface::class);
        $candidate->method('getType')->willReturn(GeometryTypeEnum::POLYGON);
        $this->expectException(UnexpectedValueException::class);
        (new Rules\ClosedPolyhedralSurfaceValidator())->validate($candidate, new Rules\ClosedPolyhedralSurface());
    }

    public function testClosedWrongConstraintPrecedesNullHandling(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        (new Rules\ClosedPolyhedralSurfaceValidator())->validate(null, new Rules\Spatial());
    }

    public function testClosurePreservesGroupsPayloadRootAndPreviousUnrelatedViolation(): void
    {
        $surface = PolyhedralSurfaceValidationTest::surface([]);
        $root = ['first' => '', 'surface' => $surface];
        $constraint = new Rules\ClosedPolyhedralSurface(groups: ['custom'], payload: ['custom' => true]);
        $violations = Validation::createValidator()->validate($root, new Assert\Collection(fields: [
            'first' => new Assert\NotBlank(groups: ['custom']), 'surface' => $constraint,
        ], groups: ['custom']), ['custom']);
        self::assertCount(2, $violations);
        $last = $violations->get(1);
        self::assertSame(Rules\ClosedPolyhedralSurface::INVALID_ERROR, $last->getCode());
        self::assertSame('[surface]', $last->getPropertyPath());
        self::assertSame(['custom' => true], $last->getConstraint()?->payload);
        self::assertSame(['custom'], $last->getConstraint()->groups);
        self::assertSame($root, $last->getRoot());
    }

    public function testParallelAndDegeneratePatchPlanesAndZeroVolumeEnvelope(): void
    {
        $face = [[[0.0, 0.0, 0.0], [4.0, 0.0, 0.0], [0.0, 4.0, 0.0], [0.0, 0.0, 0.0]]];
        $other = array_values(array_map(static fn (array $ring): array => array_map(
            static fn (array $point): array => [$point[0], $point[1], 2.0],
            $ring
        ), $face));
        self::assertFalse(PolyhedralIntersections::intersects($face, $other));
        self::assertFalse(PolyhedralIntersections::intersects([[[0.0, 0.0, 0.0], [1.0, 0.0, 0.0]]], $face));
        self::assertFalse(PolyhedralTopology::closed([$face, [array_reverse($face[0])]]));
    }
}
