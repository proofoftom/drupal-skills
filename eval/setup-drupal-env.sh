#!/usr/bin/env bash
#
# Sets up an isolated Drupal environment for skill eval using os-knowledge-garden.
#
# Usage: ./eval/setup-drupal-env.sh <unique-name>
# Example: ./eval/setup-drupal-env.sh scaffold-with
#
# Outputs the ddev project directory path on success.
# Docroot is html/ (os-knowledge-garden layout).
# The caller is responsible for teardown via teardown-drupal-env.sh
#
set -euo pipefail

# Fix for nested Claude sessions: unset CLAUDECODE to allow headless
# claude -p to run from within a Claude Code agent session.
# See: Phase 6 lesson -- env -u CLAUDECODE is required for nested sessions.
unset CLAUDECODE 2>/dev/null || true

NAME="${1:?Usage: setup-drupal-env.sh <unique-name>}"
SOURCE_DIR="$(cd "$(dirname "$0")/../os-knowledge-garden" && pwd)"
TARGET_DIR="/tmp/os-kg-${NAME}"

if [ -d "$TARGET_DIR" ]; then
  echo "Cleaning up stale environment: $TARGET_DIR" >&2
  # Delete ddev project if it exists (auto-confirm)
  (yes | ddev delete -O "os-kg-${NAME}" 2>/dev/null) || true
  # Use docker to remove root-owned files (e.g., Qdrant storage from Docker volumes)
  docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/os-kg-${NAME}" 2>/dev/null || rm -rf "$TARGET_DIR"
fi

cp -a "$SOURCE_DIR" "$TARGET_DIR"
cd "$TARGET_DIR"

# Give ddev a unique project name.
# NOTE: os-knowledge-garden/.ddev/config.yaml has NO name: field.
# We INSERT it at line 1 (after line 1 means as line 2, but sed 1a inserts after line 1).
# Using sed substitute would silently fail since there is no existing name: line to match.
sed -i "1a name: os-kg-${NAME}" .ddev/config.yaml

# Start ddev — serialized via flock to prevent ddev-router conflicts
# when multiple parallel setups run simultaneously.
(flock -x 200; ddev start) 200>/tmp/ddev-start.lock

# Install Drupal with cascadia demo (lighter than full --demo=all)
bash scripts/install.sh --demo=cascadia

# Output the working directory
echo "$TARGET_DIR"
