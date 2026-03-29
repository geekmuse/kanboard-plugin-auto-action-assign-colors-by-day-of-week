# Changelog

All notable changes to **AssignColorsByDayOfWeek** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://git.bradleyscampbell.net/geekmuse-kanboard/plugin-auto-action-assign-colors-by-day-of-week/compare/v0.1.0...HEAD
[0.1.0]: https://git.bradleyscampbell.net/geekmuse-kanboard/plugin-auto-action-assign-colors-by-day-of-week/releases/tag/v0.1.0
