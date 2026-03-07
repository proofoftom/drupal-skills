# Drupal Skills

## What This Is

A collection of 13 Claude skills extracted from the "Drupal 10 Module Development" book (Daniel Sipos, 4th ed, 2023). Each skill encapsulates a domain of Drupal module development knowledge — from scaffolding and routing to entities, theming, and testing — enabling Claude to produce correct, idiomatic Drupal code when developers ask for help.

## Core Value

Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.

## Current Milestone: v2.0 Eval & Optimization Loop

**Goal:** Build a robust, autonomous eval pipeline following skill-creator methodology -- proving all 13 Drupal skills produce measurably better code than baseline Sonnet, with clean empirical data.

**Target features:**
- Custom `eval-executor` subagent with `model: sonnet` for controlled A/B runs
- Custom `eval-browser` subagent with agent-browser for automated E2E/UAT (drush uli + UI verification + claims verification for theming/frontend evals)
- Fresh Drupal 10 ddev instances per eval run (no os-kg, faster/controlled)
- Content quality evals for all 13 skills (with-skill vs without-skill)
- Skill-creator methodology: spawn runs, draft expectations while waiting, grade with skill-creator grader agent
- Autonomous batch loop -- orchestrator runs full eval cycles with minimal manual intervention
- Eval viewer integration (skill-creator's `generate_review.py`)
- Final analysis with tier classifications and delta data

**Deferred:** Description/trigger optimization (separate step after content evals prove value)

## Requirements

### Validated

- [x] 13 skills covering all 18 book chapters, grouped by developer workflow
- [x] Each skill follows skill-creator anatomy (frontmatter, <500 line body, reference files)
- [x] Skills produce correct D10 code with D11 differences noted
- [x] Skills cross-reference each other where relevant
- [x] Skills published to GitHub (packaged in repo `skills/` folder)

### Active

(Defined in REQUIREMENTS.md for v2.0)

### Out of Scope

- Building a Drupal site or module directly — skills teach Claude how to build them
- D11-only patterns without D10 baseline — book is D10, we note D11 differences
- Real-time book updates — snapshot of 4th edition content
- Rewriting SKILL.md content — skills are locked unless eval findings demand changes
- Description/trigger optimization — deferred to after content evals prove value

## Context

**Book source:** `Sipos D. Drupal 10 Module Development. Develop...enterprise-level apps 4ed 2023.md` (11,787 lines, 18 chapters)

**Test project:** `os-knowledge-garden/` — an Open Social + AI Drupal project with custom modules (`social_ai_indexing`, `localnodes_platform`, demo modules) exercising routes, services, blocks, event subscribers, templates, and Search API processors.

**13 Skills from 18 Chapters:**

| # | Skill Name | Source Chapters | Wave |
|---|-----------|----------------|------|
| 1 | `drupal-module-scaffold` | Ch 1-2 (intro, module creation) | 1 |
| 2 | `drupal-routing-controllers` | Ch 2 (routes, controllers, services, DI) + Ch 5 (menus) | 1 |
| 3 | `drupal-forms-api` | Ch 2 (forms, altering, submit handlers) | 1 |
| 4 | `drupal-plugins-blocks` | Ch 2 (blocks) + Ch 7 (custom plugin types) | 2 |
| 5 | `drupal-entities-fields` | Ch 6-7, Ch 9, Ch 16 (entities, fields, files) | 1 |
| 6 | `drupal-config-storage` | Ch 6 (State, TempStore, Config) + Ch 13 (i18n) | 2 |
| 7 | `drupal-access-security` | Ch 10 + Ch 18 | 2 |
| 8 | `drupal-theming` | Ch 4 + Ch 12 (JS/Ajax) | 3 |
| 9 | `drupal-caching` | Ch 11 | 3 |
| 10 | `drupal-testing` | Ch 17 | 3 |
| 11 | `drupal-database-api` | Ch 8 | 3 |
| 12 | `drupal-views-dev` | Ch 15 | 4 |
| 13 | `drupal-batch-queue-cron` | Ch 14 + Ch 3 (logging/mail) | 4 |

**Output locations:**
- `skills/` — packaged skills for GitHub publishing
- `~/.claude/skills/` — installed skills for local Claude use

## Constraints

- **Skill size**: Each SKILL.md must be <500 lines per skill-creator anatomy rules
- **D10 baseline**: Code patterns must match book's D10 examples; D11 changes noted separately
- **Eval framework**: Must use existing skill-creator eval loop (no custom pipeline)
- **Book accuracy**: Skills must faithfully represent book content, not hallucinate Drupal APIs

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Wave-based parallel creation | Foundational skills first so later skills can cross-reference | -- Pending |
| D10 primary, D11 notes | Book is D10; D11 differences are additive not rewrite | -- Pending |
| Dual output (repo + ~/.claude) | GitHub publishing + local use | -- Pending |
| Use skill-creator for eval | Existing eval infrastructure, no need to build custom | -- Pending |

---
*Last updated: 2026-03-06 after v2.0 milestone start*
