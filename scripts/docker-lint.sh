#!/usr/bin/env bash
# Syntax-check all PHP files using the kanboard/kanboard image.
# NOTE: entrypoint MUST be overridden — default starts Apache and runs forever.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

docker run --rm \
  --entrypoint /bin/sh \
  -v "$PLUGIN_DIR":/plugin:ro \
  kanboard/kanboard \
  -c "find /plugin -name '*.php' -not -path '/plugin/vendor/*' | xargs php -l"
