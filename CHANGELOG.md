# Changelog

All notable changes to plugins in this marketplace are documented here. Each plugin versions independently; entries are grouped by plugin.

The format roughly follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [SemVer](https://semver.org/).

---

## drupal-skills

### 3.1.0 — 2026-05-09

**First public release.** Prior 3.x iteration was internal eval-driven refinement; this release packages the skill set as a Claude Code plugin marketplace.

Added:
- Marketplace structure: install via `/plugin marketplace add proofoftom/drupal-skills` then `/plugin install drupal-skills@drupal-skills`.
- 15 skills covering Drupal 10/11 module development:
  - **Foundations**: `drupal-module-scaffold`, `drupal-routing-controllers`, `drupal-entities-fields`
  - **Core workflow**: `drupal-forms-api`, `drupal-plugins-blocks`, `drupal-config-storage`, `drupal-access-security`
  - **Presentation & quality**: `drupal-theming`, `drupal-caching`, `drupal-testing`, `drupal-database-api`
  - **Specialized**: `drupal-views-dev`, `drupal-batch-queue-cron`, `drupal-drush`
  - **Cross-cutting**: `drupal-coding-standards`
- `CONTRIBUTING.md` covering dev loop, eval-before-publish, attribution policy, and versioning workflow.

Changed:
- Skill count corrected to 15 (`drupal-drush` was undercounted in earlier internal docs).

---

## drupal-tdd

### 0.1.0 — 2026-05-09

**Initial public release.** Pre-1.0 versioning indicates the skill set will continue to evolve based on usage; the trigger phrasing and worked-example walkthrough are stable.

Added:
- `drupal-tdd` skill: red/green/refactor cadence, outside-in test ordering, test-first feature growth. Pairs with `drupal-skills`' `drupal-testing` (which covers base classes and assertion APIs).
- Worked example walking from empty project to a blog module driven test-first across Functional, Kernel, and Unit tiers.
- References for PHPUnit setup, test data builders, custom assertions, and test-only modules/config.
