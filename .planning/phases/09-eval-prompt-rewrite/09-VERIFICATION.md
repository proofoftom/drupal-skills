---
phase: 09-eval-prompt-rewrite
verified: 2026-03-07T05:30:00Z
status: passed
score: 4/4 must-haves verified
re_verification: false
---

# Phase 9: Eval Prompt Rewrite Verification Report

**Phase Goal:** All 13 eval prompts work against a vanilla Drupal 10 site instead of os-knowledge-garden
**Verified:** 2026-03-07T05:30:00Z
**Status:** passed
**Re-verification:** No -- initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | All 13 evals.json have prompts that reference "Drupal 10 site" not os-kg/Open Social | VERIFIED | `grep -ri "Open Social\|os-knowledge-garden\|os-kg" skills/drupal-*/evals/evals.json` returns exit code 1 (zero matches). All 13 files contain "Drupal 10" in their prompt field. |
| 2 | Prompts describe realistic module development tasks grounded in Sipos book patterns | VERIFIED | All 13 prompts describe concrete Drupal module development tasks: block plugins (caching, plugins-blocks), forms (forms-api, config-storage), entities (entities-fields), routing/controllers, testing, theming, database API, batch/queue/cron, Views, access control, and module scaffolding. Each maps to a Sipos book chapter topic already encoded in SKILL.md. |
| 3 | Differentiating assertions still target SKILL.md non-obvious patterns (adjust if prompt changes affect them) | VERIFIED | Testing expectations properly updated (3 of 8 changed: installEntitySchema -> parent::setUp(), installSchema -> container->get(), modules list -> just 'calculator'). All other 12 skills' expectations preserved byte-identical. Prompt-expectation coherence confirmed for all 5 complex rewrites. |
| 4 | No prompt contains implementation hints that would teach the without-skill agent what to produce | VERIFIED | 3 high-impact hints removed: (a) "Do NOT use max-age: 0" from caching, (b) "Do NOT process items directly in hook_cron" and "cron.time setting" from batch-queue-cron, (c) "do NOT use the Entity API" from database-api. Remaining low-impact hints (e.g., "Use AccessResult objects" in access-security, "Use render arrays with #theme" in theming) were deliberately retained per research classification -- they describe functional requirements, not the non-obvious implementation patterns that assertions test. |

