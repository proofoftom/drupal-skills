# E2E Eval Infrastructure + Full Skill Eval Loop

**Date:** 2026-03-06
**Phase:** 7 (replanned)
**Status:** Approved

## Problem

Current eval assertions stop at `drush en` + code inspection. Many module bugs only surface at runtime: broken routes (404), forms that don't render, blocks that throw errors, permissions that don't gate access. The existing grading pipeline catches syntax and structural issues but misses user-facing behavior.

## Solution

Add an E2E verification tier to the grading phase using [agent-browser](https://github.com/vercel-labs/agent-browser) (headless Chromium CLI). After the Sonnet agent generates a module, the grader enables it via drush, then uses agent-browser to navigate the ddev site and verify runtime behavior.

## Architecture

```
Eval Pipeline (upgraded)
─────────────────────────────────────────────────────
1. Setup ddev env (existing)
2. Headless Sonnet generates module (existing)
3. Grading phase (UPGRADED):
   a. Code inspection (grep, file checks)     ← existing
   b. drush verification (drush en, php-eval)  ← existing
   c. agent-browser E2E (navigate, click,      ← NEW
      fill forms, check rendered output)
4. Teardown (existing)
5. Aggregate benchmarks (existing)
```

## E2E Infrastructure

- **agent-browser** installed on host machine (npm global + `agent-browser install --with-deps`)
- Connects to ddev sites at `https://{project}.ddev.site`
- mkcert already trusted on host — no TLS cert issues
- Grading scripts open a browser session, navigate to routes, verify rendering
- Session persists across multiple assertions within one grading run

## Assertion Tiers

| Tier | Method | What it catches | Example |
|------|--------|----------------|---------|
| Static | grep, file checks | Missing files, wrong structure, bad annotations | `.info.yml has core_version_requirement` |
| Runtime | drush, php-eval | Module won't enable, broken services, schema errors | `drush en module` exit code 0 |
| E2E | agent-browser | Routes 404, forms don't render, blocks invisible, access not enforced | `agent-browser open /admin/config/...` shows form |

Every eval gets assertions from all 3 tiers.

## Eval Scenario Design

Mix of invented modules and real os-knowledge-garden patterns:
- **Invented modules** for standalone patterns (scaffold, caching, batch-queue-cron)
- **Real patterns** where they fit — forms-api settings for social_ai_indexing, routing-controllers API for knowledge_resource, access-security around group_treasury operations

Web3 modules (siwe_login, safe_smart_accounts, group_treasury, social_group_treasury) serve as realistic scenario inspiration. Gaps discovered in drush usage or QA patterns feed future drush and QA skills.

## Per-Skill E2E Assertions

| Skill | E2E Assertion |
|-------|--------------|
| routing-controllers | Navigate to API endpoint, verify JSON response (not 404) |
| forms-api | Open settings form, verify fields render, submit saves config |
| plugins-blocks | Place block via drush, verify block content appears on page |
| access-security | Anonymous gets 403 on restricted route, admin gets 200 |
| theming | Template renders with CSS library loaded |
| config-storage | Change config via drush, page reflects new value |
| database-api | Trigger tracking, analytics page shows aggregated data |
| views-dev | Views listing page renders with expected columns |
| batch-queue-cron | Run cron, processed content visible on site |
| caching (upgrade) | Verify cache headers present in response |
| entities-fields (upgrade) | Entity form renders, CRUD via UI works |
| module-scaffold (upgrade) | Module's declared routes are accessible |
| testing (upgrade) | N/A (test execution is already runtime) |

## Wave Structure

| Wave | What | Plans |
|------|------|-------|
| 1 | Install agent-browser, create E2E grading helpers, CLAUDECODE fix | 1 plan |
| 2 | Author all evals.json (9 new + upgrade 4 existing with E2E assertions) | 1-2 plans |
| 3 | Run eval batches (1 agent per skill, batches of 2-3) | 3 plans |
| 4 | Analyze, iterate weak deltas, final report | 1 plan |

## Eval Count Per Skill

Varies by skill complexity:
- 1 eval: skills with a single clear pattern (scaffold, caching, batch-queue-cron)
- 2 evals: skills with distinct sub-patterns (forms-api: ConfigFormBase + custom form, plugins-blocks: block with config + block with DI)

## Future Skill Lessons

During grading, document every:
- drush command gap → feeds drupal-drush skill
- E2E test pattern difficulty → feeds QA skill
- Web3 module interaction pattern → feeds potential web3 skills

## Decisions

- **agent-browser on host** (not inside ddev container) — mkcert trusted, simpler, faster
- **Grading phase runs E2E** — Sonnet agent doesn't know about browser testing
- **Sonnet 4.6 as executor** — Opus shows 0% delta (too capable)
- **Batch size 2-3 skills** — RAM constraint (~8GB available, each ddev pair ~1GB)
