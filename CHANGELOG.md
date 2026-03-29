# Changelog

All notable changes to **AssignColorsByDayOfWeek** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **[US-007]** Add PHPDoc blocks to all classes and methods (closes GAP-09): class-level `@package`/`@author` tags on both `Plugin.php` and `Action/AssignColorsByDayOfWeek.php`; full `@param`/`@return` annotations on every public and private method; existing class PHPDoc on `AssignColorsByDayOfWeek` extended with `@package` and `@author`; `@property \Kanboard\Model\ColorModel $colorModel` already present from US-006
- **[US-001]** Replace raw SQL helpers with `getParam()` API: removed `projectHasCustomColors()` and `getColorSettings()` private methods; `getColorForDay()` now reads colors via `$this->getParam(t($dayOfWeek))` with no DB queries (fixes GAP-01 SQL injection, GAP-04 bypassing ActionManager API)
- **[US-001]** Remove `use PDO` import (no longer needed)
- **[US-001]** Normalize PSR-12 brace style and 4-space indentation in `Plugin.php` and `Action/AssignColorsByDayOfWeek.php`
- **[US-006]** Remove phantom `use Kanboard\Model\ColorModel` import (GAP-10): `$colorModel` is resolved via DI container magic `__get` — no explicit `use` needed; replaced with a `@property \Kanboard\Model\ColorModel $colorModel` annotation on the class PHPDoc for static-analysis visibility
- **[US-006]** Fix loose `==` to strict `===` in `hasRequiredCondition()` (GAP-11): `color_id` and `getDefaultColor()` are both strings; `===` is PSR-12-idiomatic and prevents future type-divergence breakage
- **[US-006]** Add class-level PHPDoc to `AssignColorsByDayOfWeek` describing its purpose
- **[US-005]** Add configurable timezone action parameter (fixes GAP-06): `getActionRequiredParameters()` now includes a `'Timezone'` parameter backed by PHP's full IANA timezone identifier list (plus a "Server default" sentinel that falls back to `date_default_timezone_get()`); hardcoded `'America/New_York'` removed entirely; day-of-week resolution extracted into a shared `resolveDayOfWeek()` private helper used by both `hasRequiredCondition()` and `getColorForDay()`
- **[US-004]** Add Saturday and Sunday color parameters (fixes GAP-07): `getActionRequiredParameters()` now includes `'Saturday'` and `'Sunday'` entries; a "No change" sentinel option (empty-string key) is prepended to the color list for every day so users can explicitly leave a day's task color unchanged; `hasRequiredCondition()` already treats `''` as "skip" — no doAction() call, task color unchanged
- **[US-003]** Add day-of-week guard to `hasRequiredCondition()` (fixes GAP-02, GAP-05): resolves the day of week from `date_due` and returns `false` when `$this->getParam($day)` is null or empty — prevents Saturday/Sunday due dates from triggering `doAction()` and silently writing a null `color_id`; `doAction()` is now fully unconditional
- **[US-002]** ⚠️ **BREAKING** Fix i18n parameter key mismatch (GAP-03): `getActionRequiredParameters()` now uses fixed English strings (`'Monday'`…`'Friday'`) instead of `t('Monday')`…`t('Friday')` as parameter keys; `getColorForDay()` drops the `t()` wrapper from its `getParam()` call — both sides now consistently use the English day name that `DateTime::format('l')` always produces, ensuring color assignment works correctly in all locales. Existing configurations saved under a non-English Kanboard installation must be re-saved after upgrading.

### Added
- Initial project scaffold with documentation and conventions
- `README.md` — project overview, setup, and usage
- `AGENTS.md` — agent-facing development guidance
- `CLAUDE.md` — Claude-specific coding instructions
- `docs/001-architecture.md` — system architecture
- `docs/002-development-guide.md` — development workflow
- `docs/003-documentation-standards.md` — documentation conventions
- `docs/specs/` — feature specifications directory
- `docs/adrs/` — architecture decision records directory
- `docs/references/` — reference documentation directory
- `docs/tasks/` — work items directory
- `docs/research/` — research and investigations directory
- `.editorconfig` — cross-editor formatting rules
- `.gitattributes` — line ending normalization
- `prek.toml` — git hook configuration
- `CHANGELOG.md` — this file

## [0.1.0] - 2026-03-29

### Added
- Initial plugin implementation
- `Plugin.php` — registers `AssignColorsByDayOfWeek` action with Kanboard's ActionManager
- `Action/AssignColorsByDayOfWeek.php` — assigns task card colors based on the day of week of the task's due date (Mon–Fri), triggered on `EVENT_CREATE`
- Configurable per-project day→color mapping via Kanboard's automatic action UI

[Unreleased]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week/releases/tag/v0.1.0