**Score:** 4/4 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `skills/drupal-access-security/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON, zero forbidden refs |
| `skills/drupal-batch-queue-cron/evals/evals.json` | D10 prompt, hints removed | VERIFIED | Contains "Drupal 10 site", no queue pattern hint, no cron.time hint |
| `skills/drupal-caching/evals/evals.json` | D10 prompt, max-age hint removed | VERIFIED | Contains "Drupal 10 site", no "Do NOT use max-age: 0" |
| `skills/drupal-config-storage/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-database-api/evals/evals.json` | D10 prompt, Entity API hint removed | VERIFIED | Contains "Drupal 10 site", no "do NOT use the Entity API" |
| `skills/drupal-entities-fields/evals/evals.json` | D10 prompt, recontextualized | VERIFIED | Contains "Drupal 10 site", no os-kg framing, keeps KnowledgeResource entity name |
| `skills/drupal-forms-api/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-module-scaffold/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-plugins-blocks/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-routing-controllers/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-testing/evals/evals.json` | Self-contained calculator scenario | VERIFIED | Contains "calculator", zero social_ai_indexing refs, expectations updated (3 of 8 changed) |
| `skills/drupal-theming/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |
| `skills/drupal-views-dev/evals/evals.json` | D10-rewritten prompt | VERIFIED | Contains "Drupal 10 site", valid JSON |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `drupal-caching prompt` | `drupal-caching expectations` | Prompt describes cache performance requirement without teaching max-age:0 anti-pattern | VERIFIED | Prompt says "Add proper cache metadata... invalidates when displayed content changes, varies by page and user." Expectations check for #cache tags+contexts, no max-age:0. Coherent. |
| `drupal-testing prompt` | `drupal-testing expectations` | Calculator prompt exercises KernelTestBase, $modules, service container patterns | VERIFIED | Prompt asks for calculator service + kernel test. Expectations check for KernelTestBase, $modules with 'calculator', parent::setUp(), container->get(), @group, assertEquals. All coherent. |
| `drupal-batch-queue-cron prompt` | `drupal-batch-queue-cron expectations` | Prompt says "process via a queue" without teaching cron.time annotation | VERIFIED | Functional requirement preserved, implementation hint removed. Expectations still check for QueueWorker cron time annotation and hook_cron queue-only pattern. |
| `drupal-database-api prompt` | `drupal-database-api expectations` | Prompt says "custom database table" without banning Entity API | VERIFIED | "Custom database table" and "schema definition" naturally implies database API. Expectation 6 still checks for no Entity API usage. |
| `drupal-entities-fields prompt` | `drupal-entities-fields expectations` | Recontextualized prompt preserves entity/module names | VERIFIED | knowledge_resource module name and KnowledgeResource entity name preserved. All expectations referencing these names remain valid. |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-----------|-------------|--------|----------|
| EVAL-01 | 09-01, 09-02 | All 13 eval prompts rewritten for fresh Drupal 10 instances (not os-kg tasks) | SATISFIED | All 13 evals.json prompts reference "Drupal 10 site". Zero instances of "Open Social", "os-knowledge-garden", "os-kg", or "social_ai_indexing" across any eval file. Testing eval redesigned with self-contained calculator scenario for vanilla D10. |
| EVAL-02 | 09-02 | All 13 evals.json have differentiating assertions targeting SKILL.md non-obvious patterns | SATISFIED | Existing differentiating assertions from v1.0 07-06 preserved across all 12 unchanged skills. Testing eval expectations updated to match calculator scenario while maintaining non-obvious pattern coverage (KernelTestBase choice, $modules array, @group annotation). 3 high-impact hints removed from prompts to increase assertion differentiation power. |

No orphaned requirements found. REQUIREMENTS.md maps only EVAL-01 and EVAL-02 to Phase 9, both covered by plans 09-01 and 09-02.

### Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| `skills/drupal-database-api/evals/evals.json` | 16 | Expectation 6 says "the prompt explicitly says to use Database API" -- prompt says "Use Drupal's database abstraction layer" but no longer explicitly bans Entity API | Info | Expectation parenthetical is technically accurate (prompt does reference database abstraction). The grader checks for Entity API usage regardless. No functional impact. |

No TODOs, FIXMEs, placeholders, or stub patterns found in any evals.json file.

### Human Verification Required

### 1. Eval Delta Measurement

**Test:** Run Phase 10 eval pipeline with 2-3 calibration skills (caching, batch-queue-cron, database-api) to measure whether hint removal produces meaningful assertion deltas.
**Expected:** Without-skill runs should now fail on assertions that were previously passed due to prompt hints (max-age:0, queue pattern, Entity API).
**Why human:** Actual eval execution requires ddev infrastructure, model invocation, and runtime behavior observation that cannot be verified statically.

### 2. Calculator Testing Eval Execution

**Test:** Run testing eval with calculator prompt to verify self-contained scenario works on vanilla D10.
**Expected:** With-skill executor produces a working calculator module with proper kernel test. Without-skill executor makes KernelTestBase vs BrowserTestBase or $modules array errors.
**Why human:** Need to verify calculator service loads correctly in the kernel test container, which requires actual Drupal execution.

### Gaps Summary

No gaps found. All 4 success criteria from ROADMAP.md are verified:

1. All 13 evals.json reference "Drupal 10 site" with zero forbidden references.
2. All prompts describe realistic Drupal module development tasks.
3. All differentiating assertions preserved or properly updated (testing eval: 3 of 8 expectations adjusted for calculator scenario).
4. High-impact implementation hints removed from 3 skills. Remaining low-impact hints deliberately retained per research analysis.

All 13 files are valid JSON. All 5 referenced commits verified in git history. Prompt-expectation coherence confirmed for all 13 skills.

---

_Verified: 2026-03-07T05:30:00Z_
_Verifier: Claude (gsd-verifier)_
