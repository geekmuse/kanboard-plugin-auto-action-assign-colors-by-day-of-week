#!/usr/bin/env bash
# Run PHP_CodeSniffer (PSR-12) via the cytopia/phpcs:latest Docker image.
# NOTE: use --entrypoint /usr/bin/phpcs — the default entrypoint in this image
#       resolves phpcs via a wrapper that behaves differently inside the container.
# No vendor/ dependency — cytopia/phpcs includes phpcs and its standard library.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

docker run --rm \
  --entrypoint /usr/bin/phpcs \
  -v "$PLUGIN_DIR":/plugin \
  cytopia/phpcs:latest \
  --standard=PSR12 \
  --extensions=php \
  /plugin/Plugin.php \
  /plugin/Action/
