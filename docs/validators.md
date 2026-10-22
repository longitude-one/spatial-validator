# Validators

## Generic validation

Call `$validator->validate($value, new Spatial())` with Symfony Validator and
`LongitudeOne\SpatialValidator\Validator\Constraints\Spatial`.

| Constraint | Description |
| --- | --- |
| [Spatial](../lib/Validator/Constraints/Spatial.php) · [Validator](../lib/Validator/Constraints/SpatialValidator.php) | Validates any spatial interface |

## Specialized validation

Point validates coordinates and LineString composes point and line rules.
Aggregate validators also validate their components recursively.
Other type-specific geometric rules still produce an unavailable-validation warning.

| Constraint | Description |
| --- | --- |
| [BRepSolid](../lib/Validator/Constraints/BRepSolid.php) · [Validator](../lib/Validator/Constraints/BRepSolidValidator.php) | Reports unavailable BRepSolid validation |
| [Circle](../lib/Validator/Constraints/Circle.php) · [Validator](../lib/Validator/Constraints/CircleValidator.php) | Reports unavailable Circle validation |
| [CircularString](../lib/Validator/Constraints/CircularString.php) · [Validator](../lib/Validator/Constraints/CircularStringValidator.php) | Reports unavailable CircularString validation |
| [Clothoid](../lib/Validator/Constraints/Clothoid.php) · [Validator](../lib/Validator/Constraints/ClothoidValidator.php) | Reports unavailable Clothoid validation |
| [CompoundCurve](../lib/Validator/Constraints/CompoundCurve.php) · [Validator](../lib/Validator/Constraints/CompoundCurveValidator.php) | Reports unavailable CompoundCurve validation |
| [CompoundSurface](../lib/Validator/Constraints/CompoundSurface.php) · [Validator](../lib/Validator/Constraints/CompoundSurfaceValidator.php) | Reports unavailable CompoundSurface validation |
| [Curve](../lib/Validator/Constraints/Curve.php) · [Validator](../lib/Validator/Constraints/CurveValidator.php) | Reports unavailable Curve validation |
| [CurvePolygon](../lib/Validator/Constraints/CurvePolygon.php) · [Validator](../lib/Validator/Constraints/CurvePolygonValidator.php) | Reports unavailable CurvePolygon validation |
| [EllipticalCurve](../lib/Validator/Constraints/EllipticalCurve.php) · [Validator](../lib/Validator/Constraints/EllipticalCurveValidator.php) | Reports unavailable EllipticalCurve validation |
| [GeodesicString](../lib/Validator/Constraints/GeodesicString.php) · [Validator](../lib/Validator/Constraints/GeodesicStringValidator.php) | Reports unavailable GeodesicString validation |
| [Geometry](../lib/Validator/Constraints/Geometry.php) · [Validator](../lib/Validator/Constraints/GeometryValidator.php) | Reports unavailable Geometry validation |
| [GeometryCollection](../lib/Validator/Constraints/GeometryCollection.php) · [Validator](../lib/Validator/Constraints/GeometryCollectionValidator.php) | Reports unavailable GeometryCollection validation |
| [LineString](../lib/Validator/Constraints/LineString.php) · [Validator](../lib/Validator/Constraints/LineStringValidator.php) | Validates line points, cardinality and consecutive positions |
| [MultiCurve](../lib/Validator/Constraints/MultiCurve.php) · [Validator](../lib/Validator/Constraints/MultiCurveValidator.php) | Reports unavailable MultiCurve validation |
| [MultiLineString](../lib/Validator/Constraints/MultiLineString.php) · [Validator](../lib/Validator/Constraints/MultiLineStringValidator.php) | Reports unavailable MultiLineString validation |
| [MultiPoint](../lib/Validator/Constraints/MultiPoint.php) · [Validator](../lib/Validator/Constraints/MultiPointValidator.php) | Reports unavailable MultiPoint validation |
| [MultiPolygon](../lib/Validator/Constraints/MultiPolygon.php) · [Validator](../lib/Validator/Constraints/MultiPolygonValidator.php) | Reports unavailable MultiPolygon validation |
| [MultiSurface](../lib/Validator/Constraints/MultiSurface.php) · [Validator](../lib/Validator/Constraints/MultiSurfaceValidator.php) | Reports unavailable MultiSurface validation |
| [NURBSCurve](../lib/Validator/Constraints/NURBSCurve.php) · [Validator](../lib/Validator/Constraints/NURBSCurveValidator.php) | Reports unavailable NURBSCurve validation |
| [Point](../lib/Validator/Constraints/Point.php) · [Validator](../lib/Validator/Constraints/PointValidator.php) | Validates a point |
| [Polygon](../lib/Validator/Constraints/Polygon.php) · [Validator](../lib/Validator/Constraints/PolygonValidator.php) | Reports unavailable Polygon validation |
| [PolyhedralSurface](../lib/Validator/Constraints/PolyhedralSurface.php) · [Validator](../lib/Validator/Constraints/PolyhedralSurfaceValidator.php) | Reports unavailable PolyhedralSurface validation |
| [Solid](../lib/Validator/Constraints/Solid.php) · [Validator](../lib/Validator/Constraints/SolidValidator.php) | Reports unavailable Solid validation |
| [SpiralCurve](../lib/Validator/Constraints/SpiralCurve.php) · [Validator](../lib/Validator/Constraints/SpiralCurveValidator.php) | Reports unavailable SpiralCurve validation |
| [Surface](../lib/Validator/Constraints/Surface.php) · [Validator](../lib/Validator/Constraints/SurfaceValidator.php) | Reports unavailable Surface validation |
| [TIN](../lib/Validator/Constraints/TIN.php) · [Validator](../lib/Validator/Constraints/TINValidator.php) | Reports unavailable TIN validation |
| [Triangle](../lib/Validator/Constraints/Triangle.php) · [Validator](../lib/Validator/Constraints/TriangleValidator.php) | Reports unavailable Triangle validation |

