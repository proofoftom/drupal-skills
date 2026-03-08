# Roadmap: Drupal Skills

## Milestones

- ✅ **v1.0 Skill Authoring** -- Phases 1-7 (shipped 2026-03-07)
- ✅ **v2.0 Eval & Optimization Loop** -- Phases 8-12 (shipped 2026-03-08)
- 🚧 **v3.0 Group AI Project Management** -- Phases 13-17 (in progress)

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

### v3.0 Group AI Project Management (In Progress)

**Milestone Goal:** Build a real Drupal contrib module (Group-based project management with AI Agents integration) as the ultimate integration eval -- validating that all 14 skills auto-trigger and produce better code than baseline, in a realistic development workflow.

- [ ] **Phase 13: Plugin Packaging** - Package skills as Claude Code plugin with auto-triggering validation
- [ ] **Phase 14: Module Foundation** - Scaffold module with entities, routes, and forms
- [ ] **Phase 15: Group & AI Integration** - Group relation plugins, permissions, and AI agent tools
- [ ] **Phase 16: Views, Theming & Processing** - User-facing displays, templates, caching, and background jobs
- [ ] **Phase 17: Testing & Final Eval** - Automated tests, quality pass, and full delta report

## Phase Details

### Phase 13: Plugin Packaging
**Goal**: Skills are installable as a Claude Code plugin that auto-triggers from natural Drupal development prompts
**Depends on**: Nothing (first phase of v3.0)
**Requirements**: PLUG-01, PLUG-02, PLUG-03, PLUG-04, EVAL-01
**Success Criteria** (what must be TRUE):
  1. Running `claude --plugin-dir .` in the repo root loads all 14 skills (verifiable via debug output)
  2. Natural Drupal development prompts (e.g., "create a custom entity type") activate the relevant skill without explicit skill references, at >80% rate across a sample of 10+ prompts
  3. Plugin root contains a minimal CLAUDE.md with only non-obvious project rules (not LLM-generated boilerplate)
  4. install.sh is marked deprecated with clear migration instructions pointing to plugin-based installation
**Plans**: 2 plans

Plans:
- [x] 13-01-PLAN.md -- Create plugin manifest, CLAUDE.md, deprecate install.sh
- [ ] 13-02-PLAN.md -- Auto-trigger validation and test infrastructure

### Phase 14: Module Foundation
**Goal**: A functional Drupal module exists with Project and Task entities, CRUD routes, forms, and configuration -- buildable and installable on a fresh Drupal 10 site
**Depends on**: Phase 13
**Requirements**: SCAF-01, SCAF-02, SCAF-03, ENTY-01, ENTY-02, ENTY-03, ENTY-04, ROUTE-01, ROUTE-02, ROUTE-03, ROUTE-04, EVAL-02
**Success Criteria** (what must be TRUE):
  1. `drush en group_ai_pm -y` installs the module without errors on a Drupal 10 site with Group and AI modules present
  2. A user can create, edit, view, and delete Project and Task entities through the admin UI
  3. Project and Task list pages display all existing entities with sortable columns
  4. A settings form at `/admin/config/group_ai_pm/settings` saves and loads configuration for default statuses and AI provider
  5. Without-plugin baseline code has been generated for this phase's scope (for later comparison)
**Plans**: TBD

### Phase 15: Group & AI Integration
**Goal**: Projects and Tasks are scoped to groups with proper permission enforcement, and an AI agent can create and query entities within group context
**Depends on**: Phase 14
**Requirements**: GRP-01, GRP-02, GRP-03, GRP-04, GRP-05, ENTY-05, AI-01, AI-02, AI-03, AI-04, AI-05
**Success Criteria** (what must be TRUE):
  1. A Project or Task created within a group is only visible to members of that group (non-members get access denied)
  2. Group-scoped permissions (create/edit own/edit any/delete own/delete any) independently control access for both Project and Task entities
  3. The AI ProjectManager agent can create a task in a group, query tasks by status, and update task status -- all scoped to the current group
  4. The module installs and functions normally when the AI module is not present (AI is optional)
  5. Entity and permission design is compatible with Open Social's group type conventions
**Plans**: TBD

### Phase 16: Views, Theming & Processing
**Goal**: Users see styled project dashboards and task lists with proper caching, and background jobs handle overdue detection and notifications
**Depends on**: Phase 14, Phase 15
**Requirements**: VIEW-01, VIEW-02, VIEW-03, VIEW-04, THEME-01, THEME-02, CACHE-01, CACHE-02, CACHE-03, BG-01, BG-02, BG-03
**Success Criteria** (what must be TRUE):
  1. A group member sees a project dashboard view with task counts and status summary, filtered to their current group only
  2. Task list view supports filtering by status, priority, and assignee -- and updates immediately when a task is modified (cache invalidation)
  3. Task cards and project pages render with custom Twig templates and attached CSS library
  4. Running cron flags overdue tasks and queues notification items for processing
  5. Block plugins for project status and task list display properly on group pages with correct cache tags and group membership cache context
**Plans**: TBD

### Phase 17: Testing & Final Eval
**Goal**: The complete module passes automated tests and coding standards, and the v3.0 eval produces a per-phase delta report comparing with-plugin vs without-plugin output
**Depends on**: Phase 13, Phase 14, Phase 15, Phase 16
**Requirements**: TEST-01, TEST-02, TEST-03, EVAL-03, EVAL-04
**Success Criteria** (what must be TRUE):
  1. Kernel tests pass for entity CRUD operations and group-based access control
  2. Functional tests pass for forms, views, and route access (all green on `phpunit --group group_ai_pm`)
  3. `phpcs --standard=Drupal,DrupalPractice` reports zero errors on the entire module
  4. The module installs cleanly on a fresh Drupal 10 site and completes an end-to-end workflow (create group, add project, add task, assign, update status, verify AI agent interaction)
  5. Per-phase delta report shows with-plugin vs without-plugin quality comparison for each development phase
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 13 -> 14 -> 15 -> 16 -> 17

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1-7 | v1.0 | 26/28 | Shipped | 2026-03-07 |
| 8-12 | v2.0 | 23/23 | Shipped | 2026-03-08 |
| 13. Plugin Packaging | v3.0 | 1/2 | In progress | - |
| 14. Module Foundation | v3.0 | 0/TBD | Not started | - |
| 15. Group & AI Integration | v3.0 | 0/TBD | Not started | - |
| 16. Views, Theming & Processing | v3.0 | 0/TBD | Not started | - |
| 17. Testing & Final Eval | v3.0 | 0/TBD | Not started | - |
