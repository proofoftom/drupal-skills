# Research Summary: v2.0 Eval & Optimization Loop

**Synthesized:** 2026-03-07
**Sources:** STACK.md, FEATURES.md, ARCHITECTURE.md, PITFALLS.md (all 2026-03-06)

## Core Architecture

Opus orchestrator spawns Sonnet subagents (`model: sonnet` in frontmatter) for controlled A/B skill evaluation against fresh Drupal 10 ddev instances. Two separate agents per skill ensure knowledge isolation. Results feed into skill-creator's grading pipeline (grading.json -> benchmark.json -> HTML viewer).

## Critical Changes from v1.0

| Change | v1.0 Approach | v2.0 Approach |
|--------|---------------|---------------|
| Model control | `/model sonnet` manual switching | `model: sonnet` in subagent frontmatter |
| Eval environment | os-knowledge-garden clone | Fresh Drupal 10 ddev instances |
| Orchestration | Manual from main session | Batch orchestrator script |
| Grading | Inline by orchestrator | Dedicated eval-grader subagent |
| Eval prompts | os-kg task-based | Book-knowledge-based for fresh D10 |
| Assertions | Standard patterns (0% delta) | Differentiating patterns from SKILL.md |

## What's Already Done (carry from v1.0)

- 13 evals.json with differentiating assertions (phase 07-06 rewrite)
- eval/teardown-drupal-env.sh
- eval/e2e-assert.sh with 5 assertion types
- agent-browser 0.16.3 installed globally
- Workspace directory layout conventions

## What Must Be Built

1. **eval-executor.md subagent** -- `model: sonnet`, standardized prompt, knowledge isolation
2. **eval-grader.md subagent** -- follows skill-creator grader.md, produces compliant grading.json
3. **Fresh D10 setup script** -- replaces os-kg-based setup
4. **Rewritten eval prompts** -- for fresh D10 (not os-kg tasks)
5. **Batch orchestrator** -- loops through skills: setup, execute, grade, aggregate, teardown
6. **eval-browser subagent** -- agent-browser for E2E/UAT on visual skills

## Top Pitfalls

1. **Non-discriminating assertions** (highest risk) -- must test SKILL.md non-obvious patterns, not standard Drupal
2. **Knowledge contamination** -- separate agents, no global skills, no CLAUDE.md references
3. **Context overflow** -- max 3-4 skills per session, externalize state to disk
4. **ddev-router failures** -- auto-retry with router restart in setup script
5. **Grader bias** -- binary assertions, automated checks where possible

## Recommended Phasing

1. Infrastructure: subagent definitions + fresh D10 setup script
2. Eval prompt rewrite: all 13 prompts for fresh D10
3. Pipeline validation: run 2-3 calibration skills end-to-end
4. Batch execution: all 13 skills through pipeline
5. Analysis + iteration: tier classification, weak delta optimization

---
*Supersedes v1.0 SUMMARY.md (2026-03-05)*
