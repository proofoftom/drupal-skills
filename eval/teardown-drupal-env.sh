#!/usr/bin/env bash
#
# Tears down a Drupal eval environment.
#
# Supports both fresh Drupal 10 (d10-) and os-knowledge-garden (os-kg-) environments.
# Auto-detects which prefix to use based on which directory exists.
#
# Usage: ./eval/teardown-drupal-env.sh <unique-name>
# Example: ./eval/teardown-drupal-env.sh caching-with
#
# The script checks for environments in this order:
#   1. /tmp/d10-<name>  (fresh Drupal 10 -- new default)
#   2. /tmp/os-kg-<name> (os-knowledge-garden -- legacy)
#
# If both exist, both are torn down.
# Idempotent: exits cleanly (exit 0) if neither directory exists.
#
set -euo pipefail

NAME="${1:?Usage: teardown-drupal-env.sh <unique-name>}"

FOUND=0

# --- Teardown d10- environment (new default) ---
D10_DIR="/tmp/d10-${NAME}"
if [ -d "$D10_DIR" ]; then
  FOUND=1
  echo "Tearing down d10 environment: $D10_DIR" >&2
  cd "$D10_DIR"
  ddev delete -O -y 2>/dev/null || true
  cd /
  # Use docker to remove root-owned files, fall back to plain rm
  docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/d10-${NAME}" 2>/dev/null || rm -rf "$D10_DIR"
  echo "Cleaned up: $D10_DIR"
fi

# --- Teardown os-kg- environment (legacy) ---
OSKG_DIR="/tmp/os-kg-${NAME}"
if [ -d "$OSKG_DIR" ]; then
  FOUND=1
  echo "Tearing down os-kg environment: $OSKG_DIR" >&2
  cd "$OSKG_DIR"
  ddev delete -O -y 2>/dev/null || true
  cd /
  # Use docker to remove root-owned files (e.g., Qdrant storage), fall back to plain rm
  docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/os-kg-${NAME}" 2>/dev/null || rm -rf "$OSKG_DIR"
  echo "Cleaned up: $OSKG_DIR"
fi

# --- No environment found ---
if [ "$FOUND" -eq 0 ]; then
  echo "No environment found for '${NAME}' (checked d10- and os-kg- prefixes)" >&2
  exit 0
fi
