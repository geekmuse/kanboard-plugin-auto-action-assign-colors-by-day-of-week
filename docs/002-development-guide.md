---
date_created: 2026-03-29
date_modified: 2026-03-29
status: draft
audience: both
cross_references:
  - docs/001-architecture.md
  - docs/003-documentation-standards.md
  - CLAUDE.md
---

# Development Guide — AssignColorsByDayOfWeek

## Prerequisites

- PHP 7.4+ (match your target Kanboard version's PHP requirement)
- A running Kanboard instance (≥1.2.19) for manual integration testing
- Composer (optional; for dev tooling like PHPUnit, PHPStan, PHP-CS-Fixer)
- Git

## Getting Started

```bash
# Clone into Kanboard's plugins directory for live testing
git clone https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week.git \
    /path/to/kanboard/plugins/AssignColorsByDayOfWeek

# Or clone standalone for development
git clone https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week.git
cd plugin-auto-action-assign-colors-by-day-of-week

# Install dev dependencies
composer install

# Verify PHP syntax on all files
find . -name "*.php" -not -path "./vendor/*" | xargs php -l
```

## Development Workflow

### 1. Branch from main

```bash
git checkout main
git pull origin main
git checkout -b <type>/<short-name>
```

Branch types: `feat/`, `fix/`, `docs/`, `chore/`, `refactor/`, `test/`

### 2. Make Changes

- Follow the coding style in `CLAUDE.md` (PSR-12)
- Keep changes focused — one logical change per branch
- Write/update tests for any behavior changes
- For non-trivial features, write a spec first in `docs/specs/`

### 3. Test

```bash
# Syntax check all PHP files
find . -name "*.php" -not -path "./vendor/*" | xargs php -l

# Run PHPUnit (once test suite is configured)
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/Action/AssignColorsByDayOfWeekTest.php

# Static analysis
./vendor/bin/phpstan analyse
```

### 4. Lint & Format

```bash
# Check PSR-12 compliance (via Docker — no local PHP needed)
bash scripts/docker-phpcs.sh

# Syntax check
bash scripts/docker-lint.sh
```

### 5. Commit

Use [conventional commits](https://www.conventionalcommits.org/):

```bash
git add .
git commit -m "feat(action): add Saturday/Sunday color support"
```

### 6. Push

```bash
git push origin <branch-name>
```

## Project Structure

```
plugin-auto-action-assign-colors-by-day-of-week/
├── Plugin.php                    # Plugin entry point — registers action, metadata
├── Action/
│   └── AssignColorsByDayOfWeek.php  # Core action: event handling, color resolution
├── tests/
│   ├── Action/
│   │   └── AssignColorsByDayOfWeekTest.php  # PHPUnit unit tests
│   ├── Stubs/
│   │   └── KanboardStubs.php     # Minimal Kanboard class stubs for local dev
│   └── bootstrap.php             # PHPUnit bootstrap (loads stubs or Kanboard autoloader)
├── scripts/
│   ├── docker-lint.sh            # PHP syntax check via kanboard/kanboard image
│   ├── docker-phpcs.sh           # PSR-12 check via cytopia/phpcs image
│   ├── docker-phpstan.sh         # Static analysis via php:8.4-cli image
│   └── docker-test.sh            # PHPUnit via php:8.4-cli image
├── .gitea/
│   └── workflows/
│       └── ci.yml                # Gitea Actions CI pipeline
├── docs/
│   ├── 001-architecture.md       # System architecture
│   ├── 002-development-guide.md  # This file
│   ├── 003-documentation-standards.md
│   ├── specs/                    # Feature specifications
│   ├── adrs/                     # Architecture Decision Records
│   ├── references/               # API docs, glossary
│   ├── tasks/
│   │   └── 001-gap-analysis.md   # Gap analysis and remediation task list (complete)
│   └── research/                 # Spikes, investigations
├── README.md
├── AGENTS.md
├── CLAUDE.md
├── CHANGELOG.md
├── LICENSE
├── composer.json                 # Dev dependencies (phpunit, phpstan)
├── phpunit.xml.dist              # PHPUnit configuration
├── phpstan.neon                  # PHPStan configuration (level 5)
├── prek.toml                     # Git hook configuration
├── .editorconfig
├── .gitattributes
└── .gitignore
```

## Testing Strategy

| Layer | Tool | Location |
|-------|------|----------|
| Unit tests | PHPUnit 11 | `tests/Action/` |
| Integration tests | Manual / Kanboard test instance | Kanboard plugin sandbox |
| Static analysis | PHPStan | Project root |

### Writing Tests

- Test class naming: `<ClassName>Test` in the same namespace under `tests/`
- Test method naming: `test<DescriptiveName>` (e.g., `testReturnsCorrectColorForMonday`)
- Mock Kanboard's container/dependencies using PHPUnit mocks or test doubles
- Test `hasRequiredCondition()` and `doAction()` independently
- Test `getColorForDay()` for each day mapping

## Code Quality Tools

| Tool | Purpose | Command |
|------|---------|---------|
| cytopia/phpcs (Docker) | PSR-12 check | `bash scripts/docker-phpcs.sh` |
| PHPStan | Static analysis (level 5) | `bash scripts/docker-phpstan.sh` |
| PHPUnit 11 | Unit testing | `bash scripts/docker-test.sh` |
| php -l (Docker) | Syntax check | `bash scripts/docker-lint.sh` |

## Git Hooks

Git hooks enforce code quality and commit conventions automatically. This project uses
**prek** to manage hooks via `prek.toml` at the project root.

### Setup

After cloning, run:

```bash
prek install
```

This wires up all hooks defined in `prek.toml`. No other setup is needed.

### Active Hooks

| Hook | What it does | Bypass (emergencies only) |
|------|-------------|--------------------------|
| `pre-commit` | Runs `ralphi check` on staged files | `git commit --no-verify` |
| `commit-msg` | Validates conventional commit format | `git commit --no-verify` |

> ⚠️ **Do not use `--no-verify` routinely.** If a hook is failing, fix the underlying issue.

### commit-msg Hook

The `commit-msg` hook validates that every commit follows
[Conventional Commits](https://www.conventionalcommits.org/):

```
feat(action): add Saturday/Sunday color support  ✅
fix(action): handle missing due date gracefully   ✅
fixed the color bug                               ❌
```

### Commit Message Quick Reference

```
feat(action): add new capability         → MINOR version bump
fix(action): correct a bug               → PATCH version bump
docs: update architecture doc            → PATCH version bump
refactor(action): restructure code       → PATCH version bump
feat(action)!: breaking change           → MAJOR version bump
```

## CI/CD (Recommended)

A CI pipeline should include:

1. **Syntax check** — `find . -name "*.php" | xargs php -l`
2. **Lint** — PHPStan + PHP-CS-Fixer check
3. **Test** — PHPUnit suite
4. **License check** — `composer audit && composer licenses` (once Composer is added)
5. **Doc check** — Validate front-matter in `docs/`

### Doc Validation Script

```bash
# Simple front-matter validation for all docs (add to CI)
for f in $(find docs -name '*.md' -not -name '.gitkeep'); do
  head -1 "$f" | grep -q "^---$" || echo "MISSING FRONT-MATTER: $f"
done
```

## Releasing

### Version Bump

Version is maintained manually in `Plugin.php`. Update the `getPluginVersion()` return
value and update `CHANGELOG.md`:

```php
public function getPluginVersion() {
    return '0.2.3';
}
```

### Release Checklist

1. [ ] All tests pass
2. [ ] `CHANGELOG.md` updated with release notes under new version header
3. [ ] Version bumped in `Plugin.php` → `getPluginVersion()`
4. [ ] Git tag created: `git tag v<version>`
5. [ ] Tag pushed: `git push origin v<version>`

## Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Plugin doesn't appear in Kanboard | Ensure the plugin directory is named `AssignColorsByDayOfWeek` (matches namespace) |
| Colors not assigned | Check `hasRequiredCondition`: task must have default color + non-zero due date + project has action configured |
| Wrong day of week | Timezone is hardcoded to `America/New_York` in `Action/AssignColorsByDayOfWeek.php` — adjust if needed |
| PHP syntax errors on load | Run `find . -name "*.php" | xargs php -l` to locate the issue |
