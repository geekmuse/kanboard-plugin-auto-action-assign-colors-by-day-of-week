# CLAUDE.md — AssignColorsByDayOfWeek

> Claude-specific instructions for working in this repository.
> Read `AGENTS.md` first for general agent conventions.

## Project Context

- **Name:** `plugin-auto-action-assign-colors-by-day-of-week`
- **Language:** PHP (PSR-12, PSR-4)
- **Description:** A Kanboard plugin that automatically assigns task card colors based on the day of the week of a task's due date, triggered at task creation time

## Coding Style

### PHP-Specific Rules

- Follow **PSR-12** coding standard (4-space indent, LF line endings, blank line after namespace/use blocks)
- Follow **PSR-4** autoloading — class `Kanboard\Plugin\AssignColorsByDayOfWeek\Action\AssignColorsByDayOfWeek` lives at `Action/AssignColorsByDayOfWeek.php`
- Use typed method signatures where PHP version allows: `function foo(string $bar): bool`
- PHPDoc blocks on every class and every public/protected method
- No inline FQCN — always declare `use` at the top of the file

### General Rules (All Languages)

- Prefer explicit over implicit
- Write small, focused methods (≤30 lines as a guideline)
- Name things clearly — avoid abbreviations except widely-known ones (e.g., `ID`, `URL`)
- Error messages must be actionable: say what went wrong AND how to fix it
- No commented-out code in commits — use version control history instead

### Formatting

- Follow `.editorconfig` settings (4-space indent for PHP, 2-space for everything else)
- Run PHP-CS-Fixer before committing: `./vendor/bin/php-cs-fixer fix .`
- Let the formatter win — do not manually override its decisions

### Imports / Dependencies

- Declare all `use` statements at the top of the file, after the namespace declaration
- Group imports: PHP built-ins first, then Kanboard core, then plugin-internal
- One class per `use` statement — no grouped braces
- No wildcard imports (`use Kanboard\Model\*` is forbidden)

**Example:**

```php
<?php

namespace Kanboard\Plugin\AssignColorsByDayOfWeek\Action;

use PDO;
use DateTime;
use DateTimeZone;

use Kanboard\Model\TaskModel;
use Kanboard\Model\ColorModel;
use Kanboard\Action\Base;
```

## Testing

### PHP Testing Rules

- Framework: **PHPUnit**
- Test class location: `tests/Action/AssignColorsByDayOfWeekTest.php` (mirrors source structure)
- Test class namespace: `Kanboard\Plugin\AssignColorsByDayOfWeek\Tests\Action`
- Test method naming: `testDescriptiveName` (e.g., `testReturnsCorrectColorForMonday`)
- Mock Kanboard's container/DB dependencies using PHPUnit mocks — do not require a live DB for unit tests
- Test `hasRequiredCondition()` and `doAction()` independently

### General Testing Rules

- Test behavior, not implementation details
- Each test method has a single clear assertion focus
- Avoid mocking internal implementation — mock at boundaries (DB, container)

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/) for every commit. See `AGENTS.md` for the full spec.

### Format

```
<type>(<scope>): <imperative description>
```

- Subject line: imperative mood ("add", not "added"), ≤72 characters, no period
- Scope: the module or area affected (see `AGENTS.md` for scope list)
- Body: explain what and why, not how — wrap at 72 characters

### Examples for This Project

```bash
# Feature
git commit -m "feat(action): add Saturday and Sunday color support"

# Bug fix
git commit -m "fix(action): use prepared statements to prevent SQL injection"

# Documentation
git commit -m "docs: add ADR for hardcoded timezone decision"

# Multi-line with body
git commit -m "refactor(action): extract day-of-week resolution into helper method

The color resolution logic was embedded directly in doAction().
Extracting it into getColorForDay() improves testability and
makes the method boundaries clearer."

# Breaking change
git commit -m "feat(action)!: rename action parameters to ISO weekday numbers

BREAKING CHANGE: Parameters are now keyed by ISO weekday number
(1=Monday through 7=Sunday). Existing action configs must be
reconfigured after upgrade."

# Chore
git commit -m "chore(deps): add phpunit and phpstan as dev dependencies"

# Test
git commit -m "test(action): add unit tests for hasRequiredCondition"
```

### Rules

