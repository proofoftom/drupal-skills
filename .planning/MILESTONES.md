# Milestones: Drupal Skills

## Shipped

### v1.0 Skill Authoring (Phases 1-7)

**Goal:** Author 13 Claude Code skills covering all 18 chapters of the Sipos Drupal 10 Module Development book, with eval infrastructure proving measurable value.

**Shipped:**
- 13 SKILL.md files across 4 waves (foundations, core workflow, presentation/quality, specialized)
- Each skill follows skill-creator anatomy (<500 lines, frontmatter, references/)
- D10 baseline with D11 differences noted
- Cross-references between related skills
- Wrong-way callouts for common Claude mistakes
- Eval infrastructure: setup/teardown scripts, evals.json for all 13 skills
- Phase 6 proved concept: caching +75%, scaffold +43%, entities +21%, testing +19% delta (Sonnet 4.6)
- Phase 7 partial: 9/13 skills evaluated, most showing 0% delta due to methodology gaps

**Key learnings:**
- Standard Drupal patterns (routing, forms, blocks) show 0% delta on Sonnet -- it knows these cold
- High-delta skills target knowledge gaps: cache golden rule, D11 compat, entity boilerplate
- Eval methodology matters: need proper model control, controlled environments, skill-creator cadence
- Agent subagents inherit parent model unless explicitly set in subagent frontmatter

**Phases:** 1-7 (last phase: 7)
**Completed:** 2026-03-06
