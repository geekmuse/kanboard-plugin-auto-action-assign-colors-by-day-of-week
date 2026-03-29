---
date_created: 2026-03-29
date_modified: 2026-03-29

> **Status: deprecated** — All 15 tasks (T-01–T-15) delivered in v0.2.0 via
> `ralph/gap-analysis-remediation`. Kept for historical reference.
status: deprecated
audience: both
cross_references:
  - docs/001-architecture.md
  - AGENTS.md
  - CLAUDE.md
---

# Gap Analysis — AssignColorsByDayOfWeek

## 1. Current State

Two PHP files implement the full plugin.

### `Plugin.php`

Registers the action class with Kanboard's `ActionManager` and provides plugin
metadata (name, description, author, version `0.1.0`, homepage, compatible version
`>=1.2.19`). No other lifecycle hooks. Homepage points to the author's personal site
rather than the repository.

### `Action/AssignColorsByDayOfWeek.php`

Implements `Kanboard\Action\Base`. Fires on `TaskModel::EVENT_CREATE` only.

| Method | What it does |
|--------|-------------|
| `getDescription()` | Returns translated description string |
| `getCompatibleEvents()` | Returns `[EVENT_CREATE]` |
| `getActionRequiredParameters()` | Returns Mon–Fri keys (via `t()`) mapped to the full color list |
| `getEventRequiredParameters()` | Requires `task_id`, `task.project_id`, `task.color_id`, `task.date_due` |
| `hasRequiredCondition()` | Guards on: task has default color, project has action configured, due date is set and non-zero |
| `doAction()` | Updates task color by calling `getColorForDay()` |
| `projectHasCustomColors()` *(private)* | Raw SQL COUNT to check if any instance of this action is configured for the project |
| `getColorSettings()` *(private)* | Raw SQL JOIN on `actions` + `action_has_params` to fetch all day→color params for the project |
| `getColorForDay()` *(private)* | Converts due-date timestamp to a day name (in hardcoded `America/New_York` timezone) and looks up the color from the result of `getColorSettings()` |

**Indentation:** mixed — method signatures use 4 spaces (PSR-12), method bodies use
tabs. Both files contain tab-indented lines.

---

## 2. Expected / Ideal State

A correct implementation of this plugin should:

1. Use `$this->getParam($key)` (the API provided by `Kanboard\Action\Base`) to read
   configured day→color mappings for the **current action instance**, rather than
   re-querying the database.
2. Use consistent parameter key casing/language: either fixed English strings throughout,
   or `t()` applied identically in both `getActionRequiredParameters()` and the lookup.
3. Guard all failure paths in `hasRequiredCondition()` so that `doAction()` can run
   unconditionally without risk of returning a null `color_id`.
4. Support all seven days of the week, or explicitly exclude weekend due dates in the
   condition guard.
5. Allow the timezone to be configured per action instance rather than hardcoding
   `America/New_York`.
6. Use PSR-12-compliant, 4-space indented code throughout with PHPDoc on all methods.
7. Include a test suite, a `composer.json` for dev tooling, and a CI pipeline.
8. Have a `LICENSE` file matching the MIT license stated in `README.md`.

---

## 3. Gaps Identified

### GAP-01 — SQL injection in `projectHasCustomColors()` and `getColorSettings()` 🔴

**Severity:** Critical — security  
**File:** `Action/AssignColorsByDayOfWeek.php`, lines 16 and 21

`$projectId` is concatenated directly into raw SQL strings:

```php
// Line 16
->query("SELECT COUNT(*) FROM actions a WHERE ... AND a.project_id = ".$projectId.";")

// Line 21
->query("SELECT p.name, p.value FROM actions a INNER JOIN action_has_params p ... AND a.project_id = ".$projectId.";")
```

`$projectId` originates from `$data['task']['project_id']` (event data), making it
untrusted input. Both methods should be eliminated in favour of `$this->getParam()` (see
GAP-04); if raw SQL must be retained, PDO prepared statements are required.

---

### GAP-02 — Weekend due dates silently set `color_id` to `null` 🔴

**Severity:** Critical — silent data corruption  
**File:** `Action/AssignColorsByDayOfWeek.php`, lines 27–31 and 65–75

`hasRequiredCondition()` returns `true` for any task with a non-zero due date,
including weekends. `doAction()` then calls `getColorForDay()`, which calls
`DateTime::format('l')` and gets `'Saturday'` or `'Sunday'`. The lookup:

