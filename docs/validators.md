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
| [GeometryCollection](../lib/Validator/Constraints/GeometryCollection.php) · [Validator](../lib/Validator/Constraints/GeometryCollectionValidator.php) | Composes heterogeneous member validity |
| [LineString](../lib/Validator/Constraints/LineString.php) · [Validator](../lib/Validator/Constraints/LineStringValidator.php) | Validates line points, cardinality and consecutive positions |
| [MultiCurve](../lib/Validator/Constraints/MultiCurve.php) · [Validator](../lib/Validator/Constraints/MultiCurveValidator.php) | Reports unavailable MultiCurve validation |
| [MultiLineString](../lib/Validator/Constraints/MultiLineString.php) · [Validator](../lib/Validator/Constraints/MultiLineStringValidator.php) | Composes LineString member validity |
| [MultiPoint](../lib/Validator/Constraints/MultiPoint.php) · [Validator](../lib/Validator/Constraints/MultiPointValidator.php) | Composes Point member validity |
| [MultiPolygon](../lib/Validator/Constraints/MultiPolygon.php) · [Validator](../lib/Validator/Constraints/MultiPolygonValidator.php) | Reports unavailable MultiPolygon validation |
| [MultiSurface](../lib/Validator/Constraints/MultiSurface.php) · [Validator](../lib/Validator/Constraints/MultiSurfaceValidator.php) | Reports unavailable MultiSurface validation |
| [NURBSCurve](../lib/Validator/Constraints/NURBSCurve.php) · [Validator](../lib/Validator/Constraints/NURBSCurveValidator.php) | Reports unavailable NURBSCurve validation |
| [Point](../lib/Validator/Constraints/Point.php) · [Validator](../lib/Validator/Constraints/PointValidator.php) | Validates a point |
| [Polygon](../lib/Validator/Constraints/Polygon.php) · [Validator](../lib/Validator/Constraints/PolygonValidator.php) | Validates complete Polygon structure, topology and relative orientation |
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
and MultiLineString are retained; this does not approve EMPTY Points inside lines or establish unrelated
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
positions; no four-position linear-ring minimum applies. Ordinary curves may be open or non-simple. Repeated arc
endpoints outside the single global closure are
forbidden, including tangent full circles that revisit the boundary at an interior join.
`SimpleCircularString` detects true-arc crossings, interior tangencies and interval overlaps/retracing,
including between circles in distinct XYZ planes and between arcs and segment degenerations.

