#!/usr/bin/env bash
#
# Creates a fresh Drupal 10 ddev instance for skill evaluation.
#
# Usage: ./eval/setup-fresh-drupal10.sh <unique-name>
# Example: ./eval/setup-fresh-drupal10.sh caching-with
#
# Creates a ddev project named "d10-<unique-name>" in /tmp/d10-<unique-name>.
# Installs Drupal 10 via composer, sets up drush, runs site:install.
# Outputs the ddev project directory path on success.
#
# The caller is responsible for teardown via teardown-drupal-env.sh.
#
# Features:
#   - Auto-retry on ddev-router health check failures (up to 3 attempts)
#   - Serialized ddev starts via flock to prevent router conflicts
#   - Clean teardown of stale environments before setup
#   - Verification of Drupal bootstrap after install
#
set -euo pipefail

# Fix for nested Claude sessions: unset CLAUDECODE to allow scripts
# to run from within a Claude Code agent session.
# See: Phase 6 lesson -- CLAUDECODE env var blocks nested sessions.
unset CLAUDECODE 2>/dev/null || true

NAME="${1:?Usage: setup-fresh-drupal10.sh <unique-name>}"
TARGET_DIR="/tmp/d10-${NAME}"
PROJECT_NAME="d10-${NAME}"
MAX_RETRIES=3

# --- Cleanup stale environment if it exists ---
if [ -d "$TARGET_DIR" ]; then
  echo "Cleaning up stale environment: $TARGET_DIR" >&2
  # Try ddev delete first (removes containers, volumes, etc.)
  (cd "$TARGET_DIR" && ddev delete -O -y 2>/dev/null) || true
  # Use docker to remove root-owned files, fall back to plain rm
  docker run --rm -v /tmp:/tmp alpine rm -rf "/tmp/d10-${NAME}" 2>/dev/null || rm -rf "$TARGET_DIR"
fi

# --- Create project directory ---
mkdir -p "$TARGET_DIR"
cd "$TARGET_DIR"

# --- Configure ddev for Drupal 10 ---
ddev config --project-type=drupal10 --docroot=web --project-name="${PROJECT_NAME}"

# --- Start ddev with retry loop for router failures ---
# ~50% of first ddev starts fail due to ddev-router health check issues.
# Strategy: flock serializes concurrent starts; on failure, restart the
# ddev-router container and wait for it to stabilize.
for attempt in $(seq 1 $MAX_RETRIES); do
  if (flock -x 200; ddev start) 200>/tmp/ddev-start.lock; then
    echo "ddev started successfully (attempt $attempt)" >&2
    break
  fi
  echo "ddev start failed (attempt $attempt/$MAX_RETRIES), restarting router..." >&2
  docker restart ddev-router 2>/dev/null || true
  sleep 20
  if [ "$attempt" -eq "$MAX_RETRIES" ]; then
    echo "FATAL: ddev start failed after $MAX_RETRIES attempts" >&2
    exit 1
  fi
done

# --- Install Drupal 10 via Composer ---
ddev composer create-project "drupal/recommended-project:^10" .

# --- Verify ddev config wasn't overwritten by composer ---
# Pitfall 4: composer create-project can overwrite .ddev/ in edge cases.
if ! grep -q "${PROJECT_NAME}" .ddev/config.yaml 2>/dev/null; then
  echo "WARNING: ddev config was overwritten by composer, re-configuring..." >&2
  ddev config --project-type=drupal10 --docroot=web --project-name="${PROJECT_NAME}"
  ddev restart
fi

# --- Install drush ---
ddev composer require drush/drush

# --- Run Drupal site install ---
ddev drush site:install standard --account-name=admin --account-pass=admin -y

# --- Verify Drupal bootstrap ---
if ! ddev drush status --field=bootstrap | grep -q "Successful"; then
  echo "FATAL: Drupal bootstrap verification failed" >&2
  exit 1
fi

echo "Drupal 10 environment ready: $TARGET_DIR" >&2
echo "$TARGET_DIR"
