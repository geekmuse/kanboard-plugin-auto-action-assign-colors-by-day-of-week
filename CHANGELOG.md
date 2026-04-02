# Changelog

All notable changes to **AssignColorsByDayOfWeek** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.3] - 2026-04-02

### Removed
- CodeQL workflow — CodeQL CLI dropped its PHP extractor so the workflow
  could never succeed; CI pipeline already covers linting, static analysis,
  and tests

## [0.2.2] - 2026-04-02

### Changed
- Bump `actions/checkout` from 4.3.1 to 6.0.2 across all GitHub Actions
  workflows (`ci.yml`, `codeql.yml`, `release.yml`)
- Widen `phpstan/phpstan` Composer requirement from `^1.10` to `^1.10 || ^2.0`

## [0.2.1] - 2026-03-31

### Added
- GitHub Actions CI workflow mirroring the existing Gitea pipeline (lint,
  Composer install, PHPCS, PHPStan, PHPUnit)
- GitHub Actions release workflow: builds distributable plugin ZIP
  (`AssignColorsByDayOfWeek-{version}.zip`) and publishes a GitHub Release
  on semver tags; supports prerelease suffixes (e.g. `v0.2.1-rc.1`)
- CodeQL SAST workflow — PHP `security-extended` query suite on push/PR
  to `main` and weekly schedule; results surface in GitHub Security tab
- `.github/dependabot.yml` — weekly Dependabot PRs for `github-actions`
  and `composer` ecosystems
- `SECURITY.md` — supported versions table, private disclosure email,
  72 h acknowledgement / 14-day resolution SLA

### Changed
- All GitHub Actions `uses:` references pinned to full commit SHAs
  (supply-chain hardening; Dependabot will keep them current)
- Release job now requires manual approval via a `release` GitHub
  environment before any artifact is published

### Security
- Enabled GitHub vulnerability alerts and automated security-fix PRs
- Applied branch protection on `main`: force-pushes blocked, required
  status check `CI / ci` must pass before merge
- Added tag protection ruleset (`deletion` + `non_fast_forward` rules
  on `refs/tags/v*`): existing release tags cannot be deleted or moved
- Disabled unused repository surface area (wiki, projects)

## [0.2.0] - 2026-03-29

### Added
- Saturday and Sunday color parameters — all seven days now configurable; a "No change"
  sentinel option lets users leave weekend tasks untouched (GAP-07)
- Configurable timezone action parameter backed by PHP's full IANA identifier list;
  "Server default" sentinel falls back to `date_default_timezone_get()` — hardcoded
  `America/New_York` removed entirely (GAP-06)
- Day-of-week guard in `hasRequiredCondition()` — resolves day from `date_due` and
  returns `false` when no color is configured for that day; prevents silent `null`
  writes to `color_id` for weekend due dates (GAP-02, GAP-05)
- PHPDoc blocks on all classes and methods — `@package`, `@author`, `@param`,
  `@return` (GAP-09)
- `composer.json` with `phpunit/phpunit ^11.0` and `phpstan/phpstan ^1.10` dev
  dependencies, PSR-4 autoloading, and a `test` Composer script (GAP-13)
- `phpunit.xml.dist`, `phpstan.neon` configuration files
- `tests/Action/AssignColorsByDayOfWeekTest.php` — 6 unit tests, 8 assertions covering
  `hasRequiredCondition()` and `doAction()` across all fixed behavior paths
- `tests/Stubs/KanboardStubs.php` — minimal Kanboard class stubs for local development
  without the Docker image
- `tests/bootstrap.php` — loads Kanboard autoloader in Docker or local stubs otherwise
- `.gitea/workflows/ci.yml` — Gitea Actions CI pipeline with 5 stages: lint, Composer
  install, PHPCS, PHPStan, PHPUnit (GAP-14)
- `LICENSE` — MIT license file (year 2026, Brad Campbell) (GAP-15)
- `docs/tasks/001-gap-analysis.md` — comprehensive gap analysis with 16 gaps and 15
  prioritized remediation tasks
- `.ralphi/config.yaml` — ralphi project configuration with Docker entrypoint caveat
  and project coding rules
- `scripts/docker-{lint,phpcs,phpstan,test}.sh` — Docker-based quality check scripts

### Changed
- ⚠️ **BREAKING** Fix i18n parameter key mismatch (GAP-03): `getActionRequiredParameters()`
  now uses fixed English strings (`'Monday'`…`'Sunday'`) instead of `t('Monday')` etc.
  as parameter keys; lookup also uses fixed English strings matching `DateTime::format('l')`.
  **Existing configurations saved under a non-English Kanboard installation must be
  re-saved after upgrading.**
- Replace raw SQL helpers with `getParam()` API (GAP-01, GAP-04): removed
  `projectHasCustomColors()` and `getColorSettings()`; `getColorForDay()` reads colors
  via `$this->getParam($day)` with no DB queries; `use PDO` import removed
- Normalize all indentation to PSR-12 4-space style in both PHP files (GAP-08)
- Remove phantom `use Kanboard\Model\ColorModel` import; replaced with
  `@property \Kanboard\Model\ColorModel $colorModel` class PHPDoc annotation (GAP-10)
- Change loose `==` to strict `===` in `hasRequiredCondition()` color comparison (GAP-11)
- `getDescription()` now explicitly states "based on the day of the week of the task's
  due date" (GAP-12)
- `getPluginHomepage()` now returns the repository URL instead of the author's personal
  site (GAP-16)
- `docs/001-architecture.md` updated to reflect corrected implementation; status `active`

## [0.1.0] - 2026-03-29

### Added
- Initial plugin implementation
- `Plugin.php` — registers `AssignColorsByDayOfWeek` action with Kanboard's ActionManager
- `Action/AssignColorsByDayOfWeek.php` — assigns task card colors based on the day of week of the task's due date (Mon–Fri), triggered on `EVENT_CREATE`
- Configurable per-project day→color mapping via Kanboard's automatic action UI

[Unreleased]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.2.3...HEAD
[0.2.3]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/releases/tag/v0.1.0
