#!/usr/bin/env bash
#
# Sets up an isolated fresh Drupal 10 environment for skill eval.
#
# Usage: ./eval/setup-drupal-env.sh <unique-name>
# Example: ./eval/setup-drupal-env.sh scaffold-with
#
# Outputs the ddev project directory path on success.
# Docroot is web/ (standard drupal/recommended-project layout).
# The caller is responsible for teardown via teardown-drupal-env.sh
#
set -euo pipefail

NAME="${1:?Usage: setup-drupal-env.sh <unique-name>}"
TARGET_DIR="/tmp/d10-${NAME}"

if [ -d "$TARGET_DIR" ]; then
  echo "Target directory already exists: $TARGET_DIR" >&2
  exit 1
fi

mkdir -p "$TARGET_DIR"
cd "$TARGET_DIR"

# Configure ddev for a fresh Drupal 10 project
ddev config \
  --project-name="d10-${NAME}" \
  --project-type=drupal10 \
  --docroot=web \
  --php-version=8.3

# Start ddev (creates containers before composer create)
ddev start

# Create fresh Drupal 10 project (downloads from packagist inside container)
ddev composer create drupal/recommended-project

# Add drush (included in recommended-project but ensure it's present)
ddev composer require drush/drush --no-update
ddev composer update drush/drush --with-dependencies

# Install Drupal with minimal profile (fast — no demo content)
ddev drush site:install minimal \
  --account-name=admin \
  --account-pass=admin \
  --site-name="Eval ${NAME}" \
  -y

# Output the working directory
echo "$TARGET_DIR"
