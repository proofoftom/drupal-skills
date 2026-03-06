#!/usr/bin/env bash
#
# Sets up an isolated Drupal environment for skill eval.
#
# Usage: ./eval/setup-drupal-env.sh <unique-name>
# Example: ./eval/setup-drupal-env.sh scaffold-with-skill
#
# Outputs the ddev project directory path on success.
# The caller is responsible for teardown via teardown-drupal-env.sh
#
set -euo pipefail

NAME="${1:?Usage: setup-drupal-env.sh <unique-name>}"
SOURCE_DIR="$(cd "$(dirname "$0")/../os-knowledge-garden" && pwd)"
TARGET_DIR="/tmp/os-kg-${NAME}"

# Clone (local, fast)
if [ -d "$TARGET_DIR" ]; then
  echo "Target directory already exists: $TARGET_DIR" >&2
  exit 1
fi
cp -a "$SOURCE_DIR" "$TARGET_DIR"

# Give ddev a unique project name.
# NOTE: os-knowledge-garden/.ddev/config.yaml has NO name: field.
# We INSERT it at line 1 (after line 1 means as line 2, but sed 1a inserts after line 1).
# Using sed substitute would silently fail since there is no existing name: line to match.
cd "$TARGET_DIR"
sed -i "1a name: os-kg-${NAME}" .ddev/config.yaml

# Start ddev and install
ddev start
bash scripts/install.sh --demo=cascadia

# Output the working directory
echo "$TARGET_DIR"
