---
date_created: 2026-03-29
date_modified: 2026-03-29
status: active
audience: both
cross_references:
  - docs/002-development-guide.md
  - AGENTS.md
---

# Architecture — AssignColorsByDayOfWeek

## Overview

`AssignColorsByDayOfWeek` is a Kanboard plugin that automatically assigns task card
colors based on the **day of the week of a task's due date**, triggered at task
creation time. The color is determined by which day of the week the due date falls on
— not the creation date. Colors are configured per-project via Kanboard's standard
automatic action UI.

## System Context

This plugin operates entirely within the Kanboard plugin ecosystem. It registers a
single automatic action that Kanboard's `ActionManager` invokes when tasks are created.

```
┌──────────────┐   EVENT_CREATE   ┌──────────────────────────┐
│   Kanboard   │ ───────────────▶ │  AssignColorsByDayOfWeek │
│   Core       │                  │  Action                  │
│              │ ◀─────────────── │  (updates task color)    │
└──────────────┘   task update    └──────────────────────────┘
        │
        │ reads color config
        ▼
┌──────────────┐
│  actions /   │
│  action_has_ │
│  params DB   │
└──────────────┘
```

## High-Level Components

| Component | Responsibility | Key Files |
|-----------|---------------|-----------|
| Plugin entry point | Registers the action with Kanboard's ActionManager | `Plugin.php` |
| AssignColorsByDayOfWeek action | Event handling, condition checking, color resolution, task update | `Action/AssignColorsByDayOfWeek.php` |

## Data Flow

1. A task is created in Kanboard (`TaskModel::EVENT_CREATE` fires)
2. Kanboard's `ActionManager` pre-populates `$this->params` with this action instance's
   configured day→color and Timezone values from `action_has_params`, then calls
   `hasRequiredCondition()` on the action
3. The action verifies: task has the default color; task has a non-zero due date; and the
   color configured for that due date's day of week (resolved via `resolveDayOfWeek()` +
   `getParam($day)`) is a non-empty, non-sentinel value
4. If the condition passes, `doAction()` is called unconditionally
5. `getColorForDay()` reads the color for the resolved day via `$this->getParam($day)` —
   no additional DB query is needed because `ActionManager` has already populated the params
6. The due date timestamp is converted to an English day name (e.g., `'Monday'`) using
   the configured Timezone parameter (falls back to `date_default_timezone_get()`)
7. The resolved color ID is applied to the task via `TaskModificationModel::update()`

## Key Design Decisions

| Decision | Rationale | ADR |
|----------|-----------|-----|
| PHP as primary language | Required by the Kanboard plugin API | — |
| Extend `Kanboard\Action\Base` | Provides DI container access and standard action lifecycle hooks | — |
| Use `$this->getParam()` API (not raw SQL) | `ActionManager` pre-populates `$this->params` before `execute()` is called; `getParam()` reads the current action instance's parameters without extra DB queries | — |
| Fixed English parameter keys (no `t()` on keys) | `DateTime::format('l')` always returns English day names; using translated keys causes an unresolvable lookup mismatch in non-English installations | — |
| Configurable Timezone parameter | Replaces hardcoded `America/New_York`; defaults to `date_default_timezone_get()` so a task due at midnight is classified by the server/user's local day, not ET | — |
| Color determined by **due date's** day of week | Users configure "I want Monday-due tasks to be red"; the action fires on creation so the relevant date is the due date, not the creation timestamp | — |

> For detailed decision records, see [`docs/adrs/`](adrs/).

## Dependencies

### Runtime

| Dependency | Purpose | Version |
|------------|---------|---------|
| Kanboard core | Plugin API, ActionManager, TaskModificationModel, ColorModel | `>=1.2.19` |
| PHP | Runtime language | `>=7.0` (implied by Kanboard 1.2.19) |
| PDO | Transitively available but not used directly — all parameter access is via `$this->getParam()` | Built-in |

### Development

| Tool | Purpose |
|------|---------|
| PHPUnit | Unit testing |
| PHP_CodeSniffer / PHP-CS-Fixer | PSR-12 code style enforcement |
| PHPStan | Static analysis |
| Kanboard dev instance | Manual integration testing |

## Constraints & Non-Goals

### Constraints
- Must conform to the Kanboard plugin API and lifecycle
- Plugin version and compatibility metadata live in `Plugin.php` — no external package manager
- Color configuration is stored in Kanboard's existing `action_has_params` table — no custom schema

### Non-Goals (Explicit)
- Assigning colors based on criteria other than day of week
- Supporting events other than `EVENT_CREATE` (e.g., task moves, updates)
- Providing a standalone UI beyond Kanboard's built-in automatic action configurator

## Future Considerations

- Support `EVENT_MOVE_COLUMN` or `EVENT_UPDATE` in addition to `EVENT_CREATE`
- Allow a second trigger condition based on the task **creation** date's day of week
  (alternative semantics that some users expect based on the plugin name)
- Add a UI hint in the action description surfacing the configured timezone so users
  see which timezone is active when editing an action instance