- One logical change per commit — don't bundle unrelated changes
- Run tests and linting before committing (the pre-commit hook enforces this)
- Never use `--no-verify` to skip hooks

## File Creation Conventions

When creating new files:

- **Source files:** `<ClassName>.php` in the appropriate PSR-4 namespace directory
- **Test files:** `<ClassName>Test.php` under `tests/`, mirroring the source directory structure
- **Feature specs:** `docs/specs/NNN-feature-name.md` with front-matter
- **ADRs:** `docs/adrs/NNN-decision-title.md` with front-matter
- **Reference docs:** `docs/references/NNN-topic.md` with front-matter
- **Task breakdowns:** `docs/tasks/NNN-task-name.md` with front-matter
- **Research/spikes:** `docs/research/NNN-topic.md` with front-matter
- **Config files:** Project root

See `AGENTS.md` for directory purpose and `docs/003-documentation-standards.md` for full rules.

## Patterns to Follow

- **Extend `Kanboard\Action\Base`** for all action classes — it provides the DI container,
  `$this->db`, model accessors, and the standard action lifecycle
- **Implement all required abstract methods:** `getDescription()`, `getCompatibleEvents()`,
  `getActionRequiredParameters()`, `getEventRequiredParameters()`, `doAction()`,
  `hasRequiredCondition()`
- **Check conditions in `hasRequiredCondition()`** — keep `doAction()` unconditional and focused
- **Use `$this->taskModificationModel->update()`** for task updates — do not write SQL directly
  for task mutations
- **Use Kanboard's translation function `t()`** for all user-visible strings

## Anti-Patterns to Avoid

- **Raw SQL with string interpolation** — the existing `getColorSettings()` and
  `projectHasCustomColors()` methods use unsafe string interpolation; new code must use
  PDO prepared statements:
  ```php
  // ❌ Do not do this
  $this->db->getConnection()->query("SELECT ... WHERE project_id = " . $projectId);

  // ✅ Do this instead
  $stmt = $this->db->getConnection()->prepare("SELECT ... WHERE project_id = ?");
  $stmt->execute([$projectId]);
  ```
- **Hardcoding user-facing configuration** — timezone, event triggers, and weekday lists
  should eventually be configurable; add a TODO/ADR if you must hardcode
- **Bypassing the action lifecycle** — always implement `hasRequiredCondition()` rather
  than putting guards inside `doAction()`
- **Catching all exceptions silently** — let Kanboard's error handling bubble up; only
  catch specific, recoverable exceptions

## Common Tasks

### Adding a New Feature

1. Create a feature branch: `git checkout -b feat/<name>`
2. Write a spec if non-trivial: `docs/specs/NNN-feature-name.md`
3. Implement the feature with tests
4. Update relevant docs if behavior changes
5. Update `CHANGELOG.md` under `[Unreleased]`
6. Commit with `feat(<scope>): <description>`

### Fixing a Bug

1. Create a fix branch: `git checkout -b fix/<name>`
2. Write a failing test that reproduces the bug
3. Fix the bug
4. Verify the test passes
5. Commit with `fix(<scope>): <description>`

### Making a Technical Decision

1. If research is needed, create `docs/research/NNN-topic.md` first
2. Create an ADR: `docs/adrs/NNN-decision-title.md`
3. Include context, decision, consequences, alternatives
4. Cross-reference the research doc if applicable
5. Commit with `docs: add ADR for <decision>`

### Updating Documentation

1. Identify the correct file and directory (see `AGENTS.md` directory table)
2. Edit the file
3. Bump `date_modified` in front-matter
4. Update cross-references if needed
5. Commit with `docs: <description>`

### Bumping the Version

1. Update `getPluginVersion()` in `Plugin.php`
2. Update `CHANGELOG.md` — move `[Unreleased]` entries under the new version header
3. Commit with `chore: bump version to X.Y.Z`
4. Tag: `git tag vX.Y.Z && git push origin vX.Y.Z`

## Decision-Making Preferences

When multiple approaches are viable:

1. **Prefer Kanboard's built-in APIs** over custom DB queries or direct model access
2. **Prefer readability** over cleverness
3. **Prefer existing patterns** in the codebase over introducing new ones
4. **Prefer small, focused changes** — split large refactors into independent commits
5. **When truly uncertain**, document the tradeoffs in an ADR (`docs/adrs/`) and pick the simpler option