```php
$colors[(array_keys(array_column($colors, 'name'), $dayOfWeek, false))[0]]['value']
```

…produces:
1. `array_keys(…, 'Saturday')` → `[]` (no configured Saturday param)
2. `[][0]` → PHP Notice: *Undefined offset: 0*, returns `null`
3. `$colors[null]` → PHP Notice: *Undefined index*, returns `null`
4. `taskModificationModel->update(['color_id' => null])` — writes `null` to the DB

The task's color column is set to `null`, corrupting the record silently.

---

### GAP-03 — Internationalization failure: translated param keys vs. English day lookup 🔴

**Severity:** Critical — complete failure in non-English installations  
**File:** `Action/AssignColorsByDayOfWeek.php`, lines 44–50 and 30

`getActionRequiredParameters()` uses `t('Monday')` etc. as **array keys**:

```php
return array(
    t('Monday') => $colors,   // stored as e.g. 'Lundi' in French
    t('Tuesday') => $colors,
    …
);
```

Kanboard's `ActionManager` stores these keys as `action_has_params.name`. In a
French installation the DB contains rows named `'Lundi'`, `'Mardi'`, etc.

`getColorForDay()` then uses:

```php
$dayOfWeek = $dt->format('l');   // Always returns English: 'Monday', 'Tuesday', …
```

…and searches for `'Monday'` in a result set where only `'Lundi'` exists → no match →
same null cascade as GAP-02. **In any non-English Kanboard installation, no color is
ever assigned.**

---

### GAP-04 — Bypasses Kanboard's parameter API; re-queries all action instances 🟠

**Severity:** High — incorrect behaviour and unnecessary DB queries  
**File:** `Action/AssignColorsByDayOfWeek.php`, lines 15–32 and 69–71

`ActionManager::attachEvents()` (confirmed in `Core/Action/ActionManager.php` lines
136–140) clones the registered action, calls `setProjectId()`, and calls `setParam()`
for every row in `action_has_params` **for that specific action's ID**. By the time
`execute()` is called, `$this->params` is already populated with this instance's
day→color mapping.

The correct way to read a parameter is `$this->getParam('Monday')`.

Instead, `getColorSettings()` runs:

```php
SELECT p.name, p.value FROM actions a
INNER JOIN action_has_params p ON a.id = p.action_id
WHERE a.action_name = '\Kanboard\Plugin\…\AssignColorsByDayOfWeek'
  AND a.project_id = <projectId>
```

This returns params from **all instances** of this action configured for the project.
If a user adds the action twice with different colour schemes, the result set is
doubled and the lookup is non-deterministic (depends on DB row order).

`projectHasCustomColors()` is also entirely redundant: `ActionManager` only invokes
`execute()` for actions that are already configured for the project. The check is
always `true` and adds an extra DB round-trip for nothing.

---

### GAP-05 — No day-of-week guard in `hasRequiredCondition()` 🟠

**Severity:** High — causes the crash in GAP-02

`hasRequiredCondition()` checks that `date_due` is set and non-zero, but not that the
resolved day of week has a configured parameter. The fix (after GAP-04 is resolved) is:

```php
$day = /* resolve day name from date_due */;
return $this->getParam($day) !== null;
```

This makes `doAction()` unconditional and safe.

---

### GAP-06 — Timezone hardcoded to `America/New_York` 🟡

**Severity:** Medium — incorrect day-of-week for all non-Eastern-Time users  
**File:** `Action/AssignColorsByDayOfWeek.php`, line 28

```php
$dt = new DateTime('now', new DateTimeZone('America/New_York'));
```

A task due at 01:00 UTC Monday is 20:00 Sunday ET — the plugin would assign Sunday's
colour (or produce a weekend error per GAP-02) even though the due date is Monday in
the server's timezone.

The timezone should be an additional action parameter, defaulting to the PHP/server
timezone (`date_default_timezone_get()`).

---

### GAP-07 — Saturday and Sunday are not supported 🟡

**Severity:** Medium — feature gap; currently causes GAP-02 for weekend due dates  
**File:** `Action/AssignColorsByDayOfWeek.php`, lines 44–50

`getActionRequiredParameters()` only defines Mon–Fri. Users with weekend due dates
have no recourse. Options:

- Add Saturday/Sunday parameters with an explicit "no change" / "keep default" option.
- Or document and enforce in `hasRequiredCondition()` that weekend tasks are skipped.

