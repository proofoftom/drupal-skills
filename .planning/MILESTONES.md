# Milestones: Drupal Skills

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