## Coordinate rules

These reusable constraints accept PointInterface values and preserve Symfony groups and payload.

| Constraint | Description |
| --- | --- |
| [CoordinateLayout](../lib/Validator/Constraints/CoordinateLayout.php) · [Validator](../lib/Validator/Constraints/CoordinateLayoutValidator.php) | Checks declared ordinates and EMPTY consistency |
| [FiniteCoordinates](../lib/Validator/Constraints/FiniteCoordinates.php) · [Validator](../lib/Validator/Constraints/FiniteCoordinatesValidator.php) | Checks finite values, including M |
| [GeographyBounds](../lib/Validator/Constraints/GeographyBounds.php) · [Validator](../lib/Validator/Constraints/GeographyBoundsValidator.php) | Checks Geography longitude and latitude bounds |

## Component rules

Aggregate validators compose `Components`; it can also be applied directly to check membership and delegate
component validation. It preserves root, outer property path and active group. Its payload defaults to null;
delegated constraints retain their own payloads and codes. It does not validate the parent geometry itself.

| Constraint | Description |
| --- | --- |
| [Components](../lib/Validator/Constraints/Components.php) · [Validator](../lib/Validator/Constraints/ComponentsValidator.php) | Checks component types, metadata and cycles; delegates geometry rules |

| Parent | Diagnostic segment | Permitted members |
| --- | --- | --- |
| GeometryCollection | `geometries[index]` | Any spatial type |
| LineString, CircularString, MultiPoint | `points[index]` | Point |
| MultiLineString | `lineStrings[index]` | LineString |
| MultiPolygon | `polygons[index]` | Polygon, including Triangle |
| Polygon, Triangle | `rings[index]` | LineString ring candidates; exterior at index 0 |
| CompoundCurve | `curves[index]` | LineString or CircularString |
| PolyhedralSurface | `patches[index]` | Polygon, including Triangle |

Indexes are zero-based positions. A nested diagnostic can be
`geometries[2].rings[1].points[3]`; any outer Symfony prefix is retained.
Each member must match its parent's Geometry/Geography family, exact XY/XYZ/XYM/XYZM variant and complete
spatial reference (authority and identifier). No CRS lookup, axis verification or conversion occurs.

| Components constant | Code |
| --- | --- |
| `TYPE_ERROR` | `components-type` |
| `FAMILY_ERROR` | `components-family` |
| `DIMENSION_ERROR` | `components-dimension` |
| `REFERENCE_ERROR` | `components-reference` |
| `CYCLE_ERROR` | `components-cycle` |

Direct and indirect cycles produce an error at the cyclic reference and stop that branch; other branches
continue. A shared object reached along independent paths is validated at each path.
Members are visited even when the parent reports EMPTY. Valid EMPTY members in GeometryCollection, MultiPoint
and MultiLineString are retained; this does not approve EMPTY Points inside lines or establish the remaining
parent geometry rules. General collections may mix geometry types and topological dimensions.

## Line rules

