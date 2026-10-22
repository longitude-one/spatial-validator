# Spatial Validator

Standalone Symfony validation for LongitudeOne spatial types. MIT licensed; requires PHP 8.4 or later within PHP 8.
Symfony Validator 5.4, 6, 7 and 8 are supported. `spatial-types` supplies the spatial objects and interfaces being validated.

## Current status

![longitude-one/spatial--validator](https://img.shields.io/badge/longitude--one-spatial--validator-blue)
![Stable release](https://img.shields.io/github/v/release/longitude-one/spatial-validator)
![Minimum PHP Version](https://img.shields.io/packagist/php-v/longitude-one/spatial-validator.svg?maxAge=3600)
[![Packagist License](https://img.shields.io/packagist/l/longitude-one/spatial-validator)](https://github.com/longitude-one/spatial-validator/blob/main/LICENSE)

[![PHP tests and quality](https://github.com/longitude-one/spatial-validator/actions/workflows/ci.yml/badge.svg)](https://github.com/longitude-one/spatial-validator/actions/workflows/ci.yml)
[![Downloads](https://img.shields.io/packagist/dm/longitude-one/spatial-validator.svg)](https://packagist.org/packages/longitude-one/spatial-validator)
[![Code coverage](https://codecov.io/gh/longitude-one/spatial-validator/branch/main/graph/badge.svg)](https://codecov.io/gh/longitude-one/spatial-validator)

## Installation

```sh
composer require longitude-one/spatial-validator
```

PCOV and the quality tools are development requirements only; consumer installations do not require PCOV.

## Development

Use PHP 8.4.1 or later (PHPUnit 13 requirement), Composer 2, Node.js 22 and the PCOV extension.
From a clean checkout:

```sh
composer install
composer install-tools
npm install --ignore-scripts
composer check
```

Lockfiles are ignored by this library. Clean installations resolve the declared compatible dependency ranges.
Run `composer update` in the corresponding directory to refresh an existing local tool environment.

| Command | Purpose |
| --- | --- |
| `composer validate --strict` | Strict validation of the root `composer.json` only |
| `composer audit` | Audit the root PHP dependency environment |
| `composer audit-dependencies` | Audit root and all four isolated PHP tool environments |
| `composer test` | PHPUnit, failing on an empty suite |
| `composer coverage` | PCOV text, Clover XML and HTML reports under `build/` |
| `composer coverage-check` | Require 100% source statement coverage from the Clover report |
| `composer cs-check` | PHP-CS-Fixer check without file changes |
| `composer cs-fix` | Apply coding fixes locally |
| `composer phpstan` | PHPStan level 9 over `lib/` and `tests/` |
| `composer phpmd` | PHPMD 3 source and test rulesets |
| `composer phpcs` | PHP_CodeSniffer PSR-12 checks |
| `composer markdown` | Markdownlint |
| `composer quality` | All static and Markdown checks |
| `composer check` | Root Composer validation, all PHP audits, tests, coverage and quality checks |

Coverage enables PCOV explicitly and restricts instrumentation to `lib/`.
Read `build/coverage.txt`, `build/clover.xml`, or `build/coverage/index.html` after running it.

Markdown checks use `markdownlint-cli`. Transitive overrides select patched KaTeX, TOML and YAML
versions until the CLI updates its own requirements; keep `npm audit` clean when updating these tools.

## Explicit validation

Use the `Spatial` constraint with Symfony Validator to dispatch by geometry type:

```php
use LongitudeOne\SpatialTypes\Types\Dimension2\Geometry\LineString;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use Symfony\Component\Validator\Validation;

$line = new LineString([[0, 0], [1, 0], [0, 1], [0, 0]]);
$violations = Validation::createValidator()->validate($line, new Spatial());
```

Constraints and their validators live under `LongitudeOne\SpatialValidator\Validator\Constraints`.
`Spatial` delegates to one dedicated constraint per `GeometryTypeEnum` case.
You can also apply a specialized constraint directly, for example `Point` or `Triangle`.
Standard Symfony groups and payload are accepted; delegation preserves the validation root,
property path and active group. `Spatial` has no warning payload by default and does not copy
its payload to specialized constraints.

### Current coverage

Dispatch covers all 27 enum cases: BRepSolid, Circle, CircularString, Clothoid, CompoundCurve,
CompoundSurface, Curve, CurvePolygon, EllipticalCurve, GeodesicString, Geometry, GeometryCollection,
LineString, MultiCurve, MultiLineString, MultiPoint, MultiPolygon, MultiSurface, NURBSCurve,
Point, Polygon, PolyhedralSurface, Solid, SpiralCurve, Surface, TIN and Triangle.
Dispatch uses spatial interfaces and the declared type, independently of dimension-specific classes.
Available Geometry/Geography and XY/XYZ/XYM/XYZM variants follow the same route for their type.
Specialized validators check their required interface when available and their exact enum type;
in particular, Triangle requires `TriangleInterface` and does not accept a plain Polygon.

**Point, LineString, CircularString and CompoundCurve validation are implemented.**
Other admissible spatial objects, including EMPTY,
produce an unavailable-validation violation with the message `<Type> validation is not implemented yet.`
Its specialized constraint carries `['severity' => 'warning']` by default.
Symfony still includes this warning in the violation list; payload is application metadata,
not an instruction to filter the violation. The remaining placeholders provide no geometric validity,
SQL/MM conformance or geodetic validation guarantee.

The defensive dispatch default throws PHP `InvalidArgumentException` with
`Unsupported spatial type: <type>`. Unlike the non-implementation warning, this is an exception;
the current closed enum makes the default unreachable for a conforming spatial object.

Historical implementation is temporarily archived in `old/`, outside production autoload.
It is reference material for later rule implementations, not part of the active API.

## Continuous integration

CI tests the cross product of PHP 8.4/8.5 and Symfony 5.4/6/7/8.
Each entry resolves its own enforced Symfony branch, verifies the installed version and runs PHPUnit.
PHP 8.4.0 is excluded from development because PHPUnit 13 requires PHP 8.4.1 or later.
PCOV remains loaded in every matrix entry so Composer can satisfy the development requirements.
A separate job runs all quality checks and enforces 100% source statement coverage with PCOV.
Jobs use Ubuntu 24.04 and Node.js 24-compatible Actions.
The quality job runs strict validation of the root Composer manifest and audits all PHP dependency environments
through `composer check`; a failed validation or audit fails the job. Tool manifests are not strictly validated.
See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

## Release tooling

`npm run release -- --dry-run` previews release generation without changing files, commits or tags.
`commit-and-tag-version` uses `.versionrc.json`, copied from `spatial-types`, for the `v` tag prefix
and Conventional Commits changelog sections. See [CHANGELOG.md](CHANGELOG.md).
Actual releases require the maintainer's authorization.

## Input compatibility

| Input | Behavior with the applicable spatial constraint |
| --- | --- |
| PHP `null` | No violation; compose with Symfony `NotNull` when a value is required |
| Any string, including `''` and WKT | Rejected: an object implementing the required spatial interface is expected |
| Other unsupported PHP value or incompatible spatial type | Rejected as an unexpected value |
| Admissible spatial object, including EMPTY | Delegates to its specialized validator |

A direct constraint-validator call throws Symfony `UnexpectedValueException` for an unsupported
value; the Symfony Validator service converts it into a standard type violation.
A wrong constraint always raises Symfony `UnexpectedTypeException`, even for `null` or `''`.
EMPTY is a spatial state, not an absent PHP value, and is never skipped by the `null` check.
Parse serialized geometry into a spatial object before validation; strings are not parsed here.

This input policy supersedes the earlier proposal to ignore both `null` and `''`:
only `null` is ignored. No library input exception hierarchy is introduced.

## Point validation

[Validator reference](docs/validators.md) lists the generic, specialized and reusable coordinate constraints.
`Point` now composes `CoordinateLayout`, `FiniteCoordinates` and `GeographyBounds` for Geometry and Geography
in XY, XYZ, XYM and XYZM. It accepts consistent EMPTY points, rejects partial or inconsistent EMPTY states,
and rejects non-finite declared ordinates including M. Geography X/Y bounds are inclusive degrees:
[-180, 180] / [-90, 90]. Geometry has no angular bounds. No CRS lookup, axis inference, repair or reprojection occurs.

Point and its coordinate constraints use a null payload by default; geometric violations are errors rather than
unavailable-validation warnings. Custom groups and payload propagate to the elementary violations.
Violation codes are `coordinatelayout-invalid`, `finitecoordinates-invalid` and `geographybounds-invalid`.
Only PHP null is ignored; every string, including the empty string, is rejected.
Use Symfony `NotNull` or `NotBlank` for presence and `Sequentially` to stop after a presence failure.
A genuine EMPTY Point passes presence checks and is still evaluated geometrically.

These rules implement the approved LongitudeOne representation, finiteness and Geography decisions in #14;
the catalogue references ISO/IEC CD 13249-3:201x(E), 2009-01-16, clauses 4.2.3 and 5.1.10, a Committee Draft.
Other specialized types still report unavailable geometric validation, with the LineString coverage described below.

## Component validation

Aggregate validators compose `Components` to check member types, parent/member family, exact coordinate variant
and complete spatial-reference identity, then delegate through `Spatial` in the active Symfony context.
Nested failures retain their original constraint, code and payload, for example at
`geometries[2].rings[1].points[3]`. Cycles yield an error at the cyclic reference; independent branches continue.
EMPTY parent flags never hide members. Type-specific rules not yet implemented still emit their warning,
so recursive component checks do not establish complete parent validity.
See the [component reference](docs/validators.md#component-rules) for paths, member types and error codes.

## LineString validity and explicit simplicity

`LineString` composes `Components`, `MinimumPointCount`, `NonEmptyPoints` and `NoConsecutiveDuplicatePoints`.
Its default payload is null. An empty point list is valid; otherwise at least two valid non-EMPTY Points are
required. Component Point rules include finite M and Geography bounds. Failures retain `points[index]` paths,
root and active group. Explicit LineString groups and payload apply to its elementary line constraints;
component Point diagnostics retain their own payloads.

Consecutive positions compare XY, or XYZ when Z is declared, with
`abs(a-b) <= PHP_FLOAT_EPSILON * max(1.0, abs(a), abs(b))`. M never distinguishes spatial positions.
A crossing or backtracking line can be valid: ordinary validity requires neither closure nor simplicity.

```php
use LongitudeOne\SpatialValidator\Validator\Constraints\LineString;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleLineString;

$validity = $validator->validate($line, new LineString());
$simplicity = $validator->validate($line, new SimpleLineString());
```

`SimpleLineString` first checks line validity, then tests Geometry segments in XY or XYZ according to the
coordinate variant. It rejects crossings, self-tangencies and overlapping intervals; adjacent segments may
share their endpoint, including the first/last endpoint of a closed line. It ignores M. Exact orientation
and coplanarity signs operate on the supplied finite binary coordinates; no determinant epsilon, snapping,
coordinate mutation or additional proximity tolerance is introduced.

For non-empty Geography, geodetic simplicity is unavailable and yields an explicit warning. The warning uses
a copy of the constraint with default payload `['severity' => 'warning']`; explicit consumer payload is retained.
The original constraint is unchanged. EMPTY is simple. Cartesian topology never substitutes for geodetic checks.
The [validator reference](docs/validators.md#line-rules) lists the constraints, codes and limitations.

## Linear rings

Apply `Ring` explicitly to a LineString boundary:

```php
use LongitudeOne\SpatialValidator\Validator\Constraints\Ring;

$violations = $validator->validate($boundary, new Ring());
```

`Ring` rejects an EMPTY LineString, then composes `MinimumPointCount(limit: 4)`,
`FirstPointEqualsLastPoint` and `SimpleLineString`. The simplicity constraint also composes LineString's Point,
metadata and consecutive-position checks. A valid closing repetition is allowed; adjacent duplicate positions,
open boundaries, bow-ties and collinear retracing are rejected. Point diagnostics retain `points[index]`,
the outer property prefix, root and active group. Boundary errors remain on the boundary value.

Geometry closure uses the approved finite-coordinate equality in XYZ when Z exists, otherwise XY, ignoring M.
The exact simplicity predicates still evaluate the supplied coordinates: approximate endpoint equality cannot
exempt an interior crossing or move the contact. A LineString can be valid and EMPTY while failing `Ring`.
`MinimumPointCount` retains its independent EMPTY allowance; Ring supplies the explicit EMPTY rejection.

Non-empty Geography closure and simplicity are unavailable and produce separate warning diagnostics alongside
available structural/Point checks. Warning constraints are copied; explicit payload is preserved. No Cartesian
calculation establishes geodetic validity. The standalone `FirstPointEqualsLastPoint` constraint only checks
closure, not full line or ring validity. Both new constraints require LineStringInterface with declared
LineString type and keep the standard null/string input contract.

Ring is an opt-in boundary rule, not a new GeometryTypeEnum case or a change to Spatial dispatch.
Polygon integration remains assigned to its specialized Stories. CircularString is not a LineString and does
not receive the four-position rule through Ring. See the [ring reference](docs/validators.md#linear-ring-rules).

## CircularString validity and explicit simplicity

`CircularString` composes `Components`, `CircularPointCount` and `CircularArcPoints`. EMPTY is valid;
non-empty curves need an odd number of at least three valid, non-EMPTY Points. An arc's intermediate position
must differ from both endpoints under Q02 equality, in XYZ when Z exists and otherwise XY, ignoring M.
A full circle A,B,A is admissible. Collinear triples degenerate to the segment connecting their endpoints.
Ordinary validity does not require closure or simplicity.

```php
use LongitudeOne\SpatialValidator\Validator\Constraints\CircularString;
use LongitudeOne\SpatialValidator\Validator\Constraints\SimpleCircularString;

$validity = $validator->validate($curve, new CircularString());
$simplicity = $validator->validate($curve, new SimpleCircularString());
```

`SimpleCircularString` first validates the curve, then checks its actual arcs and degenerate segments, including
crossings, interior tangencies and overlapping/retraced intervals. Adjacent arc joins and the actual common
start/end of an otherwise simple closed curve are allowed. A position repeated at an interior join and the
curve boundary is forbidden: two tangent full circles forming a figure eight are not simple. Chord intersections
never substitute for arcs.
Exact rational supports and algebraic intersection signs operate on supplied finite binary coordinates;
there is no determinant tolerance, snapping, densification or coordinate repair. M is ignored spatially.

Non-empty Geography simplicity reports an unavailable warning while structural/Point checks remain active.
An isolated full circle XYZ is simple; A,B,A supplies no plane for interactions with another arc. Those pairs
therefore report an explicit unavailable warning, following Alexandre's approved scope decision for #17.
No XY plane is invented for these 3D circles. Independently provable endpoint repetitions remain errors. EMPTY is
simple and is not a circular ring; a circular ring must
also be non-empty and closed. Ring's four-position minimum remains specific to linear boundaries.

Warnings clone their constraints, preserve explicit payload and use warning severity by default. Other errors
have null payload by default. Delegation retains Symfony root, path and active group. See the
[circular constraint reference](docs/validators.md#circular-curve-rules) for codes and diagnostic paths.

## CompoundCurve validity and explicit simplicity

`CompoundCurve` validates its LineString/CircularString members, rejects EMPTY contributing curves and checks
consecutive endpoint continuity. A zero-member compound curve is valid EMPTY. Geometry joins compare XYZ when
Z exists, otherwise XY, using the scaled finite-coordinate equality and ignoring M.
Ordinary validity requires neither closure nor simplicity.

Opt in to `SimpleCompoundCurve` to check the whole curve for line/line, line/arc and arc/arc crossings,
interior tangencies, overlaps and retracing, including interactions across independently simple members.
Allowed adjacent joins and one global closure remain valid. The check uses actual arcs and original coordinates.
Geography joins/topology and interactions with full XYZ circles whose plane is undetermined yield explicit warnings.
See the [compound-curve reference](docs/validators.md#compound-curve-rules) for the API, codes, paths and limitations.
