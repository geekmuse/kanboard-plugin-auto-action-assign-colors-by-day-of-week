#!/usr/bin/env bash
# Run PHPStan static analysis.
# NOTE: entrypoint MUST be overridden — default starts Apache and runs forever.
# Requires composer.json + vendor/ to be present (see docs/tasks/001-gap-analysis.md T-12).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

if [ ! -f "$PLUGIN_DIR/vendor/bin/phpstan" ]; then
  echo "ERROR: vendor/bin/phpstan not found. Run 'composer install' first."
  exit 1
fi

docker run --rm \
  --entrypoint /bin/sh \
  -v "$PLUGIN_DIR":/plugin \
  kanboard/kanboard \
  -c "cd /plugin && vendor/bin/phpstan analyse --level=5 Plugin.php Action/"