---

### GAP-08 — Mixed tabs and spaces (PSR-12 violation) 🟡

**Severity:** Medium — code style, readability  
**Files:** `Action/AssignColorsByDayOfWeek.php` (most method bodies), `Plugin.php`
(lines 22, 30, 34)

Method signatures and closing braces use 4-space indentation; method bodies use tabs.
Running PHP-CS-Fixer with `--rules=@PSR12` will normalize all indentation.

---

### GAP-09 — No PHPDoc blocks 🟡

**Severity:** Medium — maintainability, static analysis  
**Files:** `Action/AssignColorsByDayOfWeek.php`, `Plugin.php`

No class-level or method-level PHPDoc comments exist. All public and private methods
need `@param`, `@return`, and a one-line description.

---

### GAP-10 — `use Kanboard\Model\ColorModel` is a phantom import 🟢

**Severity:** Low — static analysis warning  
**File:** `Action/AssignColorsByDayOfWeek.php`, line 10

`ColorModel` is never directly instantiated or referenced by class name. `$this->colorModel`
is resolved through `Kanboard\Core\Base::__get()` magic via the DI container. PHPStan
(and PHP-CS-Fixer) will flag this as an unused `use` statement. Either remove the
`use` and add a `@property \Kanboard\Model\ColorModel $colorModel` annotation on the
class, or keep the import and suppress the warning with an inline comment.

---

### GAP-11 — Loose `==` comparison against `getDefaultColor()` return value 🟢

**Severity:** Low — minor type-safety concern  
**File:** `Action/AssignColorsByDayOfWeek.php`, line 70

```php
$data['task']['color_id'] == $this->colorModel->getDefaultColor()
```

`getDefaultColor()` returns a string (e.g. `'yellow'`) from `configModel->get()`.
`color_id` in event data is also a string. No practical risk today, but `===` is the
PSR-12-idiomatic choice and prevents future breakage if types diverge.

---

### GAP-12 — `date_due` semantics undocumented and potentially surprising 🟢

**Severity:** Low — UX / documentation  

The plugin name and description say "by day of week" without specifying *which* day.
The implementation uses the **due date's** day of week, not the creation date. A user
reasonably expecting "Monday tasks are blue because I create them Monday mornings"
will be confused when the colour is determined by the due date instead.

This should be clarified in `getDescription()`, `README.md`, and `docs/001-architecture.md`.

---

### GAP-13 — No unit tests 🟢

**Severity:** Low — quality infrastructure  

No `tests/` directory, no PHPUnit, no `composer.json`. The core logic
(`hasRequiredCondition()`, `getColorForDay()`, the weekend crash path, the i18n path)
is entirely untested.

---

### GAP-14 — No CI pipeline 🟢

**Severity:** Low — quality infrastructure  

No GitHub Actions or Gitea CI workflow. Syntax checking, PHPCS, PHPStan, and
PHPUnit should run automatically on push.

---

### GAP-15 — No `LICENSE` file 🟢

**Severity:** Low — legal / completeness  
`README.md` states MIT but no `LICENSE` file exists in the repository.

---

### GAP-16 — Plugin homepage points to personal site 🟢

**Severity:** Low — discoverability  
`getPluginHomepage()` returns `'https://bradleyscampbell.net'` instead of the
repository URL.

---

## 4. Prioritized Task List

### 🔴 Critical — fix before any new feature work

| ID | Gap | Task | Files |
|----|-----|------|-------|
| T-01 | GAP-04 | Replace `projectHasCustomColors()`, `getColorSettings()`, and `getColorForDay()` with `$this->getParam()` calls. Remove the two private helper methods entirely. | `Action/AssignColorsByDayOfWeek.php` |
| T-02 | GAP-03 | Decide on parameter key strategy and apply it consistently. **Recommended:** change `getActionRequiredParameters()` keys to fixed English strings (drop `t()` wrappers on keys); update day-of-week lookup to use the same fixed English strings. Document as a breaking change for any existing non-English configurations. | `Action/AssignColorsByDayOfWeek.php` |
| T-03 | GAP-02, GAP-05 | Add day-of-week guard to `hasRequiredCondition()`: resolve the day from `date_due`, check `$this->getParam($day) !== null`. This both fixes the weekend crash and makes `doAction()` unconditional. Depends on T-01 and T-02. | `Action/AssignColorsByDayOfWeek.php` |
| T-04 | GAP-01 | Confirm no raw SQL remains after T-01. If any raw SQL is retained for any reason, convert to PDO prepared statements. | `Action/AssignColorsByDayOfWeek.php` |