These constraints accept LineStringInterface with declared type LineString. Only null is ignored; strings are
rejected. `MinimumPointCount(limit: 2)` always admits an empty point list; its limit must be positive.
`LineString` composes the first three with `Components`, without requiring closure or simplicity.

| Constraint | Description |
| --- | --- |
| [MinimumPointCount](../lib/Validator/Constraints/MinimumPointCount.php) · [Validator](../lib/Validator/Constraints/MinimumPointCountValidator.php) | Checks minimum count, allowing EMPTY |
| [NonEmptyPoints](../lib/Validator/Constraints/NonEmptyPoints.php) · [Validator](../lib/Validator/Constraints/NonEmptyPointsValidator.php) | Rejects EMPTY Point members |
| [NoConsecutiveDuplicatePoints](../lib/Validator/Constraints/NoConsecutiveDuplicatePoints.php) · [Validator](../lib/Validator/Constraints/NoConsecutiveDuplicatePointsValidator.php) | Rejects consecutive equal spatial positions |
| [SimpleLineString](../lib/Validator/Constraints/SimpleLineString.php) · [Validator](../lib/Validator/Constraints/SimpleLineStringValidator.php) | Explicit Geometry simplicity in XY or XYZ; Geography warning |

| Constant | Code |
| --- | --- |
| `MinimumPointCount::INVALID_ERROR` | `minimumpointcount-invalid` |
| `NonEmptyPoints::INVALID_ERROR` | `nonemptypoints-invalid` |
| `NoConsecutiveDuplicatePoints::INVALID_ERROR` | `noconsecutiveduplicatepoints-invalid` |
| `NoConsecutiveDuplicatePoints::INPUT_ERROR` | `noconsecutiveduplicatepoints-input` |
| `SimpleLineString::INVALID_ERROR` | `simplelinestring-invalid` |
| `SimpleLineString::UNAVAILABLE_ERROR` | `simplelinestring-unavailable` |

EMPTY-member, invalid comparison-input and duplicate diagnostics use `points[index]`, with zero-based indexes;
cardinality and simplicity diagnostics apply to the line. Non-finite or missing ordinates are rejected before
comparison, including M, while M is excluded from equality and intersection calculations. The standalone
consecutive-position rule also rejects members that cannot supply a finite non-empty Point position.

Equality uses the approved coordinate formula and XYZ when Z exists, otherwise XY. Explicit simplicity uses
exact binary orientation/coplanarity signs and allows only neighbouring endpoint contacts (including closure).
For first/last segments, that exception requires their actual shared endpoint; approximate Q02 endpoint
equality never exempts an interior crossing or moves the contact.
This dimension-aware API follows the LongitudeOne policy: ISO-CD 5.1.8 defines XY ST_IsSimple while 5.1.9
separately defines ST_3DIsSimple. The new API does not claim the XY-only semantics of ST_IsSimple on XYZ objects.

