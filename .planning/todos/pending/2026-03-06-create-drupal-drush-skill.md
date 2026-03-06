---
created: 2026-03-06T07:19:24.065Z
title: Create drupal-drush skill
area: general
files:
  - skills/drupal-testing/evals/evals.json
  - skills/drupal-entities-fields/evals/evals.json
  - os-knowledge-garden/html/modules/custom/social_ai_indexing/social_ai_indexing.services.yml
---

## Problem

During phase 6 eval runs, multiple Drush command knowledge gaps were discovered:

1. **`entity:updates` does not exist** — was assumed in entities eval assertion 8. Neither `entity:updates` nor `entity:update` is a valid Drush command.
2. **Actual entity commands** are: `entity:create`, `entity:delete`, `entity:save` — very different from what was assumed.
3. **No skill covers Drush CLI** — the 13 existing skills focus on Drupal API/code patterns but none covers Drush commands, site management, config import/export, cache operations, or entity CLI operations.

This gap means both humans and AI agents guess at Drush commands, leading to broken eval assertions and wasted debugging time.

Reference: https://www.drush.org/13.x/commands/all/ for the complete command list.

## Solution

Create a new `skills/drupal-drush/SKILL.md` covering:
- Entity operations (`entity:create`, `entity:delete`, `entity:save`)
- Config management (`config:export`, `config:import`, `config:get`, `config:set`)
- Cache operations (`cache:rebuild`, `cache:clear`)
- Site management (`site:install`, `site:status`)
- Module management (`pm:install`, `pm:uninstall`, `pm:list`)
- Database operations (`sql:dump`, `sql:cli`, `sql:query`)
- Common wrong-way patterns (e.g., `entity:update` doesn't exist, `pm:enable` is now `pm:install`)

This would be a new phase in the milestone or a standalone skill creation task.
