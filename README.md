# Spatial Validator

Standalone Symfony validation for LongitudeOne spatial types. MIT licensed; requires PHP 8.4 or later within PHP 8.
Symfony Validator 5.4, 6, 7 and 8 are supported. There is no dependency on `spatial-types`.

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
| `composer check` | Composer validation, tests, coverage threshold and all quality checks |

Coverage enables PCOV explicitly and restricts instrumentation to `lib/`.
Read `build/coverage.txt`, `build/clover.xml`, or `build/coverage/index.html` after running it.

## Temporary constraint

`LongitudeOne\SpatialValidator\Temporary\NonNegativeInteger` exists only to exercise the Symfony Validator engine
and the quality pipeline. It accepts positive integers and zero and reports a violation for negative integers.
Other types are rejected by the engine. It has no stable public API commitment.

The temporary constraint, its validator and their tests will be removed in
[Story 2 (#3)](https://github.com/longitude-one/spatial-validator/issues/3), when existing validators are transferred.
No geometric validation or normative conformity is claimed by this scaffold.

## Continuous integration

CI tests the cross product of PHP 8.4/8.5 and Symfony 5.4/6/7/8.
Each entry resolves its own enforced Symfony branch, verifies the installed version and runs PHPUnit.
PHP 8.4.0 is excluded from development because PHPUnit 13 requires PHP 8.4.1 or later.
A separate job runs all quality checks and enforces 100% source statement coverage with PCOV.
See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

## Release tooling

`npm run release -- --dry-run` previews release generation without changing files, commits or tags.
`commit-and-tag-version` uses `.versionrc.json`, copied from `spatial-types`, for the `v` tag prefix
and Conventional Commits changelog sections. See [CHANGELOG.md](CHANGELOG.md).
Actual releases require the maintainer's authorization.
