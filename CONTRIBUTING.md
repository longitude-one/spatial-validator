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
