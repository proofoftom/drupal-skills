# Roadmap: Drupal Skills

## Milestones

- ✅ **v1.0 Skill Authoring** -- Phases 1-7 (shipped 2026-03-07)
- ✅ **v2.0 Eval & Optimization Loop** -- Phases 8-12 (shipped 2026-03-08)
- ✅ **v3.0 Group AI Project Management** -- Phases 13-17 (shipped 2026-03-08)
- ✅ **v4.0 UX Overhaul** -- Phases 18-21 (shipped 2026-03-09)
- 📋 **v5.0 AI Integration & Eval Tooling** -- Phases 22-27 (planned)

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

<details>
<summary>v2.0 Eval & Optimization Loop (Phases 8-12) -- SHIPPED 2026-03-08</summary>

- [x] Phase 8: Eval Infrastructure (2/2 plans) -- completed 2026-03-07
- [x] Phase 9: Eval Prompt Rewrite (2/2 plans) -- completed 2026-03-07
- [x] Phase 10: Pipeline Validation (2/2 plans) -- completed 2026-03-07
- [x] Phase 11: Batch Execution (13/13 plans) -- completed 2026-03-07
- [x] Phase 12: Analysis & Optimization (4/4 plans) -- completed 2026-03-08

Full details: milestones/v2.0-ROADMAP.md

</details>

<details>
<summary>v3.0 Group AI Project Management (Phases 13-17) -- SHIPPED 2026-03-08</summary>

- [x] Phase 13: Plugin Packaging (2/2 plans) -- completed 2026-03-08
- [x] Phase 14: Module Foundation -- completed 2026-03-08
- [x] Phase 15: Group & AI Integration -- completed 2026-03-08
- [x] Phase 16: Views, Theming & Processing -- completed 2026-03-08
- [x] Phase 17: Testing & Final Eval -- completed 2026-03-08

Full details: milestones/v3.0-ROADMAP.md

</details>

<details>
<summary>v4.0 UX Overhaul (Phases 18-21) -- SHIPPED 2026-03-09</summary>

- [x] Phase 18: REST API + Vue Infrastructure + Basic Board (3/3 plans)
- [x] Phase 19: Interactions + Detail Panel + Visual Polish (3/3 plans)
- [x] Phase 20: Dashboard + List Enhancements (2/2 plans)
- [x] Phase 21: Testing + Final Eval (2/2 plans)

Full details: milestones/v4.0-ROADMAP.md

</details>

### v5.0 AI Integration & Eval Tooling

**Milestone Goal:** Add AI-powered project management features to group_ai_pm while first building foundational tooling -- a Drush skill and an eval-author agent -- that makes all subsequent phases more robust and less context-heavy.

- [x] **Phase 22: Drush Skill + Eval-Author Agent** - Build the 15th skill and automated eval design agent (completed 2026-03-09)
- [ ] **Phase 23: Skill Gap Fixes + Eval-Author Validation** - Patch 3 skills and validate eval-author against gold-standard
- [ ] **Phase 24: AI Task Service + NL Task Creation** - Central AI service layer with natural language task creation
- [ ] **Phase 25: Batch AI Operations + Agent Tools** - Queue-based batch processing and remaining AiFunctionCall plugins
- [ ] **Phase 26: Task History Analytics** - Custom history table, Views integration, analytics endpoint and dashboard
- [ ] **Phase 27: Cross-Cutting Eval + Final Report** - Full pipeline validation measuring v5.0 cumulative impact

## Phase Details

### Phase 22: Drush Skill + Eval-Author Agent
**Goal**: Eval tooling foundation exists -- a 15th skill teaches Drush USAGE (self-verification, scaffolding, debugging, Drupal-first entity operations) and an Opus subagent automates three-tier assertion design
**Depends on**: Nothing (first v5.0 phase; builds on shipped v4.0 infrastructure)
**Requirements**: TOOL-01, TOOL-02, TOOL-03, TOOL-04
**Success Criteria** (what must be TRUE):
  1. `skills/drupal-drush/SKILL.md` teaches Drush usage for development: self-verification recipes, `drush generate` scaffolding, debugging via watchdog, and Drupal-first principle (entity:save over sql:query). Command-authoring reference at `references/command-authoring.md`.
  2. `skills/drupal-drush/evals/evals.json` contains assertions targeting Drush usage patterns (built-in commands over php-eval, watchdog checks, entity API over SQL) -- not boilerplate file existence checks
  3. Eval-author agent (`.claude/agents/eval-author.md`) accepts skill content + module code + phase prompt and outputs three-tier assertions (static + runtime + browser)
  4. Eval-author output enforces assertion category distribution (60% differentiating, 20% wiring, max 20% structural) with explicit tautology rejection
**Plans:** 2/2 plans complete
Plans:
- [x] 22-01-PLAN.md -- Author Drush usage skill and eval assertions
- [x] 22-02-PLAN.md -- Create eval-author Opus subagent

