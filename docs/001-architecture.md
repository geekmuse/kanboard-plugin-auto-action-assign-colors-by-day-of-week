---
date_created: 2026-03-29
date_modified: 2026-03-29
status: draft
audience: both
cross_references:
  - docs/002-development-guide.md
  - AGENTS.md
---

# Architecture — AssignColorsByDayOfWeek

## Overview

`AssignColorsByDayOfWeek` is a Kanboard plugin that automatically assigns task card
colors based on the day of the week of a task's due date, triggered at task creation
time. Colors are configured per-project via Kanboard's standard automatic action UI.

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
2. Kanboard's ActionManager calls `hasRequiredCondition()` on all registered actions
3. The action verifies: task has the default color, task has a non-zero due date, and
   the project has at least one `AssignColorsByDayOfWeek` action configured
4. If conditions are met, `doAction()` is called
5. The action queries `action_has_params` to retrieve the day→color mapping for the project
6. The due date timestamp is converted to a day of week (e.g., "Monday") in the
   `America/New_York` timezone
7. The resolved color ID is applied to the task via `TaskModificationModel::update()`

## Key Design Decisions

| Decision | Rationale | ADR |
|----------|-----------|-----|
| PHP as primary language | Required by the Kanboard plugin API | — |
| Extend `Kanboard\Action\Base` | Provides DI container access and standard action lifecycle hooks | — |
| Read config from `action_has_params` at runtime | Reuses Kanboard's built-in action parameter storage; no separate config table needed | — |
| Hardcoded `America/New_York` timezone | Initial implementation; should be made configurable | `docs/adrs/` |

> For detailed decision records, see [`docs/adrs/`](adrs/).

## Dependencies

### Runtime

| Dependency | Purpose | Version |
|------------|---------|---------|
| Kanboard core | Plugin API, ActionManager, TaskModificationModel, ColorModel | `>=1.2.19` |
| PHP | Runtime language | `>=7.0` (implied by Kanboard 1.2.19) |
| PDO | Raw SQL queries against `actions` and `action_has_params` tables | Built-in |

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

- Make timezone configurable (currently hardcoded to `America/New_York`)
- Add Saturday/Sunday support (currently only Mon–Fri are configurable)
- Use prepared statements instead of raw string-interpolated SQL queries
- Support `EVENT_MOVE_COLUMN` or `EVENT_UPDATE` in addition to `EVENT_CREATE`
