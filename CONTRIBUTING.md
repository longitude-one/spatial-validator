# Contributing

Read the approved GitHub work item and its acceptance criteria before changing code.
Keep changes within its scope and preserve the documented PHP and Symfony compatibility.

Follow the installation steps in [README.md](README.md), add appropriate tests and run `composer check`.
Run `composer cs-fix` to apply coding style fixes; CI uses the non-mutating `composer cs-check`.
PHPMD uses the version-controlled `pdepend.yml` memory cache so each invocation recomputes metrics.
This prevents a user-wide PDepend cache from hiding complexity violations that a clean CI runner detects.
PHPMD's `--no-cache` option only disables its result cache; it does not disable PDepend's metric cache.

Do not commit dependencies, caches or generated reports. Lockfiles remain local and are ignored.

Changes require independent review. Public API, architectural, breaking and unresolved normative decisions require
approval from the project's technical architect.

## Composer commands

Prepare the PHP and Markdown tools before running quality checks:

```bash
composer install
composer update-tools
npm install --ignore-scripts
```

`composer update-tools` updates only the four isolated PHP quality environments.
Use `composer update` separately to update the library dependencies. Composer
lockfiles remain local and ignored.

| Command | Purpose |
| --- | --- |
| `composer test` | Run PHPUnit without coverage |
| `composer coverage` | Generate coverage reports |
| `composer coverage-check` | Check the coverage threshold |
| `composer cs-check` | Check formatting without changing files |
| `composer cs-fix` | Apply formatting corrections |
| `composer phpstan` | Run static analysis |
| `composer phpmd` | Analyze production code and tests |
| `composer phpcs` | Check coding standards |
| `composer markdown` | Check Markdown |
| `composer quality` | Check PHP and Markdown without changing source files |
| `composer update-tools` | Update PHP quality tools |
| `composer audit-dependencies` | Audit PHP dependencies |
| `composer check` | Run all checks |

The coverage threshold is 100%.

## Continuous integration

Pull requests run the supported PHP combinations and a separate Markdown check
when relevant files change. Superseded pull request runs are cancelled.
The `CI` check summarizes the relevant jobs, including documentation-only changes.

CI uses `composer coverage-ci` with `phpunit.ci.xml` to generate only
`build/coverage/clover.xml` for Codecov. No HTML/text coverage reports or coverage
artifacts are produced. `composer coverage` keeps the local reports unchanged.
After relevant changes reach `main`, a lightweight workflow updates Codecov
without repeating the PHP quality checks or compatibility matrix.

Weekly runs resolve current dependencies and collect the complete matrix results.
Composer and npm caches contain downloads; lockfiles remain local and ignored.
Matrix jobs without coverage disable the coverage driver. They ignore only the
optional development requirement `ext-pcov` during dependency installation.
