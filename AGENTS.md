# AGENTS.md — AssignColorsByDayOfWeek

> **Audience:** AI agents and automated tools working on this repository.
> **Read this file first** before making any changes.

## Project Overview

- **Name:** `plugin-auto-action-assign-colors-by-day-of-week`
- **Plugin Class:** `AssignColorsByDayOfWeek`
- **Language:** PHP
- **Purpose:** Kanboard automation plugin — assigns configurable colors to task cards based on the day of week of the task's due date, triggered at task creation
- **Current Version:** 0.2.3 (see `Plugin.php` → `getPluginVersion()`)
- **Kanboard Compatibility:** `>=1.2.19`
- **Status:** Early development

## Repository Structure

```
plugin-auto-action-assign-colors-by-day-of-week/
├── Plugin.php                           # Plugin entry point — registers action, metadata
├── Action/
│   └── AssignColorsByDayOfWeek.php      # Core action: event handling, color resolution
├── README.md                            # Human-facing project documentation
├── AGENTS.md                            # THIS FILE — agent-facing guidance
├── CLAUDE.md                            # Claude-specific instructions
├── CHANGELOG.md                         # Version history (Keep a Changelog format)
├── prek.toml                            # Git hook configuration (prek)
├── .editorconfig                        # Editor formatting rules
├── .gitattributes                       # Git line-ending normalization
└── docs/
    ├── 001-architecture.md              # System architecture and design
    ├── 002-development-guide.md         # Development workflow and tooling
    ├── 003-documentation-standards.md  # How docs are structured
    ├── specs/                           # Feature specifications and design docs
    ├── adrs/                            # Architecture Decision Records
    ├── references/                      # API docs, glossary, external refs
    ├── tasks/                           # Work items, backlogs, sprint plans
    └── research/                        # Spikes, investigations, POC write-ups
```

## Conventions

### Commit Messages

