# Spatial validation rule catalogue

Design reference for [Story #8](https://github.com/longitude-one/spatial-validator/issues/8),
part of [Epic #7](https://github.com/longitude-one/spatial-validator/issues/7).
English version of the French document dated 21 October 2026. Approved decisions and remaining details are separated below.
This file does not change any runtime contract, algorithm, diagnostic or behavior.

## 1. Scope and method

The catalogue separates four categories:

| Category | Question evaluated | Consequence |
| --- | --- | --- |
| Validity | Does the object satisfy its type definition? | Compose the requirements for the type and its components. |
| Simplicity | Are there prohibited intersections or tangencies? | Do not require simplicity for every valid curve. |
| Closure | Do the endpoints coincide in the selected space? | An open curve can be valid. |
| Business restriction | Does the application require more? | Explicit constraint, never an implicit ISO requirement. |

A topologically closed set includes its boundary; this does not mean that a curve returns to its starting point
or that a polyhedral surface encloses a solid.
Rules are design requirements, not future PHP class names or violation codes.
Scenarios describe abstract candidates; some cannot be constructed using the installed model.

### Inventory revisions

| Item | Inspected revision |
| --- | --- |
| `spatial-validator` | `fca0ef88424a862deda7f7d2e8d7c8deafe58c16` |
| Installed `spatial-types`, `1.0.0-beta` | `ceaee5d142c77fc3922ab50c762fa6648fea0e30` |
| Installed `spatial-core`, `1.3.0` | `1289582288e8954243de5ccb1f28ab7e78f4e9a4` |

References relative to `lib/` and `tests/` refer to the first revision.
References under `vendor/longitude-one/` refer to the two installed revisions above;
the local `composer.lock` is not a published version contract.
The installed model still includes historical assertions: its guarantees do not establish the guarantees
that will remain after their removal in the extraction Epic.
A third-party implementation of `SpatialInterface` does not benefit from these guarantees.

## 2. Sources and authority

| Code | Document and status | Inspected clauses and use |
| --- | --- | --- |
| ISO-CD | ISO/IEC CD 13249-3:201x(E), 2009-01-16, Committee Draft | Available primary source; not final. |
| ISO-CD/base | Same document | 4.2.1, 4.2.2, 5.1.7–5.1.10: dimensions, equality, states. |
| ISO-CD/curves | Same document | 4.2.3–4.2.7, 7.1.6–7.1.9, 7.2.1, 7.3.1, 7.4.1. |
| ISO-CD/surfaces | Same document | 4.2.8–12; 8.3.1, 8.4.1, 8.5.1. |
| ISO-CD/collections | Same document | 4.2.14–4.2.19: members, simplicity and relationships. |
| OGC | OGC 06-103r4, SFA 1.2.1, Corrigendum | 6.1.6–6.1.7, 6.1.11–6.1.14: comparison of definitions. |
| OGC/GML | OGC 07-036, GML 3.2.1, 2007-08-27, final standard | 10.5.8: linear ring, minimum four positions. |
| ISO 19107 | Normative reference in ISO-CD, clause 2 | Spatial schema; source text not inspected here. |
| ISO 19111 | Normative reference in ISO-CD, clause 2 | Reference systems; source text not inspected here. |
| ISO 19136 | Normative reference in ISO-CD, clause 2 | GML; GML syntax validation is outside this Epic. |

ISO-CD was read in the private PDF supplied with the `longitudeone-spatial-standards` skill.
OGC was read in its local transcription; the [public OGC document](https://portal.ogc.org/files/?artifact_id=25355)
provides access to the original. Private extracts are not redistributed.
The editions and detailed clauses of the three ISO 191xx references must still be checked before reaching
a conclusion that depends exclusively on them. Their absence does not turn an assumption into a requirement.

Below: **N** = requirement or definition found in the cited source; **H** = observed historical behavior;
**L** = explicitly received LongitudeOne decision; **P** = proposal requiring approval.
Notes, examples and informative annexes are not used as independent obligations.
No general SQL/MM conformance is claimed. Database behavior is not a normative source.

## 3. Type and capability matrices

### Available variants

`G` = Geometry; `A` = Geography. `Yes` means that a class exists, not that validation is complete.

| Type | G XY | G XYZ | G XYM | G XYZM | A XY | A XYZ | A XYM | A XYZM |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Point | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| LineString | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| CircularString | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| CompoundCurve | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| Polygon | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| Triangle | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| MultiPoint | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| MultiLineString | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| MultiPolygon | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| GeometryCollection / GeographyCollection | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes |
| PolyhedralSurface | Absent | Yes | Absent | Yes | Absent | Yes | Absent | Yes |

Sources: `vendor/longitude-one/spatial-types/lib/Types/Dimension{2,3z,3m,4zm}/`
and `Implementation/SpatialTypeImplementationStatus.php` in the same library.
TIN, CurvePolygon, MultiCurve and MultiSurface are not implemented and remain outside scope.
A coordinate dimension of three does not make XYZ and XYM interchangeable.

### Rule applicability

Identifiers are defined in section 4. A component's rules also apply when it is contained in another object.
A simplicity rule is mandatory for validity only when required by the type definition,
notably for surface rings.

| Type | Common rules | Specific rules | Components to traverse |
| --- | --- | --- | --- |
| Point | V01, V02 | V03, V16 for Geography | None |
| LineString | V01, V02, C01–C04 | V04, B01; S01, F01 on request | Points |
| CircularString | V01, V02, C01–C04 | V05, B01; S01, F01 on request | Points and arcs |
| CompoundCurve | V01, V02, C01–C04 | V06; S01, F01 on request | Curves and joins |
| Polygon | V01, V02, C01–C04 | V07–V09, V15 | Exterior ring and holes |
| Triangle | Polygon rules | V10 | Exterior ring |
| MultiPoint | V01, V02, C01–C04 | S02 on request | Points |
| MultiLineString | V01, V02, C01–C04 | S03, F02 on request | Lines |
| MultiPolygon | V01, V02, C01–C04 | V11 | Polygons and their rings |
| Spatial collection | V01, V02, C01–C04 | Overlap not prohibited by default | Members |
| PolyhedralSurface | V01, V02, C01–C04 | V12–V14 | Patches and their rings |

V16 also applies to each Geography Point contained in a composite object.
Combine this table with the next two: no cell promises a geodetic algorithm.

| Check category | Geometry | Geography |
| --- | --- | --- |
| Type, family, dimension, reference, cardinality | Applicable | Applicable |
| Absence, EMPTY and recursion | Applicable | Applicable |
| Joins and closure | XYZ if Z exists, otherwise XY; M ignored | Semantics deferred, Q03 |
| Topology and planarity | Cartesian; space specified below | Outside initial coverage, Q03 |
| SRS-specific limits | No general check | No check specific to each SRS |
| Angular bounds in degrees | Not applicable | Latitude [−90, +90], longitude [−180, +180]; Q04 |

**L-GEO-01 — decision of 21 October 2026:** Alexandre requests structural checks first,
with geographic topology to be specified separately. This does not approve Cartesian calculations
as a geodetic substitute. Unavailable checks produce a warning under Q03.

| Operation | XY | XYZ | XYM | XYZM | M |
| --- | --- | --- | --- | --- | --- |
| Ordinary ISO-CD simplicity and closure | XY | XY | XY | XY | Not a spatial dimension |
| ISO-CD `3D` simplicity and closure | No Z contribution | XYZ | No Z contribution | XYZ | Spatially ignored |
| Plane of a PolyhedralSurface patch | Type absent | XYZ | Type absent | XYZ | Ignored historically |
| Polygon plane/topology | XY | XYZ | XY | XYZ | Not a spatial dimension |
| Dimension compatibility | XY | XYZ | XYM | XYZM | Distinguish its presence |

ISO-CD 5.1.8–5.1.9 and 7.1.6–7.1.9 distinguish ordinary and 3D operations.
**L-DIM-01 — decision of 21 October 2026:** Alexandre requests that the future generic Geometry validator
automatically use XYZ when Z is present, otherwise XY. XYM is therefore evaluated spatially in XY,
and XYZM in XYZ. M does not add a spatial dimension.
This LongitudeOne choice does not redefine ordinary ISO-CD operations or change existing constraints.
Numeric bounds, epsilon and projections are not inferred from this decision.
Geometric calculations always ignore M (Q01), unlike historical `equalsTo`, which also compares M.

## 4. Stable rule catalogue

Each entry states its category, conditions, source, coverage and reference scenarios.
Scenario outcomes concern the cited rule, not the object's overall validity.
Numbers are exact and finite unless stated otherwise. Each scenario applies to the relevant variants
in the matrix; for Geography, only structural checks have an initial outcome.

### V01 — Validity 01 — An EMPTY object is valid; `null` is not a spatial object

A geometry can be empty and valid. This does not permit invalid or EMPTY components
where the type definition prohibits them.

| Case | Expected outcome |
| --- | --- |
| Point with all ordinates of its variant null | Valid EMPTY Point under V03. |
| LineString with no points | Valid EMPTY LineString. |
| LineString with a single non-empty point | Invalid: at least two points unless EMPTY. |
| LineString containing an invalid Point | Invalid: each Point must be valid. |
| LineString containing an EMPTY Point | Invalid: its points must provide positions. |
| LineString with at least two valid, non-empty Points | Continue with the other line checks. |
| PHP value `null` or `''` | Ignored by the spatial constraint under L-INPUT-01. |

- **Rule:** accept a genuine EMPTY object; for an object containing components,
  validate those components and the type-specific rules. Point count alone does not establish line validity.
- **Sources:** ISO-CD 5.1.10(2a) accepts EMPTY; 7.2.1 Description 4 and 11 defines line cardinality;
  7.2.2 uses `ST_Points` (7.2.3), which calls `ST_CheckConsecDups` (16.1.7), then `ST_CheckNulls`
  (16.1.6). The latter procedure explicitly rejects null and EMPTY members.
- **Coverage: partial.** The installed model already rejects an EMPTY Point in a LineString.
  The generic validator and recursive validation remain to be built in C/D/F.
  Closure and the definition of a ring are addressed separately in F01.

#### L-INPUT-01 — Input decision 01 — Follow Symfony conventions

Alexandre's decision of 21 October 2026, specified in [Story #10][story10].
All constraint validators follow this contract:

| Case | Behavior |
| --- | --- |
| Wrong constraint supplied to the validator | Throw Symfony `UnexpectedTypeException`. |
| PHP value `null` or `''` | Return without violations; use `NotNull` or `NotBlank` to require a value. |
| Value incompatible with the expected spatial interface | Throw Symfony `UnexpectedValueException`. |
| Spatial object of the expected type, including EMPTY | Apply the corresponding geometric rules. |

The Symfony Validator service converts `UnexpectedValueException` into a type violation.
A direct call to the constraint validator lets this exception propagate.
The library adds no dedicated exceptions for these inputs.

Reference: [official Symfony documentation][symfony-input].
Story #10 implements this contract in the existing constraint validators.

[story10]: https://github.com/longitude-one/spatial-validator/issues/10
[symfony-input]: https://symfony.com/doc/7.4/validation/custom_constraint.html#creating-the-validator-itself

### V02 — Validity 02 — Coordinate finiteness and precision

- **Category:** numeric precondition, points of all non-empty objects.
- **LongitudeOne decision (Q04):** reject `INF`, `-INF` and `NAN` in X/Y and any present Z/M.
  Produce a violation for these values; do not equate them with `null` or EMPTY.
  Checking M is structural, even though geometric calculations ignore M.
- **H / coverage: partially covered.** `Internal/PlanarPatch::isValid` rejects non-finite XYZ.
  No equivalent general check or M check; no epsilon in the inspected algorithms.
- **Scenarios:** finite coordinates → pass this check; infinite X, NaN Z or infinite M → violation.
  A patch with infinite X is already rejected by the current code.
- **Affected Stories:** E, F–O; Q02, Q04. An unreliable arithmetic result does not establish validity.

### V16 — Validity 16 — Angular bounds of Geography points

- **Category:** coordinate validity, non-empty Geography Point and points in its composite objects.
- **LongitudeOne decision (Q04):** longitude X from −180 to +180 degrees and latitude Y from −90 to +90 degrees,
  bounds included. An out-of-range value produces a violation; no SRID-specific limit is added.
- **Existing checks: partially covered.** The model applies these bounds in
  `vendor/longitude-one/spatial-types/lib/Types/AbstractPoint.php`, methods `initializeLatitude`
  and `initializeLongitude`, at the revision in section 1. No dedicated constraint or test
  for these bounds exists in `spatial-validator` at the inventoried revision. This inventory imposes no compatibility.
- **Scenarios:** longitudes −180 and +180, latitudes −90 and +90 → accepted;
  longitude −180.000001 or +180.000001 → violation; latitude −90.000001 or +90.000001 → violation;
  EMPTY Geography Point → V03; Geometry Point X=700000 → no V16 violation.
  Apply to XY, XYZ, XYM and XYZM; Z/M do not change these bounds.
- **Affected Stories:** E and D for traversal of contained points.

### V03 — Validity 03 — Point: all ordinates supplied or all null

- **Category:** structural validity, Geometry or Geography Point, empty or non-empty.
- **N:** ISO-CD 4.2.3 describes a position with X/Y and optionally Z/M; a point is simple.
  The PHP representation using null ordinates belongs to the LongitudeOne model, not this ISO clause.
- **H / L-POINT-01:** model rule confirmed by Alexandre on 21 October 2026: for the declared variant's ordinates,
  all null → EMPTY Point; none null → non-empty Point; a mixture of null and supplied ordinates → invalid.
  The presence of M in XYM/XYZM is part of this structural check, even though M is not used in spatial calculations.
- **H / coverage: partially covered.** `AbstractPoint::hasOnlyNullCoordinates` and the variant constructors
  already apply this rule, throwing `InvalidValueException` for a partial state.
  No specialized Point validator exists in `spatial-validator`; it must check interface candidates
  without assuming these construction guarantees apply to them.
- **Scenarios:** see the table; a non-empty Point must also pass the other applicable checks,
  notably coordinate checks. Verify consistency between `isEmpty()` and the ordinates for an interface candidate;
  a partial state does not become valid merely because it declares itself EMPTY.
- **Affected Stories:** E; V01 for EMPTY and C03 for the declared variant.

| Variant | Ordinates to check | All null | None null | Mixture |
| --- | --- | --- | --- | --- |
| XY | X, Y | Valid EMPTY | Non-empty | Invalid |
| XYZ | X, Y, Z | Valid EMPTY | Non-empty | Invalid |
| XYM | X, Y, M | Valid EMPTY | Non-empty | Invalid |
| XYZM | X, Y, Z, M | Valid EMPTY | Non-empty | Invalid |

Examples: XY `(null,null)` → EMPTY; XY `(1,null)` → invalid; XYZ `(1,2,null)` → invalid;
XYM `(1,2,null)` → invalid; XYZM `(null,null,null,null)` → EMPTY.
Axes absent from the variant are not checked: `getZ() === null` for a non-empty XY Point does not make it invalid.
Zero is a supplied coordinate, distinct from `null`.

### V04 — Validity 04 — LineString cardinality

- **Category:** validity, non-empty list of points.
- **N:** ISO-CD 7.2.1, Description 3–5 and 10–11: no null member, at least two points to be well formed;
  zero points represents EMPTY.
  Clauses 7.2.2–7.2.3 and calls 16.1.7 → 16.1.6 prohibit EMPTY Point components;
  each Point must also satisfy V03 and the other applicable rules.
  The same call chain checks consecutive duplicates: see B01.
- **H / coverage: partially covered.** `MinimumPointCount(limit: 2)` supports the threshold;
  its default is 4. No general LineString composition or EMPTY exemption.
- **Scenarios:** zero → V01; one point → invalid; two distinct points → valid cardinality;
  a self-crossing line → may satisfy V04 while failing S01;
  two points, one EMPTY → invalid; two EMPTY Points → invalid, not an EMPTY LineString;
  a Point with partially null ordinates → invalid even with enough members.
- **Affected Stories:** F; B01 for consecutive duplicates and Q06 for historical ring composition.

### V05 — Validity 05 — CircularString structure and interpretation

- **Category:** validity, non-empty points and arc segments.
- **N:** ISO-CD 7.3.1, Description 6–11 and 4.2.6: three points for the first arc,
  then two points per arc; an odd count of at least three unless EMPTY.
  Three collinear points produce a degenerate straight segment, not automatic rejection.
  The intermediate point must differ from the start and end of its arc.
  The full circle and its intermediate point follow the source's special case.
- **H / coverage: missing in the validator.** The model already enforces cardinality and non-empty points,
  and distinct adjacent points by comparing `toArray()` with `==`;
  this comparison includes the present ordinates and does not establish spatial equality ignoring M.
- **Scenarios:** 3 then 5 points → admissible structure; 2 or 4 → invalid;
  `(0,0),(1,0),(2,0)` → straight-line degeneration allowed by the definition;
  `(1,0),(-1,0),(1,0)` → full-circle case, do not require three distinct points;
  A,A,B and A,B,B with A≠B → invalid, intermediate point coincident with an endpoint.
  Same spatial position but different M → same position; M does not distinguish the arc's points.
- **Affected Stories:** H; arc simplicity to be specified separately, not from chords alone; Q01–Q03.

### V06 — Validity 06 — CompoundCurve components and continuity

- **Category:** validity, contributing curves and joins.
- **N:** ISO-CD 7.4.1, Description 3–10: well-formed LineString or CircularString values,
  contiguous, each previous endpoint coinciding with the next start point; zero components represents EMPTY.
- **H / coverage: missing in the validator.** The model checks types, absence of EMPTY members,
  compatibility and continuity through `equalsTo`; this equality does not define the future spatial contract.
- **Scenarios:** line A→B then arc B→C → admissible join; A→B then C→D, B≠C → rejected;
  Polygon member → type rejection; same XY/XYZ position but different M → join accepted;
  EMPTY member → rejected in the ISO-CD path (Q05).
- **Affected Stories:** I, depends on F/H and D; Q01, Q05.

### S01 — Simplicity 01 — Curve simplicity

- **Category:** simplicity, non-empty curve and declared space; required for rings.
- **N:** ISO-CD 4.2.4 and 5.1.8–5.1.9: no interior point encountered again
  in the interior or on the boundary. OGC 6.1.6–6.1.7 confirms the curve/ring distinction.
- **H / coverage: partially covered.** `SimpleLineStringValidator` in XY;
  `Simple3dLineStringValidator` in XYZ; no simplicity check for arcs or an entire CompoundCurve.
- **Scenarios:** open path without crossing → simple; bow-tie → non-simple;
  adjacent segment overlap → non-simple; simple closed loop → simple;
  XY crossing at different elevations → non-simple in XY, simple in XYZ if no other contact;
  change only M → spatial result unchanged.
- **Affected Stories:** F–I, K–O; Q01 resolves the ordinates; Q02 addresses robustness.

### F01 — Closure 01 — Closure and rings

- **Category:** closure, then composition with simplicity, curves and surface rings.
- **N:** ISO-CD 4.2.4–4.2.5 and 7.1.6–7.1.9: closed if endpoints are equal;
  a ring if simple and closed; EMPTY is neither closed nor a ring.
- **H / coverage: divergent from the complete ring definition.**
  `FirstPointEqualsLastPointValidator` uses `equalsTo`; `RingValidator` adds a minimum of 4 and no
  consecutive duplicates, without simplicity. The minimum of four positions for a linear ring
  is explicit in OGC 07-036, GML 3.2.1, 10.5.8; see Q06.
- **Scenarios:** non-collinear A,B,C,A → ring; crossing A,B,C,D,A → closed but not a ring;
  same start/end XY, different Z → not closed for a variant with Z; different M → no effect;
  EMPTY line → not a ring; fewer than four positions → not a valid linear ring (Q06).
- **Affected Stories:** G, J–L, O; Q01, Q06 and compatibility Q07.

### V07 — Validity 07 — Linear boundaries of Polygon values

- **Category:** validity, non-empty Polygon, exterior and all holes.
- **N:** ISO-CD 8.3.1, Description 2–4: exterior and interior boundaries are linear rings.
- **H / coverage: partially covered.** No Polygon constraint; Ring exists but is incomplete.
  Acceptance by the installed constructor does not establish simplicity.
  Each non-empty linear ring requires at least four positions, including closure (Q06).
- **Scenarios:** simple closed exterior → admissible; open exterior or self-crossing hole → invalid;
  missing exterior in an object declared non-empty → structurally invalid; EMPTY exterior with holes → invalid (Q05).
- **Affected Stories:** J, depends on G/D; F01, V01 and Q05.

### V08 — Validity 08 — Planarity and non-degeneracy of planar surfaces

- **Category:** validity, non-empty Polygon/Triangle and PolyhedralSurface patches.
- **N:** ISO-CD 4.2.9–4.2.10: planar surface; OGC 6.1.11.1: planar Polygon.
  Do not infer planarity of an XYZ patch from its XY projection alone.
- **H / coverage: partially covered.** `PlanarPatch` checks a non-degenerate plane in XYZ
  only during polyhedral-surface validation; exact comparison, including all rings.
- **Scenarios:** square in an inclined or vertical plane → planar; four non-coplanar vertices → rejected;
  entirely collinear exterior → no non-degenerate planar surface; hole outside the plane → rejected.
- **Affected Stories:** J, L, O; Q01/Q02, Geography deferred under L-GEO-01.

### V09 — Validity 09 — Relationships between Polygon rings

- **Category:** topological validity, exterior and holes of a non-empty Polygon.
- **N:** ISO-CD 4.2.9: no crossings between rings, point contacts allowed,
  no cut lines, spikes or punctures, connected interior, each hole defines an exterior component.
  OGC 6.1.11.1 specifies tangent contacts; the explanatory note adds no independent rule.
- **H / coverage: missing.** Neither Ring nor PlanarPatch checks these relationships.
- **Scenarios:** hole strictly inside → admissible; exterior hole, hole crossing the shell,
  overlapping holes or a nested hole creating an island → rejected as a single Polygon;
  isolated tangent contact without interior disconnection → admissible; shared segment or chain of holes
  disconnecting the interior → rejected. A point contact alone does not establish validity.
- **Affected Stories:** K, depends on J; Q01–Q03 for space, precision and Geography.

### V15 — Validity 15 — Relative orientation of Polygon rings

- **Category:** validity / orientation convention, exterior and holes of a non-empty Polygon.
- **N OGC:** 6.1.11.1, second paragraph: exterior traversal defines the top side;
  interior rings have the opposite direction when viewed from that same side. This does not impose
  an absolute clockwise direction for all CRSs. Equivalence to an ISO-CD obligation is not established here.
- **H / coverage: missing** for relative orientation in `spatial-validator`.
  V13 checks a different orientation: boundaries shared between patches.
- **Scenarios:** counterclockwise exterior and clockwise hole in XY → OGC convention satisfied;
  hole with the same orientation as the exterior → warning under Q09; simultaneous reversal
  of all rings → relative orientation preserved. Vertical XYZ patch → examine in its plane.
- **LongitudeOne decision:** report this orientation defect through a warning in the payload (Q09).
- **Affected Stories:** J/K/O/P; compare each hole with the exterior.

### V10 — Validity 10 — Triangle

- **Category:** validity, non-empty Triangle, exterior only.
- **N:** ISO-CD 8.4.1, Description 2–4: ring of four points, no holes.
  OGC 6.1.11.1 explicitly requires three distinct, non-collinear vertices.
  Non-degeneracy cannot be reduced to counting four positions including the repeated closure position.
- **H / coverage: partially covered.** `TriangleValidator` checks four points, no holes,
  delegates to Ring; no explicit non-collinearity check.
- **Scenarios:** `(0,0),(1,0),(0,1),(0,0)` → valid; three collinear vertices → invalid;
  fourth distinct vertex → invalid; hole → invalid; EMPTY → V01.
- **Affected Stories:** L, depends on G/J; include the vertical XYZ case under Q01.

### S02 — Simplicity 02 — MultiPoint simplicity

- **Category:** simplicity, Point members.
- **N:** ISO-CD 4.2.15: simple if no pair of points is equal; no ordering or connectivity requirement.
- **H / coverage: missing.** Checking consecutive duplicates in a line is not a MultiPoint check.
- **Scenarios:** two distinct positions → simple; same position twice → non-simple,
  without automatically implying invalidity; same XY and different Z → depends on the XY/3D operation;
  different M only → no spatial separation; EMPTY → simple.
- **Affected Stories:** M; Q01 resolves the ordinates; Q05 for EMPTY members.

### S03 and F02 — Simplicity 03 and Closure 02 — MultiLineString

- **Category:** S03 simplicity; F02 closure; LineString components.
- **N:** ISO-CD 4.2.16–4.2.17: simple if each member is simple and the only intersections
  between members lie on their respective boundaries; closed if all members are closed.
- **H / coverage: missing.** Individual simplicity available, no comparison between members.
- **S03 scenarios:** disjoint lines → simple; shared endpoint of two open lines → simple;
  endpoint of one line inside another → non-simple; crossing or overlap → non-simple.
- **F02 scenarios:** two closed loops → closed; one open line → not closed;
  EMPTY collection → not closed under ISO-CD 9.3.3–9.3.4; EMPTY members rejected through Q05's path.
- **Affected Stories:** M, depends on F/D; do not require S03 or F02 for every valid collection.

### V11 — Validity 11 — Relationships between MultiPolygon members

- **Category:** validity, already validated Polygon members.
- **N:** ISO-CD 4.2.18–4.2.19: disjoint interiors, boundaries touching only at finitely many points;
  no cut lines, spikes or punctures; OGC 6.1.14 provides the same targeted comparison.
- **H / coverage: missing.** No validator for interactions between polygons.
- **Scenarios:** separate polygons → admissible; single-vertex contact → admissible;
  shared edge, overlapping surfaces, one polygon inside the other's interior → rejected;
  polygon inside another's hole without contact → admissible if each is valid.
- **Affected Stories:** N, depends on K/M; Q01–Q03.

### V12 — Validity 12 — PolyhedralSurface patches

- **Category:** validity, each Polygon patch.
- **N:** ISO-CD 8.5.1, Description 16–17: well-formed patches with Z.
- **H / coverage: partially covered.** `PolyhedralSurfaceValidator` requires Z, non-empty patches,
  `PlanarPatch`, Ring and XYZ simplicity; V09 and C02–C04 are not fully composed.
- **Scenarios:** valid planar XYZ patches → pass local checks; patch without Z → rejected;
  non-planar patch → rejected; misplaced but coplanar hole → V09 rejection expected, not covered;
  EMPTY patch in a non-empty surface → ISO-CD and historical rejection (Q05).
- **Affected Stories:** O, depends on J/K; M ignored in historical plane and topology checks.

### V13 — Validity 13 — Patch adjacency, incidence and orientation

- **Category:** topological validity of a non-empty PolyhedralSurface.
- **N:** ISO-CD 4.2.12 and 8.5.1, Description 2, 4, 18–19: contiguous surface,
  linear shared boundaries, at most two patches per segment, opposite directions on a shared boundary.
  OGC 6.1.12.1 describes the same adjacency and orientation requirements.
- **H / coverage: partially covered.** `PolyhedralSurfaceTopology` splits edges at known vertices,
  checks incidence, orientation and connectedness; no general search for intersections between patches.
- **Scenarios:** two patches connected by an edge traversed in opposite directions → admissible for V13;
  same direction, three incident patches or disjoint patches → rejected;
  subdivided shared edge → historically accepted; vertex-only contact → no edge connection;
  patches crossing away from shared edges → missing check to specify.
- **Affected Stories:** O; Q02 and Q08.

### V14 — Validity 14 — Open surfaces and closure enclosing a solid

- **Category:** validity / closure, PolyhedralSurface.
- **Sources in tension:** ISO-CD 8.5.1 Description 6 states a closed polyhedron, whereas Description 7
  and OGC 6.1.12.1 distinguish the closed case; 4.2.12 describes a contiguous surface without that general obligation.
  **LongitudeOne decision approved under Q08:** open surface allowed by default, closure required separately.
- **H / coverage: divergent from a reading requiring closure.** The validator states that it does not require
  a solid; single patches and open surfaces are accepted. This does not resolve the ISO tension.
- **Scenarios:** valid open book → accepted by default, rejected by the validator requiring closure;
  valid closed tetrahedron or cube surface → accepted by both validators;
  EMPTY Z → valid under V01, not evidence of a solid; its closure check remains to be specified.
- **Affected Stories:** C, O, P; two validators under Q08, no historical compatibility obligation (Q07).

### C01–C04 — Compatibility 01 to 04 — Compatibility and recursive validation

- **C01 — type and recursion.** Validity: permitted member type and application of member rules.
  N: ISO-CD 7.4.1, 8.3.1, 8.5.1 and 4.2.14–19. Point in MultiPoint accepted; Polygon rejected.
- **C02 — family.** Parent/component Geometry or Geography compatibility, historical rule to formalize.
  Geometry in Geography → historical rejection; same family → passes; future policy to specify below.
- **C03 — dimension.** Variant compatibility, historical model behavior rather than a universal ISO obligation.
  XYZ and XYM rejected despite cardinality 3; same variant → passes this check.
- **C04 — spatial reference.** Same SRS: N, ISO-CD 4.2.1 and 4.2.14; PHP identity: H.
  Same authority/identifier → accepted; different identifier or authority → historical rejection.

**Coverage:** C01 partially covered by the model, general recursion missing in the validator;
C02–C04 partially covered by `SameFamilyValidator`, `SameDimensionValidator`,
`SameSpatialReferenceValidator`, to be explicitly composed for each relationship.
These constraints compare with an expected value; they do not automatically traverse components.

`SpatialReference::equals` compares authority and identifier; it does not resolve equivalence between two CRSs.
The same integer under two authorities therefore does not establish identity. Equal spatial references
establish neither axis validity nor agreement between the CRS axis count and the model.
ISO-CD 4.2.1 relates dimension to SRS; checking that agreement requires CRS information
not available from identity alone (ISO 19111, Q04/Q07).
ISO-CD 8.5.1 Description 9 uses the minimum patch coordinate dimension:
the model's strict homogeneity is not presented as a universal ISO obligation.

**Affected Stories:** D and all composite validators; Q05/Q07 address recursion, diagnostics and compatibility.
A collection heterogeneous in types or topological dimensions is allowed by 4.2.14;
there is no general prohibition of intersections between its members. Test Point + Polygon containing it →
no validity failure solely because they intersect.

### B01 — Business restriction 01 — Consecutive duplicates: SQL/MM check for line points

- **Category:** SQL/MM construction/mutation condition, to include in the line validation contract.
  Identifier B01 is retained for traceability; this check is not merely a business restriction.
- **N:** ISO-CD 7.2.3 calls `ST_CheckConsecDups` (16.1.7), which rejects equal consecutive members
  and calls `ST_CheckNulls` (16.1.6), which prohibits null and EMPTY members.
  The procedure's equality criterion must be distinguished from historical PHP comparison;
  merely reading the SQL symbol `=` does not justify arbitrarily substituting `ST_Equals`.
- **H / coverage: partially covered.** `NoConsecutiveDuplicatePointsValidator` compares adjacent points
  through `equalsTo` and belongs to historical Ring. The model also checks point additions.
  General LineString composition is missing; Z is considered and M ignored under Q01.
  Historical differences may be corrected without compatibility constraints (Q07).
- **Scenarios:** A,A,B → consecutive duplicate rejected; A,B,A with A≠B → no consecutive duplicate;
  same XY/Z but different M → duplicate under Q01, unlike the historical check;
  EMPTY member → rejected by the associated condition 16.1.6, even if the other points differ.
- **Affected Stories:** F/G; this check does not replace simplicity S01.

## 5. EMPTY, absent values and equality

| Situation | Meaning | Historical behavior / contract to specify |
| --- | --- | --- |
| `null` or `''` | No spatial value supplied | Ignored; presence handled by `NotNull`/`NotBlank`. |
| EMPTY object | Typed empty geometric set | V01; do not blindly apply a non-empty point minimum. |
| Collection without members | Empty collection | Valid under V01; recursion has no members. |
| EMPTY members | Present components | ISO-CD prohibitions and scope specified under Q05. |
| EMPTY Point in a LineString | Component without a position | Prohibited: 7.2.3 → 16.1.7 → 16.1.6. |
| EMPTY hole/exterior with holes | Invalid boundary | Rejected in the ISO-CD path, Q05. |
| M absent in XYZ | Variant without a measure | Normal; C03 distinguishes XYZ from XYM. |
| Partially null Point ordinates | Incomplete position | Invalid under V03; not EMPTY. |

Historical `Point::equalsTo` depends on concrete class, family, dimension,
integer SRID and strict comparisons of X/Y/Z/M ordinates. It does not compare SRS authority.
It is neither full spatial-reference identity equality nor a definition of `ST_Equals`.
ISO-CD 4.2.2.3 distinguishes ordinary equality (Z/M ignored), 3D equality and implementation-defined tolerance.
Comparison uses `PHP_FLOAT_EPSILON` scaled by the magnitude of the values under Q02.
Test int/float, signed zero, small perturbations, large coordinates,
near-collinearity and axis permutations before stabilizing an algorithm.

## 6. Coverage evidence in the repository

Paths under `lib/Validator/` and `tests/Unit/Validator/` at the revision in section 1.
A passing suite establishes tested historical behavior, not normative completeness.
Under Q07, no old API or restriction must be preserved; the references below are an inventory.

The following sources are under `lib/Validator/Constraints/` unless otherwise stated:

- C02–C04: `SameFamilyValidator.php`, `SameDimensionValidator.php`, `SameSpatialReferenceValidator.php`;
  tests `Constraints/CompatibilityTest.php` and `Constraints/RingValidatorTest.php`.
- V04, F01, B01: `MinimumPointCountValidator.php`, `FirstPointEqualsLastPointValidator.php`,
  `NoConsecutiveDuplicatePointsValidator.php`, `RingValidator.php`;
  tests `Constraints/RingValidatorTest.php` and `Constraints/ConstraintContractTest.php`.
- S01 XY: `SimpleLineStringValidator.php`; test `Constraints/SimpleLineStringValidatorTest.php`.
- S01 XYZ: `Simple3dLineStringValidator.php`; test `Constraints/Simple3dLineStringValidatorTest.php`.
- V10: `TriangleValidator.php`; test `Constraints/TriangleValidatorTest.php`.
- V02/V08/V12: `../Internal/PlanarPatch.php` and `PolyhedralSurfaceValidator.php`;
  tests `Constraints/BoundaryCandidatesTest.php` and `Constraints/PolyhedralSurfaceValidatorTest.php`.
- V13/V14: `../Internal/PolyhedralSurfaceTopology.php`; test `Constraints/PolyhedralSurfaceValidatorTest.php`.
- Assertions: helpers `../LineStringValidation.php`, `../RingValidation.php`, `../TriangleValidation.php`;
  test `AssertionHelpersTest.php`.

The helpers `DimensionValidation`, `FamilyValidation`, `SpatialReferenceValidation`,
`LineStringValidation`, `RingValidation`, `TriangleValidation`, `PolyhedralSurfaceValidation`
also expose an assertion API with exceptions; Q07 permits changing or removing them.
`tests/Support/SpatialCandidates.php` supplies candidates to test states
prevented by historical constructors. Model guarantees do not replace these tests.

### Guarantees of the installed model

| Source under `vendor/longitude-one/spatial-types/lib/Types/` | Observed guarantee | Limitation |
| --- | --- | --- |
| `AbstractPoint.php` | All ordinates null or none (V03) | No general finiteness guard. |
| `AbstractLineString.php` | Non-EMPTY points, checks on addition | No general minimum of two points. |
| `AbstractPolygon.php` | Ring compatibility and Ring assertion | No topology between holes. |
| `AbstractTriangle.php` | Triangle assertions | No proof of non-collinearity. |
| `AbstractCircularString.php` | Cardinality and non-empty points | No arc simplicity. |
| `AbstractCompoundCurve.php` | Types, compatibility, members and joins | Historical PHP equality. |
| `AbstractPolyhedralSurface.php` | Assertions on construction and mutation | V09/V12–V14 limitations. |

The Geography family historically bounds longitude and latitude; this does not establish geodetic topology.
The future contract must recheck interface objects and relevant mutations under D,
without introducing a reverse dependency from `spatial-types` to `spatial-validator`.

## 7. Questions and answers

Answers record Alexandre's decisions; remaining questions are open.
The catalogue can be completed without resolving these choices; the affected Stories do not automatically become Ready.

### Q01 — Answer 01 — Consider Z, ignore M

**Alexandre's decision of 21 October 2026:** geometric calculations consider Z when present
and always ignore M, including position equality, closure, joins and simplicity.
M is an arbitrary measure, not necessarily time.
Example: `(1,2,3,10)` and `(1,2,3,20)` represent the same XYZ position.

### Q02 — Answer 02 — Scale PHP_FLOAT_EPSILON to the magnitude of the values

**Alexandre's decision of 21 October 2026:** compare two finite coordinates using a tolerance of
`PHP_FLOAT_EPSILON` multiplied by `max(1.0, abs($a), abs($b))`.

```php
$factor = max(1.0, abs($a), abs($b));
$tolerance = PHP_FLOAT_EPSILON * $factor;
$equal = abs($a - $b) <= $tolerance;
```

The factor is symmetric and follows the larger absolute value; below 1, the tolerance remains
`PHP_FLOAT_EPSILON`. No rounding or coordinate modification occurs.
Non-finite values are rejected before this comparison (V02).

**Verified examples:** `0.1 + 0.2` and `0.3`, `1.1 + 2.2` and `3.3`,
`700000.1 + 0.2` and `700000.3` → equal; `3.0` and `3.000001` → different.
This is approximate numeric equality, not a geographic distance.
This tolerance does not guarantee compensation for every accumulation of calculation errors and is not transitive.
Its use in planarity, orientation and intersection tests remains to be specified according to
intermediate quantities; do not blindly apply a coordinate threshold to a determinant.
Source: [PHP manual — comparing floats](https://www.php.net/manual/en/language.types.float.php).
Affected Stories: E–O.

### Q03 — Answer 03 — Warn when a geographic check is unavailable

**Alexandre's decision of 21 October 2026:** an unavailable geographic check produces a violation
with an explicit message and `payload: ['severity' => 'warning']` on the associated constraint.
This means "check not performed", not "invalid geometry". Available checks continue.
The application interprets the `warning` level; Symfony retains the violation in its list.
Affected Stories: C, H–K, N–P.

### Q04 — Answer 04 — Finite coordinates and Geography angular bounds

**Alexandre's decisions of 21 October 2026:** reject `INF`, `-INF` and `NAN` in present ordinates.
For the Geography family, retain angular bounds in degrees, inclusive:
latitude from −90 to +90 and longitude from −180 to +180. Exceeding a bound produces a violation.
The general validator checks no other limits specific to each reference system.
Specialized validators, for example Lambert-93, may be proposed through contributions.
Partially null coordinates are already invalid under V03.
Checks of reference-system axes and dimension remain to be specified separately.
Affected Stories: E, J, O.

### Q05 — Normative result 05 — EMPTY components

**Alexandre's instruction:** apply prohibitions established by standards; do not add
a prohibition without an explicit source.

In ISO-CD, `ST_CheckNulls` (16.1.6) rejects null **and EMPTY** members.
The following methods call it directly or through another procedure:

| Parent object | EMPTY component | Verified ISO-CD normative path |
| --- | --- | --- |
| LineString | Point prohibited | 7.2.3 → 16.1.7 → 16.1.6 |
| CircularString | Point prohibited | 7.3.3 → 16.1.7 → 16.1.6 |
| CompoundCurve | Curve prohibited | 7.4.3 → 16.1.6 |
| Polygon / Triangle | Interior ring prohibited | 8.3.4 → 8.2.4 → 16.1.6; Triangle has no holes (8.4.1) |
| Polygon / Triangle | EMPTY exterior ring prohibited for a non-empty object | 8.3.3 → 8.2.3; ring required, 7.1.8 |
| PolyhedralSurface | Patch prohibited | 8.5.3 → 16.1.6 |
| GeometryCollection | Member prohibited in this SQL/MM path | 9.1.3 → 16.1.6 |
| MultiPoint | Point prohibited | 9.2.3 → 16.1.8 → 16.1.6 |
| MultiLineString | Line prohibited | 9.4.3 → 16.1.10 → 16.1.6 |
| MultiPolygon | Polygon prohibited | 9.6.3 → 16.1.15 → 16.1.6 |

A list without members is not a list containing an EMPTY member: the former may represent a valid EMPTY object.
A Polygon without holes does not need an EMPTY interior ring.
Examples: LineString without points → valid EMPTY; LineString containing an EMPTY Point → invalid;
non-empty Polygon with an EMPTY hole → component rejected in the ISO-CD path.
A collection containing a non-empty Point and an EMPTY Point is rejected by ISO-CD 9.1.3 → 16.1.6.

**Scope limitation:** these prohibitions are explicit in the Committee Draft's SQL/MM methods.
OGC SFA 1.2.1, 6.1.3.1 does not state this general prohibition for GeometryCollection.
Do not present the SQL/MM result as an obligation shared by all standards;
its application to the generic collection contract must preserve this distinction.
No additional prohibition is inferred merely from a text's silence.

**Closure of EMPTY:** an EMPTY MultiLineString is not closed under ISO-CD 9.3.3–9.3.4,
which return zero for an EMPTY MultiCurve; this does not make it geometrically invalid.
Affected Stories: C–O. No runtime change in this catalogue.

### Q06 — Normative result 06 — Minimum four positions for a linear ring

A non-empty linear ring has at least four positions, the last repeating the first: A, B, C, A.
This minimum does not replace closure, simplicity and non-degeneracy checks.

- **Triangle:** exactly four points, explicitly in ISO-CD 8.4.1, Description 2; no holes.
- **Polygon:** exterior and holes are linear rings (ISO-CD 8.3.1, Description 2–3).
- **Linear ring:** minimum four positions and coincident endpoints, explicitly in
  [OGC 07-036, GML 3.2.1, §10.5.8](https://docs.ogc.org/is/07-036/07-036.pdf), printed page 78.
  ISO-CD 4.2.5 defines a linear ring through closure and simplicity, without stating this numeric minimum there.

This result concerns **linear** rings: a circle carried by a CircularString can be defined by three points
and does not receive this threshold of four.
Affected Stories: F, G, L. Ring's historical minimum is therefore retained for linear rings.

### Q07 — Answer 07 — No historical compatibility to preserve

**Alexandre's decision of 21 October 2026:** nothing has been published; all old APIs and restrictions
may be changed or removed, including helpers, messages and exceptions.
Existing behavior is an inventory, not a contract to preserve.
Normative requirements and previously approved functional decisions remain applicable.
Affected Stories: C, D, F, G, L, O, P.

### Q08 — Answer 08 — Two validators, open surfaces allowed by default

**Alexandre's decision of 21 October 2026:** create two PolyhedralSurface validators.
The first allows an open surface; the second also checks that the polyhedron is closed.
General validation calls the first by default: an open book is accepted if it satisfies the other rules.
The open book fails the additional closure check; a valid cube surface satisfies it.
This decision resolves the LongitudeOne choice in response to the ISO-CD inconsistency identified in V14.
Affected Stories: C, O, P.

### Q09 — Answer 09 — Warn if a hole has the same direction as the exterior

**Alexandre's decision of 21 October 2026:** an interior ring traversed in the same direction as the exterior
produces a violation with `payload: ['severity' => 'warning']` on the associated constraint.
Compare directions from the same side of the plane; do not automatically reorient rings.
Example: counterclockwise exterior and counterclockwise hole → warning; clockwise hole → no orientation warning.
Affected Stories: J, K, O, P.

### Remaining details before the affected Stories

The preceding answers are approved. The following points still require investigation or Alexandre's approval;
they do not block delivery of the catalogue, but prevent declaring affected Stories Ready without refinement.

| Item | Source | Stories |
| --- | --- | --- |
| Tolerance of intermediate calculations | Q02, V08/V09/V13 | H–O |
| SRS axes and dimension | ISO-CD 4.2.1, ISO 19111 not inspected | D/E/J/O |
| Closure of an EMPTY surface | ISO-CD 8.5.1, Q08 | O/P |
| Component family and variant | C02/C03, model guarantees, Q07 | D and composites |
| Collection profile with EMPTY members | ISO-CD 9.1.3/16.1.6, OGC 6.1.3.1 | C/D/M/P |
| Arc simplicity and patch intersections | V05/S01/V13; ISO-CD 4.2.4/8.5.1 | H/I/O |

- **Intermediate calculations:** choose suitable normalization or a threshold accounting for their scale,
  without changing Q02's formula for two coordinates. Consequence: consistent plane, orientation
  and intersection checks. Results near the threshold still depend on this specification.
- **SRS axes and dimension:** check using CRS metadata or declare this check unavailable.
  Consequence: additional data needed or limited coverage; no inference from the integer SRID alone.
  Analyze ISO 19111 before a normative conclusion.
- **EMPTY surface:** specify whether the validator requiring closure rejects it or accepts it as an empty case.
  Consequence: the EMPTY scenario's result with this validator is undetermined; default EMPTY validity is approved.
- **Family and variant:** retain strict homogeneity or explicitly define admissible mixtures.
  Consequence: accepted components and calculation space; Q07 permits changes without choosing this policy.
- **EMPTY collections:** retain the SQL/MM path rejecting these members or offer a separate profile.
  Consequence: different acceptance by profile; no claim of identical SQL/MM and OGC requirements.
- **Arcs and patches:** specify checks for all interactions, or declare those not supported.
  Consequence: arc self-intersection and crossing-patch scenarios to complete before H/I/O.
  Chord or edge tests alone do not establish simplicity or overall validity.

## 8. Mapping to proposed Stories

Letters A to P identify the Epic's Stories, distinct from rule codes such as S01.
A is issue #8 and B is issue #10. The other letters are not GitHub issue numbers.

| Story | Rules and deliverable to specify | Dependencies and decisions |
| --- | --- | --- |
| A / #8 | This catalogue, inventory and register | Independent traceability review |
| B / #10 | Input contract following Symfony conventions | A; L-INPUT-01 |
| C | Generic Symfony entry point and advertised coverage | A/B; Q01, Q03, Q05, Q07 |
| D | C01–C04, traversal, paths, groups, codes | C; Q05, Q07; prepare before composites |
| E | V02/V03/V16, common coordinate rules and Geography bounds | A; Q02, Q04 |
| F | V04, S01, B01 according to contract | D/E; Q01, Q05–Q07 |
| G | F01 and ring simplicity | F; Q01, Q02, Q06, Q07 |
| H | V05 and arc simplicity if requested | D/E; Q01–Q05 |
| I | V06, joins and overall simplicity if requested | F/H; Q01–Q03, Q05 |
| J | V07/V08/V15, structure and plane | D/G; Q01–Q05, Q09 |
| K | V09/V15, relationships and orientation | J; Q01–Q03, Q09 |
| L | V10, composition of Polygon rules | G/J; Q01, Q05–Q07 |
| M | Collections, C01–C04, optional S02/S03/F02 | D/E/F; Q01–Q05, Q07 |
| N | V11, interactions between Polygon values | K/M; Q01–Q03 |
| O | V12–V14, patches, interactions and two validators under Q08 | J/K; Q01–Q05, Q07–Q09 |
| P | API, composition, diagnostics, publishable limitations | Approved C/D contracts and delivered coverage |

This file does not finalize recursive traversal, violation codes or public APIs.
Detailed `spatial-docs` user documentation remains scheduled from `1.0.0-RC.0`.

## 9. Verification and delivery status

- [x] Source and revision inventory recorded.
- [x] Rules, variants, scenarios and historical differences identified.
- [x] Alexandre's structural-first Geography direction recorded, topology deferred.
- [x] Generic Geometry calculation space approved: XYZ if Z is present, otherwise XY (L-DIM-01).
- [x] Symfony input conventions adopted in accordance with Story #10 (L-INPUT-01).
- [x] Received decisions recorded; remaining details, options and consequences mapped to affected Stories.
- [x] Final independent review: no documentary blocker identified against Story #8's criteria.
- [x] `composer test`: 112 tests, 177 assertions; `composer quality`: passed.
- [ ] Integration and full Definition of Done: outside this local preparation.