Non-empty Geography simplicity remains unavailable; valid EMPTY Geography is simple. Warnings use a cloned
constraint and default warning payload; explicit payload is preserved. Other errors default to payload null.
See the [usage and coverage](../README.md#linestring-validity-and-explicit-simplicity).

## Linear-ring rules

Use `Ring` explicitly for a linear boundary. It rejects EMPTY, then composes `MinimumPointCount(limit: 4)`,
`FirstPointEqualsLastPoint` and `SimpleLineString`, reusing LineString/Point/component validity through the latter.
No Polygon integration or Spatial dispatch changes are included.

| Constraint | Description |
| --- | --- |
| [Ring](../lib/Validator/Constraints/Ring.php) · [Validator](../lib/Validator/Constraints/RingValidator.php) | Checks a non-empty, closed, simple linear ring with at least four positions |
| [FirstPointEqualsLastPoint](../lib/Validator/Constraints/FirstPointEqualsLastPoint.php) · [Validator](../lib/Validator/Constraints/FirstPointEqualsLastPointValidator.php) | Checks finite non-empty Geometry endpoints using XY/XYZ equality; Geography warning |

| Constant | Code |
| --- | --- |
| `Ring::EMPTY_ERROR` | `ring-empty` |
| `FirstPointEqualsLastPoint::INVALID_ERROR` | `firstpointequalslastpoint-invalid` |
| `FirstPointEqualsLastPoint::UNAVAILABLE_ERROR` | `firstpointequalslastpoint-unavailable` |

Other diagnostics retain the delegated constraints and codes. Cardinality, EMPTY and closure violations are on
the boundary; Point failures use `points[index]`. The original root, prefix and active group are preserved.
Default error payload is null. Explicit Ring groups/payload apply to elementary boundary/line constraints;
delegated Point diagnostics retain their own payloads.

Geometry closure compares XYZ when Z exists, otherwise XY; it ignores M and retains the approved coordinate
equality formula. This does not change the exact simplicity predicates or the actual-shared-endpoint exception.
A closed simple A,B,C,A is admitted; a bow-tie, retracing or ordinary adjacent duplicate is rejected.

Non-empty Geography closure and simplicity are explicitly unavailable, with separate warning diagnostics;
structural and Point errors still apply. Warnings use copied constraints with warning payload by default,
preserving explicit consumer payload and leaving shared instances unchanged. EMPTY is not a ring or closed.
`MinimumPointCount` continues to allow EMPTY independently.

Both constraints accept LineStringInterface with declared type LineString. They ignore only null and reject
strings. Standalone closure does not establish complete line/ring validity. CircularString candidates are
rejected by the input contract, without applying this four-position minimum to a circular full circle.

Sources: ISO/IEC CD 13249-3:201x(E), 2009-01-16 Committee Draft, 4.2.4–4.2.5 and 7.1.6–7.1.9;
[OGC 07-036, GML 3.2.1 §10.5.8](https://docs.ogc.org/is/07-036/07-036.pdf) for the linear-ring minimum.
The dimension-aware API follows the approved LongitudeOne policy, rather than redefining ISO's separate
XY and 3D closure/ring operations.

## Circular-curve rules

`CircularString` composes component/Point validity with the two structural constraints below.
`SimpleCircularString` is opt-in and first composes ordinary CircularString validity.

| Constraint | Description |
| --- | --- |
| [CircularPointCount](../lib/Validator/Constraints/CircularPointCount.php) · [Validator](../lib/Validator/Constraints/CircularPointCountValidator.php) | Checks EMPTY or an odd point count of at least three |
| [CircularArcPoints](../lib/Validator/Constraints/CircularArcPoints.php) · [Validator](../lib/Validator/Constraints/CircularArcPointsValidator.php) | Rejects EMPTY members and intermediate positions equal to either endpoint |
| [SimpleCircularString](../lib/Validator/Constraints/SimpleCircularString.php) · [Validator](../lib/Validator/Constraints/SimpleCircularStringValidator.php) | Checks actual circular arcs and segment degenerations for forbidden intersections |

| Constant | Code |
| --- | --- |
| `CircularPointCount::INVALID_ERROR` | `circularpointcount-invalid` |
| `CircularArcPoints::INVALID_ERROR` | `circulararcpoints-invalid` |
| `SimpleCircularString::INVALID_ERROR` | `simplecircularstring-invalid` |
| `SimpleCircularString::UNAVAILABLE_ERROR` | `simplecircularstring-unavailable` |

Cardinality errors remain on the curve; EMPTY/invalid Points use `points[index]`, defining-position errors use
`arcs[index]`, and forbidden pair interactions identify the second arc as `arcs[index]`.
Original root, path prefix and active group are preserved. Explicit consumer payload propagates to elementary
curve rules; delegated Point errors retain their own payload. Error payload defaults to null. Warning
constraints are copied, defaulting to `['severity' => 'warning']` while retaining explicit consumer payload.

Geometry uses XYZ when Z exists, otherwise XY; M is ignored in spatial predicates but must remain finite.
Q02 applies to defining-position equality; actual circles, sectors and intersections use supplied coordinates
without an added proximity tolerance. Collinear triples represent their endpoint segment, not two chords.
Full circles with exactly repeated endpoints have their center midway between start and intermediate points.

EMPTY passes ordinary validity and simplicity, but is not a circular ring. A full circle needs three defining
positions; no four-position linear-ring minimum applies. Ordinary curves may be open or non-simple.
`SimpleCircularString` detects true-arc crossings, interior tangencies and interval overlaps/retracing,
including between circles in distinct XYZ planes and between arcs and segment degenerations.

Non-empty Geography topology is unavailable. A full circle XYZ encoded A,B,A does not determine its plane:
a single circle is simple, but its pairs with other arcs report unavailable warnings. This explicit scope limit
was approved by Alexandre for #17; no arbitrary plane is selected. CompoundCurve interactions remain in #18.

Sources: ISO/IEC CD 13249-3:201x(E), Committee Draft 2009-01-16, 4.2.4, 4.2.6,
5.1.8–5.1.9 and 7.3.1 Description 6–11; dimension-aware behavior follows Q01–Q03 and the #17 plane decision.
