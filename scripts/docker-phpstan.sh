#!/usr/bin/env bash
# Run PHPStan static analysis.
# NOTE: entrypoint MUST be overridden — default starts Apache and runs forever.
# Requires composer.json + vendor/ to be present (see docs/tasks/001-gap-analysis.md T-12).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

if [ ! -f "$PLUGIN_DIR/vendor/bin/phpstan" ]; then
  echo "SKIP: vendor/bin/phpstan not found — run 'composer install' first (see US-010)."
  exit 0
fi

# Use php:8.4-cli instead of kanboard/kanboard — the kanboard image is missing
# the 'tokenizer' extension that PHPStan requires (token_get_all()).
# The test bootstrap provides Kanboard class stubs when the kanboard Docker
# image's autoloader is absent (phpstan.neon: bootstrapFiles = tests/bootstrap.php).
docker run --rm \
  --entrypoint /bin/sh \
  -v "$PLUGIN_DIR":/plugin \
  php:8.4-cli \
  -c "cd /plugin && vendor/bin/phpstan analyse --level=5 Plugin.php Action/"
