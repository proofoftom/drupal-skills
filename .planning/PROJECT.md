# Drupal Skills

## What This Is

A collection of 13 Claude skills extracted from the "Drupal 10 Module Development" book (Daniel Sipos, 4th ed, 2023), plus a coding-standards skill. Each skill encapsulates a domain of Drupal module development knowledge -- from scaffolding and routing to entities, theming, and testing -- enabling Claude to produce correct, idiomatic Drupal code when developers ask for help. Empirically validated through a headless eval pipeline showing 9/13 skills with measurable positive delta.

## Core Value

Claude can generate correct, production-ready Drupal module code across all major development domains when guided by these skills.

## Requirements

### Validated

- ✓ 13 skills covering all 18 book chapters, grouped by developer workflow -- v1.0
- ✓ Each skill follows skill-creator anatomy (frontmatter, <500 line body, reference files) -- v1.0
- ✓ Skills produce correct D10 code with D11 differences noted -- v1.0
- ✓ Skills cross-reference each other where relevant -- v1.0
- ✓ Skills published to GitHub (packaged in repo `skills/` folder) -- v1.0
- ✓ Eval infrastructure: setup/teardown scripts, evals.json, E2E helpers -- v1.0
- ✓ Live eval proved measurable skill impact (caching +75%, scaffold +43%) -- v1.0
- ✓ All 13 evals rewritten with differentiating assertions from source material -- v1.0
- ✓ Headless eval pipeline with controlled A/B execution -- v2.0
- ✓ All 13 eval prompts rewritten for fresh Drupal 10 instances -- v2.0
- ✓ All 13 skills have graded benchmarks with tier classifications -- v2.0
- ✓ Skills with weak deltas iterated on (coding-standards skill, SKILL.md patches) -- v2.0
- ✓ Final report with stabilized results and overall verdict -- v2.0

### Active

(Defined in REQUIREMENTS.md for v5.0)

### Out of Scope