This project enforces [Conventional Commits](https://www.conventionalcommits.org/). Every commit **must** follow this format:

```
<type>(<scope>): <short description>    ← subject line (≤72 chars, imperative mood)

[optional body]                          ← what and why, not how (wrap at 72 chars)

[optional footer(s)]                     ← BREAKING CHANGE, issue refs
```

**Types:**

| Type | When to use | Version impact |
|------|------------|----------------|
| `feat` | New feature or capability | MINOR bump |
| `fix` | Bug fix | PATCH bump |
| `docs` | Documentation only (no code change) | PATCH bump |
| `style` | Formatting, whitespace (no logic change) | PATCH bump |
| `refactor` | Code restructuring (no feature/fix) | PATCH bump |
| `perf` | Performance improvement | PATCH bump |
| `test` | Adding or fixing tests | PATCH bump |
| `chore` | Build, tooling, dependencies | PATCH bump |
| `ci` | CI/CD configuration | PATCH bump |

**Scopes for this project:**

| Scope | Covers |
|-------|--------|
| `action` | `Action/AssignColorsByDayOfWeek.php` — core action logic |
| `plugin` | `Plugin.php` — entry point, metadata, registration |
| `docs` | Documentation in `docs/` |
| `ci` | CI/CD, GitHub Actions |
| `deps` | Composer dependencies |

**Examples:**

```
feat(action): add Saturday and Sunday color support
fix(action): use prepared statements instead of raw SQL interpolation
fix(action): handle missing due date without fatal error
refactor(action): extract timezone to configurable constant
docs: add ADR for hardcoded timezone decision
chore(deps): add phpunit and phpstan as dev dependencies
```

**Breaking changes:**

```
feat(action)!: change color parameter names from day names to ISO weekday numbers

BREAKING CHANGE: Action parameters now use numeric keys (1=Monday, 7=Sunday).
Existing configurations must be reconfigured after upgrade.
```

**Enforcement:** A `commit-msg` git hook (via prek) validates the format on every commit.

### Branching

- `main` — stable, release-ready
- `feat/<name>` — new features
- `fix/<name>` — bug fixes
- `docs/<name>` — documentation changes
- `chore/<name>` — maintenance tasks
- `refactor/<name>` — refactoring work

### Code Style

- PSR-12 coding standard (4-space indentation for PHP)
- PHP 7.4+ syntax where possible (typed properties, arrow functions)
- Every class and public method must have a PHPDoc block
- Use `use` statements for all external classes — no FQCN inline in code

### File Naming

- Source code: `PascalCase.php` (PSR-4 autoloading convention)
- Docs: `NNN-kebab-case-title.md` within the appropriate `docs/` subdirectory
- Tests: `<ClassName>Test.php` mirroring the source structure under `tests/`

## Documentation Rules

### Front-Matter (Required for all docs)

Every markdown file in `docs/` and its subdirectories must include:

```yaml
---
date_created: YYYY-MM-DD
date_modified: YYYY-MM-DD
status: draft | active | review | deprecated
audience: human | agent | both
cross_references:
  - docs/001-architecture.md
---
```

### Directory Purpose

| Directory | What goes here | When to create a file |
|-----------|---------------|----------------------|
| `docs/` (root) | Foundational, cross-cutting docs | New cross-cutting concern |
| `docs/specs/` | Feature specs, design docs | Before or during feature implementation |
| `docs/adrs/` | Architecture Decision Records | When making a significant technical decision |
| `docs/references/` | API docs, glossary, config reference | When a stable interface needs documentation |
| `docs/tasks/` | Work items, backlogs | When breaking down a body of work |
| `docs/research/` | Spikes, investigations, POCs | When evaluating a tool, approach, or pattern |

### Numbered Files

Files within each directory are numbered `NNN-kebab-case-title.md`:
- Sequential within each directory (001, 002, 003...)
- Leave gaps of 5–10 between files to allow insertions
- **Never renumber existing files** — cross-references would break

## Versioning

Version is maintained manually in `Plugin.php` → `getPluginVersion()`. There is no
Composer-based version tooling.

**Semantic Versioning (semver):**

| Change Type | Version Bump | Example |
|-------------|-------------|---------|
| Breaking change | MAJOR | Rename action parameters, change event trigger |
| New feature | MINOR | Add weekend support, new color option |
| Bug fix / docs | PATCH | Fix timezone issue, update README |

Update `CHANGELOG.md` with every version bump.

## Task Decomposition (for agents)

When picking up work:

1. **Read this file first** to understand current state
2. **Read `docs/001-architecture.md`** for system context
3. **Check `docs/tasks/`** for outstanding and in-progress work
4. **Check `CHANGELOG.md`** for recent changes and current version
5. **Break work into atomic tasks** — each task should:
   - Touch ≤5 files when possible
   - Have clear "done" criteria
   - Be completable in a single session
   - Be documented in `docs/tasks/` if non-trivial
6. **Commit frequently** with conventional commit messages
7. **Update docs** if your changes affect documented behavior

### Context Window Management

- Individual docs are kept under 500 lines
- Use cross-references instead of duplicating content
- Front-load critical information (inverted pyramid style)
- Prefer tables and lists over prose for structured data

### Key Pitfalls to Avoid

- **getParam API (IMPORTANT):** Use `$this->getParam($key)` to read action parameters — ActionManager pre-populates `$this->params` from `action_has_params` before `execute()` is called. Never query the DB to read action params.
- **Parameter key strategy:** The keys in `getActionRequiredParameters()` are stored verbatim as `action_has_params.name`. The key passed to `getParam()` must exactly match. Currently using `t('Monday')` etc. — US-002 will change to fixed English strings.
- **PSR-12 brace style:** PHP classes AND methods require opening brace on its own line (Allman style). Run `docker run --rm -v $(pwd):/app cytopia/phpcs:latest --standard=PSR12 /app/Plugin.php /app/Action/` to verify when vendor/ is not yet set up.
- **Plugin directory name:** Must be `AssignColorsByDayOfWeek` (not the repo name) for Kanboard's namespace resolution to work
- **Timezone:** Day-of-week resolution is still hardcoded to `America/New_York` — will be made configurable in US-005

## Current Work Items

<!-- Agents: update this section as work progresses -->
<!-- For detailed task breakdowns, see docs/tasks/ -->

All gap-analysis remediation stories delivered in v0.2.0. No open items.

- [x] **US-001** Replace raw SQL with getParam() API (GAP-01, GAP-04) ✅
- [x] **US-002** Fix i18n parameter key strategy — use fixed English keys (GAP-03) ✅
- [x] **US-003** Add day-of-week guard to hasRequiredCondition() (GAP-02, GAP-05) ✅
- [x] **US-004** Add Saturday and Sunday color parameters (GAP-07) ✅
- [x] **US-005** Add configurable timezone action parameter (GAP-06) ✅
- [x] **US-006** PSR-12 indentation, phantom import, and strict comparison fixes (GAP-08, GAP-10, GAP-11) ✅
- [x] **US-007** Add PHPDoc blocks to all classes and methods (GAP-09) ✅
- [x] **US-008** Clarify date_due semantics in description and docs (GAP-12) ✅
- [x] **US-009** Add LICENSE file and fix plugin homepage URL (GAP-15, GAP-16) ✅
- [x] **US-010** Add composer.json and PHPUnit test suite (GAP-13) ✅
- [x] **US-011** Add CI pipeline (GAP-14) ✅
