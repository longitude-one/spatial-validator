# Contributing

## Before starting

For a bug report, include a minimal reproducer, the expected behavior,
and your PHP and dependency versions.

Discuss significant changes in a GitHub issue before implementing them.
Public API, compatibility, architecture, and unresolved standards decisions
require maintainer approval.

## Development

Work on a dedicated branch and keep each contribution focused.

- Follow the configured coding standards.
- Cover behavior changes with tests and add regression tests for bug fixes.
- Update documentation when usage or behavior changes.
- Preserve supported PHP and dependency versions.
- Do not weaken tests or quality rules to make checks pass.

See [Tests](tests/README.md) and [Quality tools](quality/README.md)
for setup and commands.

## Before committing

```bash
composer test
composer quality
git diff --check
```

Review your changes and stage only files related to the contribution.
Do not commit dependencies, local lockfiles, caches, or generated reports.

Use Conventional Commits, with an optional scope:

```text
fix(parser): handle empty input
docs: clarify installation
```

Use the commit types configured in [.versionrc.json](.versionrc.json).

## Submitting a pull request

Run the complete checks from the repository root:

```bash
composer check
```

Resolve failures before submitting. In the pull request:

- Link the related issue and explain what changed and why.
- Report the checks performed and their results.
- Highlight compatibility changes and any remaining limitations.

Keep checks passing when updating the pull request.
Contributions require successful CI checks and independent review before merge.

## Local setup and checks

PCOV and the quality tools are development requirements only; consumer installations do not require PCOV.

Use PHP 8.4.1 or later (PHPUnit 13 requirement), Composer 2, Node.js 22 and the PCOV extension.
From a clean checkout:

```sh
composer install
composer update-tools
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
| `composer coverage` | PCOV text, Clover XML and HTML reports under `build/coverage/` |
| `composer coverage-check` | Require 100% source statement coverage from the Clover report |
| `composer cs-check` | PHP-CS-Fixer check without file changes |
| `composer cs-fix` | Apply coding fixes locally |
| `composer phpstan` | PHPStan level 9 over `lib/` and `tests/` |
| `composer phpmd` | PHPMD 3 source and test rulesets |
| `composer phpcs` | PHP_CodeSniffer PSR-2 and shared LongitudeOne checks |
| `composer markdown` | Markdownlint |
| `composer quality` | All static and Markdown checks |
| `composer check` | Root Composer validation, all PHP audits, tests, coverage and quality checks |

Coverage enables PCOV explicitly and restricts instrumentation to `lib/`.
Read `build/coverage/coverage.txt`, `build/coverage/clover.xml`, or `build/coverage/html/index.html` after running it.

Markdown checks use `markdownlint-cli`. Transitive overrides select patched KaTeX, TOML and YAML
versions until the CLI updates its own requirements; keep `npm audit` clean when updating these tools.

## Continuous integration

CI tests the cross product of PHP 8.4/8.5 and Symfony 5.4/6/7/8.
Each entry resolves its own enforced Symfony branch, verifies the installed version and runs PHPUnit.
PHP 8.4.0 is excluded from development because PHPUnit 13 requires PHP 8.4.1 or later.
PCOV is installed only in coverage jobs. Matrix dependency installation ignores
only the development requirement `ext-pcov`; all PHP and Symfony constraints remain enforced.
A separate job runs PHP quality checks and enforces 100% source statement coverage with PCOV.
Jobs use Ubuntu 24.04 and Node.js 24-compatible Actions.
The quality job runs strict validation of the root Composer manifest and audits all PHP dependency environments
using the dedicated Composer scripts; a failed validation or audit fails the job.
Tool manifests are not strictly validated. Markdown checks run separately, including on documentation-only PRs.
CI sends only Clover XML to Codecov, without HTML/text coverage reports or coverage artifacts.
A lightweight workflow updates Codecov after relevant changes reach `main`.
See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

## Release tooling

`npm run release -- --dry-run` previews release generation without changing files, commits or tags.
`commit-and-tag-version` uses `.versionrc.json` for tags without a prefix, Conventional Commits
changelog sections and installation version updates in [README.md](README.md). See [CHANGELOG.md](CHANGELOG.md).
Actual releases require the maintainer's authorization.
