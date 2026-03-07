# Roadmap: Drupal Skills

## Milestones

- v1.0 **Skill Authoring** -- Phases 1-7 (shipped 2026-03-07)
- v2.0 **Eval & Optimization Loop** -- Phases 8-12 (in progress)

## Phases

<details>
<summary>v1.0 Skill Authoring (Phases 1-7) -- SHIPPED 2026-03-07</summary>

- [x] Phase 1: Foundations (3/3 plans) -- completed 2026-03-05
- [x] Phase 2: Core Workflow (4/4 plans) -- completed 2026-03-06
- [x] Phase 3: Presentation and Quality (4/4 plans) -- completed 2026-03-06
- [x] Phase 4: Specialized Patterns (2/2 plans) -- completed 2026-03-06
- [x] Phase 5: Eval, Optimization, and Packaging (2/2 plans) -- completed 2026-03-06
- [x] Phase 6: Live Eval Loop (5/5 plans) -- completed 2026-03-06
- [x] Phase 7: Full Eval-Optimize Loop (6/8 plans) -- completed 2026-03-06 (2 plans carried to v2.0)

Full details: milestones/v1.0-ROADMAP.md

</details>

### v2.0 Eval & Optimization Loop

- [x] **Phase 8: Eval Infrastructure** - Create subagent definitions (eval-executor, eval-grader, eval-browser), fresh D10 setup script with auto-retry, validate subagent model control
- [x] **Phase 9: Eval Prompt Rewrite** - Rewrite all 13 eval prompts for fresh Drupal 10 instances (replace os-kg references), adjust assertions if needed for new prompt context
- [x] **Phase 10: Pipeline Validation** - Run 2-3 calibration skills (caching, scaffold) end-to-end through new pipeline, validate grading.json/benchmark.json schemas, confirm meaningful deltas (completed 2026-03-07)
- [ ] **Phase 11: Batch Execution** - Run all 13 skills through eval pipeline in batches of 3-4 per session, produce graded benchmarks for every skill
- [ ] **Phase 12: Analysis & Optimization** - Classify skills into tiers, iterate on weak deltas (tighten assertions or improve skill content), produce final report with stabilized results

## Phase Details

### Phase 8: Eval Infrastructure
**Goal**: Build the subagent-based eval pipeline foundation so that a single skill can be evaluated end-to-end without manual model switching or environment hacks
**Depends on**: v1.0 (skills, evals.json, teardown scripts)
**Requirements**: INFRA-01, INFRA-02, INFRA-03, INFRA-04
**Plans:** 2 plans
Plans:
- [x] 08-01-PLAN.md -- Create subagent definitions, setup script, and teardown update
- [x] 08-02-PLAN.md -- End-to-end pipeline validation with smoke test
**Success Criteria** (what must be TRUE):
  1. `.claude/agents/eval-executor.md` exists with `model: sonnet` and spawns correctly
  2. `.claude/agents/eval-grader.md` exists and produces compliant grading.json
  3. `eval/setup-fresh-drupal10.sh` creates a working Drupal 10 ddev instance with auto-retry
  4. `.claude/agents/eval-browser.md` exists and can navigate a ddev site via drush uli + agent-browser
  5. Knowledge isolation verified: with-skill agent reads SKILL.md, without-skill agent never sees it

### Phase 9: Eval Prompt Rewrite
**Goal**: All 13 eval prompts work against a vanilla Drupal 10 site instead of os-knowledge-garden
**Depends on**: Phase 8
**Requirements**: EVAL-01, EVAL-02
**Plans:** 2 plans
Plans:
- [x] 09-01-PLAN.md -- Rewrite 8 straightforward eval prompts (text swap, no hint issues)
- [x] 09-02-PLAN.md -- Rewrite 5 complex eval prompts (hint removal, testing redesign, entities recontextualization)
**Success Criteria** (what must be TRUE):
  1. All 13 evals.json have prompts that reference "Drupal 10 site" not os-kg/Open Social
  2. Prompts describe realistic module development tasks grounded in Sipos book patterns
  3. Differentiating assertions still target SKILL.md non-obvious patterns (adjust if prompt changes affect them)
  4. No prompt contains implementation hints that would teach the without-skill agent what to produce

### Phase 10: Pipeline Validation
**Goal**: Prove the new pipeline produces valid, meaningful data by running calibration skills with known deltas
**Depends on**: Phase 9
**Requirements**: PIPE-01
**Plans:** 2/2 plans complete
Plans:
- [x] 10-01-PLAN.md -- Run caching calibration skill through full pipeline (first real grader validation)
- [x] 10-02-PLAN.md -- Run scaffold calibration skill and cross-skill pipeline validation
**Success Criteria** (what must be TRUE):
  1. Caching skill (known +75% delta in v1.0) produces >30% delta through new pipeline
  2. Scaffold skill (known +43% delta in v1.0) produces >15% delta through new pipeline
  3. grading.json and benchmark.json match expected schemas
  4. Full pipeline cycle (setup -> execute -> grade -> aggregate -> teardown) completes without manual intervention

### Phase 11: Batch Execution
**Goal**: All 13 skills have graded benchmarks from the new pipeline
**Depends on**: Phase 10
**Requirements**: PIPE-02, PIPE-03, ANLZ-01
**Plans:** 1/13 plans executed
Plans:
- [ ] 11-01-PLAN.md -- Workspace setup and Phase 10 calibration data copy
- [ ] 11-02-PLAN.md -- Batch 1: access-security, routing-controllers, plugins-blocks, forms-api
- [ ] 11-03-PLAN.md -- Batch 2: entities-fields, config-storage, database-api, batch-queue-cron
- [ ] 11-04-PLAN.md -- Batch 3: testing, theming, views-dev
- [ ] 11-05-PLAN.md -- Consolidation: validate all 13 benchmarks and produce results table
**Success Criteria** (what must be TRUE):
  1. All 13 skills have benchmark.json with with-skill and without-skill pass rates
  2. Eval runs completed in batches of 3-4 skills per session
  3. All workspace directories follow correct layout for aggregate_benchmark.py

### Phase 12: Analysis & Optimization
**Goal**: Final analysis with tier classifications, iteration on weak deltas, and overall verdict on skill value
**Depends on**: Phase 11
**Requirements**: ANLZ-02, ANLZ-03, ANLZ-04, CARRY-01, CARRY-02
**Success Criteria** (what must be TRUE):
  1. All 13 skills classified into tiers: High (>15%), Moderate (5-15%), Low (<5%)
  2. Skills with weak deltas analyzed -- at least 1 iteration of assertion tightening or skill improvement attempted
  3. Final report produced with stabilized results and overall verdict
  4. generate_review.py HTML viewers available for human review

## Progress

**Execution Order:**
Phases execute in numeric order: 8 -> 9 -> 10 -> 11 -> 12

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1-7 | v1.0 | 26/28 | Shipped | 2026-03-07 |
| 8. Eval Infrastructure | v2.0 | 2/2 | Complete | 2026-03-07 |
| 9. Eval Prompt Rewrite | v2.0 | 2/2 | Complete | 2026-03-07 |
| 10. Pipeline Validation | v2.0 | 2/2 | Complete | 2026-03-07 |
| 11. Batch Execution | 1/13 | In Progress|  | - |
| 12. Analysis & Optimization | v2.0 | 0/? | Not started | - |