Non-empty Geography topology is unavailable. A full circle XYZ encoded A,B,A does not determine its plane:
a single circle is simple, but its pairs with other arcs report unavailable warnings. This explicit scope limit
was approved by Alexandre for #17; no arbitrary plane is selected.
CompoundCurve interactions are covered by the [compound-curve rules](#compound-curve-rules).

Sources: ISO/IEC CD 13249-3:201x(E), Committee Draft 2009-01-16, 4.2.4, 4.2.6,
5.1.8–5.1.9 and 7.3.1 Description 6–11; dimension-aware behavior follows Q01–Q03 and the #17 plane decision.

## Compound-curve rules

| Constraint | Contract |
| --- | --- |
| [CompoundCurve](../lib/Validator/Constraints/CompoundCurve.php) | Composes recursive `Components`, rejects EMPTY members and checks consecutive endpoint continuity |
| [SimpleCompoundCurve](../lib/Validator/Constraints/SimpleCompoundCurve.php) | First validates the compound curve, then checks the whole curve's actual arcs and linear segments |

Both accept `CompoundCurveInterface` with declared CompoundCurve type. PHP null is ignored; all strings,
including `''`, are rejected. Error payload defaults to null. Ordinary validity requires neither closure nor simplicity.
Zero components form a valid EMPTY curve, which also passes simplicity but is not a ring.

```php
use LongitudeOne\SpatialValidator\Validator\Constraints\CompoundCurve;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleCompoundCurve;

$validity = $validator->validate($curve, new CompoundCurve());
$simplicity = $validator->validate($curve, new SimpleCompoundCurve());
```

All members must be valid LineString or CircularString objects with the same family, coordinate variant and
complete spatial-reference identity. Continuity is inspected only after component validation succeeds.
Geometry joins use Q02 scaled finite-coordinate equality in XYZ when Z exists, otherwise XY, ignoring M.
Neither the component order nor coordinates are changed to close gaps.

Explicit simplicity covers line/line, line/arc and arc/arc interactions within and across members.
Allowed neighbouring endpoint joins and one global closure are permitted; crossings, interior tangencies,
retracing, overlaps and repeated interior positions fail. Exact predicates inspect the original positions:
Q02 equality does not exempt an actual interior intersection or snap approximately joined endpoints.
Individually simple members can therefore form a non-simple compound curve.

| Diagnostic | Code | Path |
| --- | --- | --- |
| EMPTY contributing member | `compoundcurve-empty-member` | `curves[index]` |
| Disconnected Geometry join | `compoundcurve-disconnected` | Following `curves[index]` |
| Unavailable Geography join | `compoundcurve-unavailable` | Following `curves[index]` |
| Intersection or repetition | `simplecompoundcurve-invalid` | `curves[index].arcs[part]` or `.segments[part]` |
| Unavailable topology | `simplecompoundcurve-unavailable` | Curve, or following part for an indeterminate arc pair |

Member failures retain their original constraint/code/payload and nested Point paths. Root, outer property path,
active group and explicit compound-rule payload are preserved. Warnings copy the constraint and use
`['severity' => 'warning']` by default while retaining explicit consumer payload; they remain violations.

Geography structural and Point checks continue, but joins and non-empty topology report unavailable warnings.
The CircularString engine's documented XYZ A,B,A plane limitation also applies to compound interactions:
an isolated full circle is simple, but interactions with another part report unavailable rather than choosing
an arbitrary plane. Independently provable repeated endpoints still fail. No Cartesian approximation,
densification, repair, CRS lookup or reprojection is performed.

Sources: ISO/IEC CD 13249-3:201x(E), Committee Draft dated 2009-01-16, clauses 4.2.7,
7.4.1 Description 3–10 and 7.4.3 → 16.1.6; dimension-aware joins follow the approved Q01–Q03 policy.

## Internal Polygon structure and supporting plane

The internal `PolygonStructure` constraint composes complete `Ring` validation at `rings[index]` and
shared parent/member metadata checks. Ring delegates Point rules once, preserving root, property path,
active group and elementary constraint identity. Explicit payload propagates to boundary rules;
Point diagnostics retain their own payload. Genuine EMPTY means no rings and an EMPTY flag; inconsistent
flags never hide stored boundaries. A non-empty value without a shell, an EMPTY shell with holes, and
EMPTY holes fail. All strings, including the empty string, follow the current unsupported-input contract.

A non-empty Geometry exterior must supply three non-collinear spatial positions. The supporting plane
uses existing exact binary orientation and coplanarity predicates, without determinant epsilon or
coordinate changes. Every exterior and interior position must lie in that same plane. XY/XYM use XY;
XYZ/XYZM use XYZ, including inclined and vertical planes. M remains subject to Point finiteness checks
but never defines a plane. Incompatible metadata or unusable positions suppress dependent plane checks,
while independent ring diagnostics continue.

| Internal diagnostic | Code | Path |
| --- | --- | --- |
| Missing shell or inconsistent EMPTY state | `polygonstructure-empty` | Polygon |
| Collinear exterior | `polygonstructure-degenerate` | `rings[0]` |
| Position outside the common supporting plane | `polygonstructure-plane` | `rings[i].points[j]` |
| Geography planarity/topology unavailable | `polygonstructure-unavailable` | Polygon |

Geography never uses Cartesian planarity or topology as a substitute. Non-empty values receive the
internal unavailable warning alongside Ring's available checks and geodetic limitations. Warning constraints
are copied, default to `['severity' => 'warning']` and retain explicit payload. Other structural diagnostics
default to null payload. Genuine EMPTY Geography requires no plane and produces no structural warning.

This internal foundation is consumed by public `Polygon`; it is not a public structural-only alternative.
A coplanar outside hole passes this internal stage but fails complete `Polygon` and `Spatial` validation.

References: ISO/IEC CD 13249-3:201x(E), Committee Draft dated 2009-01-16, clauses 4.2.9–4.2.10 and
8.3.1 Description 2–4 (linear exterior/interior boundaries and planar surface); OGC SFA 1.2.1, 6.1.11.1;
Alexandre's approved #19 composition and numerical decisions. Ring semantics and approved Geography
limitations remain those documented above. No vendor behavior defines these rules.

## Collection rules

All constraints below use the standard null/string input contract and accept `groups` and `payload`.
Ordinary collection constraints now default to null payload; only genuinely unavailable member/property checks
carry the warning default. Delegation retains root, nesting, member order and active group.

| Constraint | Contract | Diagnostics |
| --- | --- | --- |
| `GeometryCollection` | Any spatial member type | Member codes at `geometries[index]` |
| `MultiPoint` | Point interface and type, including valid EMPTY | Member codes at `points[index]` |
| `MultiLineString` | LineString interface and type, including valid EMPTY | Member codes at `lineStrings[index]` |
| `SimpleMultiPoint` | Distinct non-empty spatial positions | `simplemultipoint-invalid` at repeated Point |
| `SimpleMultiLineString` | Simple lines, boundary-only inter-line contacts | Member/pair errors at later line |
| `ClosedMultiLineString` | Non-EMPTY aggregate, every member closed | Aggregate/member closure errors |

All explicit properties compose ordinary validity first. Inter-line errors use `simplemultilinestring-invalid`
at the later `lineStrings[index]`; member simplicity retains SimpleLineString codes. An EMPTY aggregate closure
error uses `closedmultilinestring-invalid` at the aggregate. Each failing member closure retains
FirstPointEqualsLastPoint codes at `lineStrings[index]`.

```php
use LongitudeOne\SpatialValidator\Validator\Constraints as SpatialAssert;

$validity = $validator->validate($multiLine, new SpatialAssert\MultiLineString());
$simplicity = $validator->validate($multiLine, new SpatialAssert\SimpleMultiLineString());
$closure = $validator->validate($multiLine, new SpatialAssert\ClosedMultiLineString());
```

EMPTY members are retained and validated for strict family, exact XY/XYZ/XYM/XYZM and reference authority/identifier.
An aggregate represents EMPTY when it has no members or every member represents the empty set. Validation never
skips members because of the parent EMPTY flag. No model objects are normalized or modified.

Simplicity skips EMPTY members' absent positions. Repeated non-consecutive Points fail only explicit simplicity.
Geometry position equality uses the approved scaled finite-coordinate comparison, including Z and ignoring M.
Line contacts use exact supplied-coordinate intersection predicates, without determinant epsilon, snapping or
proximity merging. A closed line has empty boundary, so even contact at its closing vertex is forbidden between
members. Overlaps, crossings and endpoints in another line's interior fail. Pair errors identify the earlier
member through `{{ other }}`. A shared endpoint must be the actual contact; approximate equality never moves it.

An EMPTY MultiLineString is not closed. In a mixed aggregate, each EMPTY member fails closure because it has no
endpoints. Closed members may still form a non-simple aggregate; simplicity and closure remain independent.
Geography member simplicity and aggregate simplicity emit their respective warnings, with aggregate codes
`simplemultipoint-unavailable` or `simplemultilinestring-unavailable`; member closure uses
`firstpointequalslastpoint-unavailable`. Each warning clones its constraint, retains explicit payload and defaults
to `['severity' => 'warning']`. Entirely EMPTY aggregates need no geodetic simplicity calculation.

Normative traceability: ISO/IEC CD 13249-3:201x(E), Committee Draft dated 2009-01-16, 9.2.1 Description 7 and
9.4.1 Description 3 define well-formedness by members; 9.3.1 Description 4 defines boundary-only simple curve
aggregates; 9.3.3–9.3.4 return false for EMPTY closure. The approved #22 contract requires every member closed and
selects XYZ behavior when Z is present. This library does not claim equivalence to every SQL/MM operation.

The approved EMPTY-member policy distinguishes geometric validity from SQL/MM construction/mutation/cast paths:
9.2.3 → 16.1.8 → 16.1.6 and 9.4.3 → 16.1.10 → 16.1.6 reject EMPTY elements in those operations. Their restrictions
are not applied as a blanket geometric membership prohibition. OGC SFA 1.2.1 6.1.5 and 6.1.8–6.1.9 have no explicit
typed EMPTY-member prohibition. The current validation API validates spatial objects, not those SQL routines.

General collection members can intersect and have different topological dimensions. Available member validity
is still enforced: Polygon members receive the complete Polygon contract, including hole topology.
General collection validity adds no mandatory cross-member simplicity.

## Polygon rules

Apply `Polygon` to `PolygonInterface` with declared Polygon type, or use generic `Spatial`:

```php
use LongitudeOne\SpatialValidator\Validator\Constraints\Polygon;

$violations = $validator->validate($polygon, new Polygon());
```

The constructor accepts only Symfony `groups` and `payload`. Null is ignored; all strings and unsupported
values are rejected. Genuine EMPTY is valid. Triangle remains a distinct declared type and interface.
Structure, metadata, every Ring and the common supporting plane are checked first. Invalid structure
prevents dependent Cartesian topology calculations; its original codes and nested paths remain available.

| Relationship | Result |
| --- | --- |
| Hole strictly inside exterior, holes mutually outside | Valid |
| Isolated tangent contacts preserving connected interior | Valid |
| Hole outside exterior, including a smaller or surrounding hole | Error |
| Crossing or shared boundary interval | Error |
| Overlapping or nested holes | Error |
| Hole/contact chain separating interior, including a cycle around an island | Error |
| Hole with exterior's orientation, viewed from the same side | Warning |

Several boundaries may meet at the same isolated point without automatically disconnecting the interior.
Cut lines, spikes and punctures already fail Ring composition. Orientation is relative to the exterior:
there is no universal clockwise shell requirement. Reversing every ring preserves the outcome, and an
orientation warning does not suppress an independent topology error.

| Polygon constant | Code | Path | Default payload |
| --- | --- | --- | --- |
| `INTERSECTION_ERROR` | `polygon-intersection` | `rings[index]` | null |
| `OUTSIDE_ERROR` | `polygon-outside` | `rings[index]` | null |
| `NESTED_ERROR` | `polygon-nested` | `rings[index]` | null |
| `DISCONNECTED_ERROR` | `polygon-disconnected` | `rings[index]` | null |
| `ORIENTATION_ERROR` | `polygon-orientation` | `rings[index]` | `['severity' => 'warning']` |
| `UNAVAILABLE_ERROR` | `polygon-unavailable` | Polygon | `['severity' => 'warning']` |

Warning constraints are copied; explicit Polygon payload is preserved and the original is unchanged.
Generic Spatial dispatch retains specialized payload defaults. Root, outer path and active group survive
all delegation. Non-empty Geography receives explicit topology/orientation warnings alongside structural,
Point and Ring diagnostics; Cartesian calculations never establish geodetic validity.

Geometry uses an injective coordinate projection of the actual supporting plane. XYZ/XYZM therefore
include Z even on inclined/vertical planes; XY/XYM use XY and M never participates spatially. Segment
intersections, signed areas and winding tests use exact signs over supplied finite binary coordinates.
Boundary intervals are split at contacts and sampled at exact arithmetic midpoints, without converting
those midpoints back to floats. Near contacts stay distinct; no area-only containment test, determinant
epsilon, proximity policy, coordinate movement or repair is introduced.

Ring closure still uses Q02 equality. When supplied first/last spatial positions differ within that rule,
Polygon topology keeps both and traverses the boundary cyclically, including their exact closing interval.
That interval is checked for self-intersection and all inter-ring relationships. No endpoint is replaced,
and a real crossing is never exempted by approximate closure. Standalone Ring checks only its supplied
LineString segments; Polygon additionally checks this cyclic surface-boundary interpretation.

Sources: ISO/IEC CD 13249-3:201x(E), Committee Draft dated 2009-01-16, 4.2.9–4.2.10 (planar closed
surface, non-crossing boundaries, no cut lines/spikes/punctures, connected interior); OGC SFA 1.2.1,
6.1.11.1 (isolated tangent contacts and relative orientation). Orientation warning severity and dimension-aware
Geometry calculations follow Alexandre's approved #20 and Q01–Q03/Q09 decisions.