---

### 🟠 High — fix in the next iteration

| ID | Gap | Task | Files |
|----|-----|------|-------|
| T-05 | GAP-07 | Add Saturday and Sunday to `getActionRequiredParameters()`. Provide a sentinel "no change" option (e.g., empty string / `'none'`) so users can leave weekends uncolored without crashing. Update `hasRequiredCondition()` guard (T-03) to treat `'none'` as a skip. | `Action/AssignColorsByDayOfWeek.php` |
| T-06 | GAP-06 | Add `Timezone` as a configurable action parameter. Default to `date_default_timezone_get()`. Replace the hardcoded `America/New_York` string in `getColorForDay()` (or its T-01 replacement). | `Action/AssignColorsByDayOfWeek.php` |

---

### 🟡 Medium — clean up in a dedicated quality pass

| ID | Gap | Task | Files |
|----|-----|------|-------|
| T-07 | GAP-08 | Run PHP-CS-Fixer (`--rules=@PSR12`) to normalize all indentation to 4 spaces. | `Action/AssignColorsByDayOfWeek.php`, `Plugin.php` |
| T-08 | GAP-09 | Add PHPDoc blocks to all classes and methods: `@param` types, `@return` type, one-line description. | `Action/AssignColorsByDayOfWeek.php`, `Plugin.php` |
| T-09 | GAP-10 | Remove `use Kanboard\Model\ColorModel` and add `@property \Kanboard\Model\ColorModel $colorModel` to the class-level PHPDoc (added in T-08). | `Action/AssignColorsByDayOfWeek.php` |
| T-10 | GAP-11 | Change `==` to `===` in the `hasRequiredCondition()` `color_id` comparison. | `Action/AssignColorsByDayOfWeek.php` |
| T-11 | GAP-12 | Update `getDescription()` to say "based on the day of the week of the task's due date". Update `README.md` and `docs/001-architecture.md` to clarify `date_due` semantics. | `Action/AssignColorsByDayOfWeek.php`, `README.md`, `docs/001-architecture.md` |

---

### 🟢 Low — infrastructure and polish

| ID | Gap | Task | Files |
|----|-----|------|-------|
| T-12 | GAP-13 | Add `composer.json` with `phpunit/phpunit` and `phpstan/phpstan` as dev dependencies. Create `tests/Action/AssignColorsByDayOfWeekTest.php` covering: `hasRequiredCondition()` with default color + valid weekday due date (true), with weekend due date (false after T-03), with non-default color (false), with zero due date (false); `doAction()` with a mocked task model asserting correct color ID is passed. | `composer.json`, `tests/` |
| T-13 | GAP-14 | Add a Gitea CI workflow (or GitHub Actions equivalent) running: PHP syntax check, PHPCS, PHPStan, PHPUnit. Reference `scripts/docker-*.sh`. | `.gitea/workflows/ci.yml` or `.github/workflows/ci.yml` |
| T-14 | GAP-15 | Add `LICENSE` file with standard MIT text (author: Brad Campbell, year: 2026). | `LICENSE` |
| T-15 | GAP-16 | Update `getPluginHomepage()` to return the repository URL. | `Plugin.php` |

---

## Task Dependency Graph

```
T-01 (getParam API)
 └── T-02 (key strategy)
      └── T-03 (weekend guard)  ← T-04 (no raw SQL)
           └── T-05 (Sat/Sun support)
           └── T-06 (timezone param)

T-07 (PSR-12 indent)
T-08 (PHPDoc)  ← T-09 (fix unused import)

T-12 (tests) — best done after T-01..T-06 so tests cover fixed behaviour
T-13 (CI)    — after T-12
T-14 (LICENSE)
T-15 (homepage) — trivial, any time
T-10, T-11   — independent, any time after T-01
```

---

## Acceptance Criteria for "gap closed"

A gap is considered closed when:

- The associated code change is committed with a `fix()` or `feat()` conventional commit
- `CHANGELOG.md` is updated under `[Unreleased]`
- If the gap affected `hasRequiredCondition()` or `doAction()`: at least one unit test
  covers the corrected path
- `docs/001-architecture.md` is updated if the data flow description changes
- This document's task row is checked off or the status of the relevant task doc in
  `docs/tasks/` is updated to `deprecated`