- Building a Drupal site or module directly -- skills teach Claude how to build them
- D11-only patterns without D10 baseline -- book is D10, we note D11 differences
- Real-time book updates -- snapshot of 4th edition content
- Migration API skill -- not in source book
- Contrib module patterns -- stale quickly, not in book's scope
- Description/trigger optimization -- active in v3.0 (run if skills don't auto-trigger)
- Multi-run variance analysis (3+ runs per config) -- single-run sufficient for tier classification

## Context

**Book source:** `Sipos D. Drupal 10 Module Development. Develop...enterprise-level apps 4ed 2023.md` (11,787 lines, 18 chapters)

**Shipped v2.0:** 13 domain skills + 1 coding-standards skill, ~7,457 lines of content, headless eval pipeline, empirical benchmarks for all 13 skills.

**Final portfolio (v2.0):**
- 4 HIGH delta (+31.6% avg): caching, routing-controllers, scaffold, testing
- 5 MODERATE delta (+11.7% avg): config-storage, batch-queue-cron, plugins-blocks, views-dev, access-security
- 4 NEUTRAL delta (0%): forms-api, database-api, theming, entities-fields

**Key insight:** Skills are most impactful for patterns that deviate from "obvious" implementations. Neutral skills cover domains where baseline Haiku already knows the patterns (FormBase, render arrays, Entity API basics, Database API).

**13 Skills from 18 Chapters:**

| # | Skill Name | Source Chapters | Tier |
|---|-----------|----------------|------|
| 1 | `drupal-module-scaffold` | Ch 1-2 | HIGH |
| 2 | `drupal-routing-controllers` | Ch 2, Ch 5 | HIGH |
| 3 | `drupal-forms-api` | Ch 2 | NEUT |
| 4 | `drupal-plugins-blocks` | Ch 2, Ch 7 | MOD |
| 5 | `drupal-entities-fields` | Ch 6-7, Ch 9, Ch 16 | NEUT |
| 6 | `drupal-config-storage` | Ch 6, Ch 13 | MOD |
| 7 | `drupal-access-security` | Ch 10, Ch 18 | MOD |
| 8 | `drupal-theming` | Ch 4, Ch 12 | NEUT |
| 9 | `drupal-caching` | Ch 11 | HIGH |
| 10 | `drupal-testing` | Ch 17 | HIGH |
| 11 | `drupal-database-api` | Ch 8 | NEUT |
| 12 | `drupal-views-dev` | Ch 15 | MOD |
| 13 | `drupal-batch-queue-cron` | Ch 14, Ch 3 | MOD |
| 14 | `drupal-coding-standards` | Cross-cutting | Baseline |

## Constraints

- **Skill size**: Each SKILL.md must be <500 lines per skill-creator anatomy rules
- **D10 baseline**: Code patterns must match book's D10 examples; D11 changes noted separately
- **Book accuracy**: Skills must faithfully represent book content, not hallucinate Drupal APIs

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Wave-based parallel creation | Foundational skills first so later skills can cross-reference | ✓ Good -- clean dependency chain |
| D10 primary, D11 notes | Book is D10; D11 differences are additive not rewrite | ✓ Good -- future-proof |
| Dual output (repo + ~/.claude) | GitHub publishing + local use | ✓ Good |
| skill-creator anatomy | <500 lines, frontmatter, references/, decision-guide format | ✓ Good -- consistent quality |
| Wrong-way callouts | Target common Claude mistakes per domain | ✓ Good -- high-delta skills all have strong callouts |
| Live eval with ddev | Real Drupal instances, not mocked | ✓ Good -- caught real bugs |
| Differentiating assertions | Target non-obvious patterns from SKILL.md, not standard Drupal | ✓ Good -- clean signal |
| Headless `claude -p` pipeline | Agent harness confounds A/B comparison (confirmed empirically) | ✓ Good -- 37.5% vs 0% delta on caching |
| Coding-standards baseline skill | phpcs noise obscured domain skill delta | ✓ Good -- isolates domain value |
| CRITICAL NEVER callout placement | Placing callout before DI flow produced +44.4% swing | ✓ Good -- content placement matters |
| Single-run eval design | Sufficient for tier classification; variance analysis deferred | ⚠️ Revisit if signal unclear |
| Neutral skills accepted | 4 skills cover baseline Haiku knowledge -- no iteration needed | ✓ Good -- honest classification |
| Eval-driven module development | Each v3.0 phase was an eval round, module emerged from measurement | ✓ Good -- honest delta, cumulative build |
| Browser eval deprecated (v2.0) | Zero discriminatory value for backend patterns | ⚠️ Revived for v4.0 UX testing |
| Three-tier assertions (v4.0) | Static + runtime + browser covers backend AND frontend quality | — Pending |

## Current Milestone: v5.0 AI Integration & Eval Tooling

**Goal:** Add AI-powered project management features to group_ai_pm (natural language task creation, AI-suggested assignments, batch AI operations) while first building the foundational tooling -- a Drush skill and an eval-author agent -- that makes all subsequent phases more robust and less context-heavy.

**Target features:**
- drupal-drush skill: Drush CLI knowledge for agents and runtime assertions
- eval-author Opus subagent: automated eval design (static + runtime + browser assertions)
- Skill gap fixes: entities-fields bundle_of, caching lazy_builder, forms-api #ajax hardening
- AI-powered task creation from natural language input
- AI-suggested task assignments and status updates
- Batch AI operations on multiple tasks
- Task history analytics with database schema and Views integration
- Final cross-cutting eval pass with eval-author agent

**Eval methodology evolution from v4.0:**
- v4.0: manual eval design in main context window, browser assertions as afterthought
- v5.0: eval-author agent (Opus) designs three-tier assertions systematically
- Browser assertions promoted to first-class (manual UAT found bugs automated pipeline missed)
- Drush skill enables smarter runtime assertions (drush php-eval, config:get, user:info)

---
*Last updated: 2026-03-09 after v5.0 milestone started*