### Phase 23: Skill Gap Fixes + Eval-Author Validation
**Goal**: Three skill gaps are closed and the eval-author agent is validated against known-good evals before relying on it for new phases
**Depends on**: Phase 22
**Requirements**: TOOL-05, TOOL-06, TOOL-07
**Success Criteria** (what must be TRUE):
  1. Eval-author agent, given Phase 18 inputs, produces assertions that match or exceed the gold-standard Phase 18 evals (17 assertions, +23.3% delta) in quality and distribution
  2. entities-fields SKILL.md includes bundle_of pattern with hook_update_N() for schema changes
  3. caching SKILL.md includes lazy_builder pattern and CacheableMetadata bubbling examples
  4. forms-api SKILL.md includes concrete #ajax patterns (callback, wrapper, AjaxResponse) within the 500-line budget
**Plans:** 1/2 plans executed
Plans:
- [ ] 23-01-PLAN.md -- Patch 3 skill gaps (entities-fields bundle_of, caching CacheableMetadata bubbling, forms-api #ajax)
- [ ] 23-02-PLAN.md -- Validate eval-author agent against Phase 18 gold-standard

### Phase 24: AI Task Service + NL Task Creation
**Goal**: Users can create tasks from natural language input via both the REST API and AI agent tools
**Depends on**: Phase 23 (skill patches change eval baselines; must be stable before measuring AI features)
**Requirements**: AI-01, AI-02, AI-03, AI-04
**Success Criteria** (what must be TRUE):
  1. AiTaskService is injectable by both REST controllers and AiFunctionCall plugins, encapsulating all AI logic in one service
  2. Module installs and functions normally when the AI module is not present (optional @? dependency verified)
  3. CreateTaskTool AiFunctionCall plugin creates a Task entity from natural language input, parsing title, description, status, and assignee
  4. POST endpoint accepts natural language text and returns created task JSON with all parsed fields
**Plans**: TBD

### Phase 25: Batch AI Operations + Agent Tools
**Goal**: Users can run AI operations on multiple tasks at once, and AI agents have a complete toolkit for task management
**Depends on**: Phase 24
**Requirements**: AI-05, AI-06, AI-07
**Success Criteria** (what must be TRUE):
  1. BatchUpdateTool processes multiple tasks via Queue API with dry-run mode that reports what would change without persisting
  2. Queue workers implement three-catch pattern (SuspendQueueException, AiRateLimitException + RequeueException, generic Exception) -- rate-limited items are requeued, not deleted
  3. UpdateTaskStatusTool AiFunctionCall plugin changes task status through AI agent conversation
  4. Per-item error reporting returns success/failure status for each task in a batch operation
**Plans**: TBD

### Phase 26: Task History Analytics
**Goal**: All task changes are recorded and surfaceable through Views reports, REST API, and the dashboard
**Depends on**: Phase 25 (history captures both manual and AI-driven mutations)
**Requirements**: ANLZ-01, ANLZ-02, ANLZ-03, ANLZ-04, ANLZ-05, ANLZ-06
**Success Criteria** (what must be TRUE):
  1. `group_ai_pm_task_history` table exists with composite indexes (task_id+timestamp, field_name+timestamp, uid+timestamp) via hook_schema()
  2. Editing any task field (manually or via AI) creates a history record captured by hook_entity_presave()
  3. hook_update_N() creates the history table on existing installations (paired with hook_schema())
  4. History table is exposed to Views via hook_views_data() and produces working Views reports
  5. Analytics REST endpoint returns aggregated metrics (cycle time, throughput, bottlenecks) via CacheableJsonResponse, and dashboard displays analytics summary
**Plans**: TBD

### Phase 27: Cross-Cutting Eval + Final Report
**Goal**: v5.0 cumulative impact is measured with empirical data comparable to v3.0 (+16.7%) and v4.0 (+7.6%) baselines
**Depends on**: Phase 26 (all features must be complete before cross-cutting measurement)
**Requirements**: EVAL-01, EVAL-02
**Success Criteria** (what must be TRUE):
  1. Cross-cutting eval pass runs the full A/B pipeline using eval-author-designed assertions across all v5.0 features
  2. v5.0 aggregate delta is computed with per-phase breakdowns and compared to v3.0 (+16.7%) and v4.0 (+7.6%) baselines in a final report
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 22 -> 23 -> 24 -> 25 -> 26 -> 27

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1-7 | v1.0 | 26/28 | Shipped | 2026-03-07 |
| 8-12 | v2.0 | 23/23 | Shipped | 2026-03-08 |
| 13-17 | v3.0 | Complete | Shipped | 2026-03-08 |
| 18-21 | v4.0 | 10/10 | Shipped | 2026-03-09 |
| 22. Drush Skill + Eval-Author Agent | v5.0 | 2/2 | Complete | 2026-03-09 |
| 23. Skill Gap Fixes + Eval-Author Validation | 1/2 | In Progress|  | - |
| 24. AI Task Service + NL Task Creation | v5.0 | 0/TBD | Not started | - |
| 25. Batch AI Operations + Agent Tools | v5.0 | 0/TBD | Not started | - |
| 26. Task History Analytics | v5.0 | 0/TBD | Not started | - |
| 27. Cross-Cutting Eval + Final Report | v5.0 | 0/TBD | Not started | - |
