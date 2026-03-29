# AssignColorsByDayOfWeek

> A Kanboard plugin that automatically assigns task card colors based on the day of the
> week of the task's due date, triggered at task creation time.

## Overview

This plugin adds an automatic action to Kanboard that assigns a configurable color to
newly created tasks based on the day of the week of their due date. Colors are
configured per-project using Kanboard's built-in automatic action UI — no extra
configuration screens needed.

## Features

- Registers a new automatic action: **"Assign automatically a color based on the day of the week"**
- Configurable Mon–Fri color mapping per project (choose any Kanboard color for each weekday)
- Only fires when a task has the default color and a non-zero due date, preventing unwanted overrides
- Uses Kanboard's existing action parameter storage — no custom database tables

## Quick Start

### Prerequisites

- Kanboard ≥ 1.2.19
- PHP 7.0+ (as required by your Kanboard installation)

### Installation

**Option A — Drop into plugins directory:**

```bash
cd /path/to/kanboard/plugins
git clone https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week.git AssignColorsByDayOfWeek
```

> ⚠️ The directory **must** be named `AssignColorsByDayOfWeek` to match the PHP namespace.

**Option B — Download ZIP:**

Download a release archive and extract it as `plugins/AssignColorsByDayOfWeek/` inside
your Kanboard installation.

### Usage

1. Navigate to a project → **Actions** → **Automatic Actions**
2. Add a new action: select **"Assign automatically a color based on the day of the week"**
3. Choose a color for each weekday (Monday through Friday)
4. Save the action

When tasks are created with a due date falling on a configured weekday, the plugin
will automatically set the task color to the configured value for that day.

## Documentation

Detailed documentation lives in the [`docs/`](docs/) directory:

| Section | Path | Description |
|---------|------|-------------|
| Architecture | [`docs/001-architecture.md`](docs/001-architecture.md) | System design and key decisions |
| Development Guide | [`docs/002-development-guide.md`](docs/002-development-guide.md) | How to develop, test, and contribute |
| Doc Standards | [`docs/003-documentation-standards.md`](docs/003-documentation-standards.md) | How docs are structured and maintained |
| Specs | [`docs/specs/`](docs/specs/) | Feature specifications and design docs |
| ADRs | [`docs/adrs/`](docs/adrs/) | Architecture Decision Records |
| References | [`docs/references/`](docs/references/) | API docs, glossary, config reference |
| Tasks | [`docs/tasks/`](docs/tasks/) | Work items and sprint plans |
| Research | [`docs/research/`](docs/research/) | Spikes, investigations, POC write-ups |

## Development

```bash
# Clone
git clone https://github.com/geekmuse/kanboard-plugin-auto-action-assign-colors-by-day-of-week.git
cd plugin-auto-action-assign-colors-by-day-of-week

# Install git hooks
prek install

# Syntax check
find . -name "*.php" -not -path "./vendor/*" | xargs php -l

# Install dev tooling (once composer.json is added)
composer install --dev

# Run tests
./vendor/bin/phpunit

# Lint
./vendor/bin/phpcs --standard=PSR12 .
```

See [Development Guide](docs/002-development-guide.md) for full details.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/amazing-feature`)
3. Commit using [conventional commits](https://www.conventionalcommits.org/) (`git commit -m 'feat(action): add amazing feature'`)
4. Push to the branch (`git push origin feat/amazing-feature`)
5. Open a Pull Request

Please read [AGENTS.md](AGENTS.md) for project conventions and [docs/002-development-guide.md](docs/002-development-guide.md) for the full development workflow.

## Versioning

This project uses [Semantic Versioning](https://semver.org/). The version is maintained
in `Plugin.php` → `getPluginVersion()`. See [CHANGELOG.md](CHANGELOG.md) for release
history.

## License

MIT — see [LICENSE](LICENSE) for details.

## Author

Brad Campbell
