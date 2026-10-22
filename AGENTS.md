# Repository Guidelines

These instructions apply to `longitude-one/spatial-validator`.

## Responsibilities and structure

This library provides explicit Symfony validation for LongitudeOne spatial objects.
Model representation belongs to `spatial-types`; geometry type identifiers belong to `spatial-core`.
Do not introduce reverse dependencies, automatic constructor validation, geometry repair or reprojection.

- `lib/`: production source, PSR-4 namespace `LongitudeOne\SpatialValidator\`.
- `lib/Validator/Constraints/`: Symfony constraints and their matching `<Name>Validator` classes.
- `tests/Functional/`: Symfony integration tests, including dispatch and input contracts.
- `docs/design/`: validation catalogues and approved design context.
- `old/`: temporary historical reference, excluded from production autoload; do not restore it as active API.
- `quality/`: isolated Composer environments and configurations for quality tools.
- `.github/workflows/`: PHP/Symfony compatibility and quality jobs.

Read [README.md](README.md), [CONTRIBUTING.md](CONTRIBUTING.md) and the assigned GitHub Issue before implementation.
Keep documentation in English and update it with observable behavior and public API changes.

## Authority and workflow

GitHub Issues, native parent/dependency relationships, Projects, Pull Requests and Actions
are the operational source of truth.
Read acceptance criteria, comments, parent, blockers and approved decisions before starting an assigned work item.
Follow the shared LongitudeOne spatial governance when available.

Alexandre is the Product Owner and Technical Architect. Product scope, architecture, public API,
breaking changes and unresolved normative interpretations require his approval.
Honor approvals already provided in the current session; do not request the same authorization again.
Implementation details within the approved contract may be decided autonomously.
Report contradictory requirements rather than inventing a resolution.

Preserve user changes and work by other agents. Keep changes focused on the assigned scope.
Respect explicit stopping points, including instructions to stop before committing or publishing.
Changes require independent review. Do not merge without Alexandre's authorization or mark an unmerged item Done.

## Validation contracts

- `Spatial` is the generic Symfony constraint; `SpatialValidator` dispatches by `GeometryTypeEnum` using `match`.
- Each enum case has a dedicated constraint and validator. Keep dispatch coverage exhaustive.
- Check the expected constraint first; a wrong constraint raises Symfony `UnexpectedTypeException`.
- Ignore only PHP `null`; use Symfony `NotNull` separately for required values.
- Reject all strings, including `''` and WKT, and other unsupported values with Symfony `UnexpectedValueException`.
  Direct invocation throws it; the Symfony Validator service converts it into a type violation.
- EMPTY spatial objects are not PHP `null`; do not skip them as missing values.
- Require the applicable specialized interface and declared geometry type; never infer Triangle from Polygon coordinates.
- Delegate through the existing Symfony context, preserving root, property path and active group.
- `Spatial` has no default warning payload. Currently, specialized validators report unavailable geometric validation
  through violations with default payload `['severity' => 'warning']`; these are not validation successes.
  Do not overwrite that payload with the parent constraint's payload.
- Implement geometric rules only when authorized by the corresponding work item; never silently claim coverage.

For spatial semantics, consult applicable standards and approved LongitudeOne decisions.
Do not treat database behavior as normative solely because a vendor implements it.
Keep Geometry/Geography, XY/XYZ/XYM/XYZM, EMPTY and reference compatibility explicit when relevant.
Escalate unresolved normative interpretation before implementing the affected behavior.

## Development and verification

Runtime PHP supports `^8.4`; development requires PHP 8.4.1 or later because of PHPUnit 13.
Use Composer 2, Node.js 22 and PCOV. Supported Symfony branches are 5.4, 6, 7 and 8;
avoid APIs available only in newer branches.

| Command | Purpose |
| --- | --- |
| `composer install` | Install library and PHPUnit dependencies |
| `composer install-tools` | Install the four isolated quality environments |
| `npm install --ignore-scripts` | Install Markdown and release tools |
| `composer test` | Run PHPUnit |
| `composer coverage` | Run tests with PCOV reports under `build/` |
| `composer coverage-check` | Enforce 100% production statement coverage |
| `composer cs-fix` | Apply the configured formatting |
| `composer quality` | Check style, PHPStan level 9, PHPMD, PHPCS and Markdown |
| `composer check` | Validate Composer, audit dependencies, run coverage and quality |

There is no `composer test-local` script. `composer quality` checks formatting without changing files.
PHPMD uses the repository's `pdepend.yml` memory cache to avoid stale user-wide metrics.
For PHP behavior changes, add meaningful tests and run tests, coverage checks and quality.
Before submitting the implementation, run `composer check` and report any failed or unavailable checks.
Local success does not establish that the full PHP/Symfony CI matrix passed.

Use four-space indentation, `declare(strict_types=1);`, explicit types, existing headers,
matching namespaces and directory paths, and the configured PSR-12/style rules.
Do not weaken tests or quality rules merely to pass checks.
The targeted `PHPMD.CouplingBetweenObjects` suppression on `SpatialValidator` is explicitly approved
for its exhaustive dispatch; it is not permission for unrelated suppressions.
Use the parser-compatible annotation `@SuppressWarnings("PHPMD.CouplingBetweenObjects")`.

Tests should cover successful routing, rejected input, wrong constraints, null, EMPTY,
applicable types/dimensions/families, groups, payload and preserved Symfony context.
Use readable fixtures and focused assertions; data providers are appropriate for enum coverage and input matrices.

## Delivery

Use Conventional Commits with types recognized by `.versionrc.json`.
Do not commit dependencies, lockfiles, caches or generated reports.
Pull Requests must link the work item and explain resulting behavior, verification, API/compatibility impact
and any remaining limitations. An independent Reviewer verifies the change before merge.
Only merged work satisfying the shared Definition of Done may be marked Done.
