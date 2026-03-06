#!/usr/bin/env bash
#
# Tears down a Drupal eval environment.
#
# Usage: ./eval/teardown-drupal-env.sh <unique-name>
# Example: ./eval/teardown-drupal-env.sh scaffold-with-skill
#
# Idempotent: exits cleanly if the directory does not exist.
#
set -euo pipefail

NAME="${1:?Usage: teardown-drupal-env.sh <unique-name>}"
TARGET_DIR="/tmp/os-kg-${NAME}"

if [ ! -d "$TARGET_DIR" ]; then
  echo "Directory not found: $TARGET_DIR" >&2
  exit 0
fi

cd "$TARGET_DIR"
ddev delete -O -y 2>/dev/null || true
rm -rf "$TARGET_DIR"
echo "Cleaned up: $TARGET_DIR"
