#!/usr/bin/env bash
# Run PHPUnit test suite.
# NOTE: entrypoint MUST be overridden — default starts Apache and runs forever.
# Requires composer.json + vendor/ + tests/ to be present (see docs/tasks/001-gap-analysis.md T-12).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

if [ ! -f "$PLUGIN_DIR/vendor/bin/phpunit" ]; then
  echo "SKIP: vendor/bin/phpunit not found — run 'composer install' first (see US-010)."
  exit 0
fi

docker run --rm \
  --entrypoint /bin/sh \
  -v "$PLUGIN_DIR":/plugin \
  kanboard/kanboard \
  -c "cd /plugin && vendor/bin/phpunit --testdox tests/"
