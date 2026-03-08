# Milestones: Drupal Skills

## v2.0 Eval & Optimization Loop (Shipped: 2026-03-08)

**Goal:** Empirically prove all 13 Drupal skills produce measurably better code than baseline Haiku, with clean data from a robust autonomous eval pipeline.

**Phases:** 5 phases (8-12), 23 plans

**Key accomplishments:**
- Built headless eval pipeline (`claude -p`) after discovering and eliminating agent harness confound
- Rewrote all 13 eval prompts for fresh Drupal 10 instances with hint-free, differentiating assertions
- Validated pipeline on calibration skills (caching +37.5%, scaffold +33.3%)
- Ran all 13 skills through eval pipeline, producing graded benchmarks for every skill
- Created coding-standards skill + patched 2 SKILL.md files, flipping 3 negative-delta skills to positive
- Final portfolio: 4 HIGH (+31.6% avg), 5 MOD (+11.7% avg), 4 NEUT (0%), 0 NEGATIVE

**Key learnings:**
- Agent harness confound is real: subagent system prompt provides implicit Drupal knowledge, inflating without-skill scores
- Headless `claude -p` with `--model haiku` is the only clean way to measure skill delta
- Skill content placement matters more than presence -- CRITICAL NEVER callout placement produced +44.4% swing on routing-controllers
- 4 neutral skills (forms-api, theming, database-api, entities-fields) represent baseline Haiku knowledge -- no skill needed
- phpcs compliance is a cross-cutting concern best handled by a dedicated coding-standards skill loaded alongside domain skills

**Known gaps:**
- PIPE-02: Batch orchestrator was manual (skills run 1 at a time), not automated 3-4 per session
- ANLZ-01: All 13 skills have benchmarks, but REQUIREMENTS.md checkbox was missed during execution
- 4 neutral-delta skills may benefit from harder eval scenarios (Group contrib, lazy_builder caching)

**Timeline:** 2026-03-06 to 2026-03-08 (3 days)
**Commits:** 52
**Git range:** `21e8c29..267194d`
**Archive:** milestones/v2.0-ROADMAP.md, milestones/v2.0-REQUIREMENTS.md

---

## v1.0 Skill Authoring (Shipped: 2026-03-07)

**Goal:** Author 13 Claude Code skills covering all 18 chapters of the Sipos Drupal 10 Module Development book, with eval infrastructure proving measurable value.

**Phases:** 7 phases (1-7), 28 plans

**Key accomplishments:**
- Authored 13 SKILL.md files across 4 waves (foundations, core workflow, presentation/quality, specialized)
- Each skill follows skill-creator anatomy (<500 lines, decision-guide format, wrong-way callouts, D10/D11 dual syntax)
- Built eval infrastructure: setup/teardown scripts, evals.json for all 13 skills, E2E assertion helpers
- Phase 6 live eval proved concept: caching +75%, scaffold +43%, entities +21%, testing +19% delta
- Phase 7 expanded to all 13 skills; rewrote assertions with source-material-driven differentiating patterns
- Packaged for distribution: install.sh, README, MIT license

**Key learnings:**
- Standard Drupal patterns (routing, forms, blocks) show 0% delta on Sonnet -- it knows these cold
- High-delta skills target knowledge gaps: cache golden rule, D11 compat, entity boilerplate
- Eval methodology matters: need proper model control, controlled environments, differentiating assertions
- Agent subagents inherit parent model unless explicitly set in subagent frontmatter

**Known gaps (carried to v2.0):**
- FULL-05: Skills with weak deltas not yet iterated on
- FULL-06: Final analysis with stabilized results not yet produced
- Phase 7 plans 07-07 and 07-08 incomplete (re-run with new assertions + final analysis)

**Timeline:** 2026-03-05 to 2026-03-06 (2 days)
**Lines of skill content:** ~6,990 (Markdown + YAML)
**Archive:** milestones/v1.0-ROADMAP.md, milestones/v1.0-REQUIREMENTS.md
