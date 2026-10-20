# Contributing

Read the approved GitHub work item and its acceptance criteria before changing code.
Keep changes within its scope and preserve the documented PHP and Symfony compatibility.

Follow the installation steps in [README.md](README.md), add appropriate tests and run `composer check`.
Run `composer cs-fix` to apply coding style fixes; CI uses the non-mutating `composer cs-check`.
Do not commit dependencies, caches or generated reports. Lockfiles remain local and are ignored.

Changes require independent review. Public API, architectural, breaking and unresolved normative decisions require
approval from the project's technical architect.
