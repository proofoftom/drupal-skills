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

# --- Clean stale Traefik configs that block ddev-router health ---
# Dead ddev projects can leave behind Traefik config/cert files in the
# ddev-global-cache Docker volume. These reference non-existent entry points,
# causing the router health check to report "configuration error(s) in project"
# and never become healthy. We clean orphans from BOTH the Docker volume
# (where the router actually reads) and the host filesystem mirror.
ACTIVE_PROJECTS=$(ddev list --json-output 2>/dev/null | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    items = data.get('raw', []) if isinstance(data, dict) else data
    for p in items:
        if isinstance(p, dict) and 'name' in p:
            print(p['name'])
except: pass
" 2>/dev/null || true)

# Clean from Docker volume (authoritative source for ddev-router)
STALE_CLEANED=0
VOLUME_CONFIGS=$(docker run --rm -v ddev-global-cache:/mnt/ddev-global-cache alpine \
  ls /mnt/ddev-global-cache/traefik/config/ 2>/dev/null || true)
for cfg_file in $VOLUME_CONFIGS; do
  cfg_name="${cfg_file%.yaml}"
  [ "$cfg_name" = "default_config" ] && continue
  if ! echo "$ACTIVE_PROJECTS" | grep -qx "$cfg_name"; then
    echo "Removing stale Traefik config from volume: $cfg_name" >&2
    docker run --rm -v ddev-global-cache:/mnt/ddev-global-cache alpine sh -c "
      rm -f /mnt/ddev-global-cache/traefik/config/${cfg_file}
      rm -f /mnt/ddev-global-cache/traefik/certs/${cfg_name}.crt
      rm -f /mnt/ddev-global-cache/traefik/certs/${cfg_name}.key
    " 2>/dev/null || true
    STALE_CLEANED=1
  fi
done

# Also clean from host filesystem mirror
TRAEFIK_CONFIG_DIR="$HOME/.ddev/traefik/config"
TRAEFIK_CERTS_DIR="$HOME/.ddev/traefik/certs"
if [ -d "$TRAEFIK_CONFIG_DIR" ]; then
  for cfg in "$TRAEFIK_CONFIG_DIR"/*.yaml; do
    [ -f "$cfg" ] || continue
    cfg_name=$(basename "$cfg" .yaml)
    [ "$cfg_name" = "default_config" ] && continue
    if ! echo "$ACTIVE_PROJECTS" | grep -qx "$cfg_name"; then
      rm -f "$cfg"
      rm -f "$TRAEFIK_CERTS_DIR/${cfg_name}.crt" "$TRAEFIK_CERTS_DIR/${cfg_name}.key" 2>/dev/null
    fi
  done
fi

# If we cleaned stale configs, force-remove the router so it restarts clean
if [ "$STALE_CLEANED" -eq 1 ]; then
  echo "Stale configs cleaned, force-removing ddev-router for clean restart..." >&2
  docker rm -f ddev-router 2>/dev/null || true
fi

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
