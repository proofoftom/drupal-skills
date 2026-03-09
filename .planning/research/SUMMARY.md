# Project Research Summary

**Project:** Drupal Skills v5.0 -- AI Integration, Eval Tooling & Analytics
**Domain:** Extending a Drupal 10 module (group_ai_pm) with AI-powered task management, a 15th skill (Drush), an Opus-class eval-author agent, skill gap fixes, and task history analytics
**Researched:** 2026-03-09
**Confidence:** MEDIUM-HIGH

## Executive Summary

v5.0 is fundamentally different from v3.0 and v4.0. Those milestones added new module code and frontend features that were measured by the eval pipeline. v5.0 adds capabilities across every layer simultaneously -- a new skill, a new agent, AI module integration, database schema for analytics, AND skill gap fixes -- but requires zero new dependencies. The existing stack (Drupal 10.6, Group 3.3, AI 1.2.11, AI Agents 1.3.0-beta2, Vue 3, Drush 13.7.1) covers everything. This is the key finding from stack research: v5.0 is about capabilities, not infrastructure.

The recommended approach is to build the tooling layer first (Drush skill + eval-author agent + skill gap fixes) before any module features. The eval-author agent is the highest-leverage deliverable because it eliminates the 30-minute-per-phase manual bottleneck of designing three-tier assertions. Once the tooling is solid, AI features (NL task creation, assignment suggestions, batch operations) follow the established AiFunctionCall plugin pattern with a new AiTaskService layer. Task history analytics uses a custom table with composite indexes for time-series queries, exposed to Views and surfaced on the dashboard.

The top risks are eval quality degradation and AI exception handling. The eval-author agent is novel -- no precedent in this project for automated assertion generation -- and the dominant failure mode is tautological assertions that test file existence instead of non-obvious skill patterns (Pitfall #2). This must be addressed with strict assertion category distribution rules (60% differentiating, 20% wiring, max 20% structural) and gold-standard examples from Phase 18. For AI features, the three-catch pattern for queue workers (SuspendQueueException, AiRateLimitException + RequeueException, generic Exception) is critical -- the standard two-catch pattern permanently deletes rate-limited items. Both risks are well-understood and have concrete prevention strategies documented in PITFALLS.md.

## Key Findings

### Recommended Stack

No new composer or npm packages are needed. All v5.0 features use existing dependencies or Drupal core APIs. The only new artifacts are code files (PHP classes, YAML routes, Vue components), a skill file, and an agent definition.

**Core technologies (all existing):**
- **Drush 13.7.1**: Already installed. Skill teaches `src/Drush/Commands/` directory, `AutowireTrait` for DI, `#[AsCommand]` PHP attribute -- all patterns Haiku gets wrong without guidance.
- **AI module 1.2.11 + AI Agents 1.3.0-beta2**: Already installed. Provider-agnostic `ai.provider` service for chat completions. AiFunctionCallBase plugin pattern for tool registration. Decision: stay on 1.2.11, not upgrade to 1.3.0-rc2 (RC not stable, no needed features).
- **Database API (Drupal core)**: `hook_schema()` for analytics table, `hook_entity_presave()` for history recording, `hook_views_data()` for Views integration. No entity overhead needed for append-only log data.
- **Claude Code subagent infrastructure**: Existing pattern from eval-grader.md. The eval-author uses `model: opus` for deep reasoning about assertion design.

### Expected Features

**Must have (table stakes):**
- Drush skill -- 15th skill targeting Drush 13+ command creation patterns (file location, AutowireTrait, `#[AsCommand]`). Fills a verified gap in the collection.
- Eval-author agent -- Opus subagent that designs three-tier assertions. Outputs evals.json + runtime assertions per phase. The multiplier for all future eval work.
- Skill gap fixes -- entities-fields `bundle_of` coverage, caching `lazy_builder` hardening, forms-api `#ajax` content. Each is LOW complexity, directly improves eval pass rates.

**Should have (differentiators):**
- CreateTaskTool -- AiFunctionCall plugin for NL task creation. Most user-visible AI feature. Follows existing CreateProjectTool pattern exactly.
- Task history analytics table -- `group_ai_pm_task_history` with composite indexes. Foundation for cycle time, throughput, bottleneck metrics.
- BatchUpdateTool -- Queue-based bulk AI operations with dry-run mode and per-item error reporting.
- Cross-cutting eval pass -- Full pipeline validation that eval-author + Drush skill + gap fixes improve outcomes.

**Defer (v6+):**
- SuggestAssigneeTool -- Requires sufficient history data to be useful. HIGH complexity for LOW eval value.
- Real-time AI chat in Kanban -- Duplicates AI Agents module's existing chatbot; requires WebSocket infrastructure Drupal lacks.
- Full burndown charts -- Requires Sprint entity type, story points, capacity planning. Massive scope, use Burndown contrib module if needed.
- Plugin-based analytics engine -- Over-architecture for 3-4 hardcoded metrics.

### Architecture Approach

v5.0 touches every layer (skills, agents, backend, eval tooling) with cross-cutting dependencies. The key architectural decisions are: (1) a new AiTaskService layer separates AI logic from controllers, making it reusable by both REST endpoints and AiFunctionCall plugins; (2) AI module is an optional dependency using `@?ai.provider` service injection and the existing `group_ai_pm_ai` submodule for hard dependencies; (3) task history uses a custom table via `hook_schema()` (not a content entity) because it is append-only log data needing aggregate queries, not user-editable content.

**NOTE: STACK.md and ARCHITECTURE.md disagree on history storage.** STACK.md recommends a custom table via `hook_schema()`. ARCHITECTURE.md recommends a TaskHistory content entity. **Recommendation: Use `hook_schema()` custom table.** Rationale: history records are append-only, never edited, never accessed individually through UI, and need efficient aggregate queries (COUNT/GROUP BY/date ranges). Entity overhead (access control, CRUD forms, revisions) adds zero value and measurable cost. The `hook_views_data()` approach for Views integration is well-documented and works identically for custom tables. This aligns with the database-api skill's guidance on custom tables for "logs, statistics, integration data."

**Major components:**
1. **drupal-drush skill** (`skills/drupal-drush/SKILL.md`) -- Teaches Drush 13+ command creation. Cross-references batch-queue-cron to prevent queue pattern conflicts.
2. **eval-author agent** (`.claude/agents/eval-author.md`) -- Opus subagent that reads skills + previous results to design three-tier assertions. Read-only except for JSON output files.
3. **AiTaskService** (`src/Service/AiTaskService.php`) -- Central AI logic layer. Parses NL text, suggests assignees, orchestrates batch operations. Injected by controllers and AI tools alike.
4. **AI REST endpoints** (`src/Controller/AiTaskController.php`) -- POST ai-create, GET suggest-assignee, POST ai-batch. Segregated from existing TaskApiController.
5. **AiFunctionCall plugins** (4 new in `group_ai_pm_ai/`) -- CreateTaskTool, UpdateTaskStatusTool, SuggestAssigneeTool, BatchUpdateTool. All follow existing plugin pattern.
6. **Task history table** (`hook_schema()` in `.install`) -- Composite-indexed custom table with `hook_entity_presave()` recording and `hook_views_data()` for Views.
7. **Analytics endpoint** (`src/Controller/AnalyticsController.php`) -- Aggregated metrics via Database API queries. CacheableJsonResponse with tag-based invalidation.

### Critical Pitfalls

1. **Drush skill teaches deprecated patterns** -- Drush 12+ requires `src/Drush/Commands/` (not `src/Commands/`), AutowireTrait (not drush.services.yml), and `#[AsCommand]` (not `@command` annotations). The skill MUST teach modern patterns as the primary approach with CRITICAL NEVER callouts. Prevention: test command discovery as a runtime assertion (`drush list | grep module_name`).

2. **Eval-author generates tautological assertions** -- The #1 failure mode of automated assertion generation. LLMs gravitate toward testing file existence and boilerplate, which pass 100% for both with/without skill runs, producing 0% delta. Prevention: enforce 60% differentiating / 20% wiring / max 20% structural assertion distribution. Provide Phase 18 evals as gold-standard examples. Include a tautology check pass.

3. **AI rate limit exceptions swallowed by queue workers** -- The standard two-catch pattern (SuspendQueueException + generic Exception) permanently deletes rate-limited items. AiRateLimitException does NOT extend SuspendQueueException. Prevention: use a three-catch pattern with RequeueException for transient failures.

4. **Missing `hook_update_N()` for schema changes** -- New base fields on Task entity or new analytics table work on fresh install but crash existing sites. Prevention: ALWAYS pair `baseFieldDefinitions()` changes with `hook_update_N()` calling `installFieldStorageDefinition()`. ALWAYS pair `hook_schema()` table additions with `hook_update_N()` calling `createTable()`.

5. **Analytics table missing composite indexes** -- Individual column indexes are insufficient for time-series queries combining entity filtering and date ordering. Prevention: design composite indexes (`task_id + timestamp`, `field_name + timestamp`, `uid + timestamp`) based on actual query patterns before creating the table.

## Implications for Roadmap

Based on research, suggested 6-phase structure starting from Phase 22:

### Phase 22: Drush Skill + Eval-Author Agent
**Rationale:** Pure tooling with zero module dependencies. The eval-author agent needs the Drush skill for runtime assertion quality, and every subsequent phase benefits from automated eval design. Building these first provides the highest leverage.
**Delivers:** 15th skill file (drupal-drush), eval-author subagent definition (.claude/agents/eval-author.md), Drush skill eval (evals.json)
**Addresses:** Drush skill (P1), eval-author agent (P1)
**Avoids:** Pitfall #1 (deprecated Drush patterns) by verifying against Drush 13.x docs; Pitfall #2 (tautological assertions) by baking Phase 18 gold-standard examples into agent prompt; Pitfall #8 (conflicting queue patterns) by adding explicit cross-references between Drush and batch-queue-cron skills

### Phase 23: Skill Gap Fixes + Eval-Author Validation
**Rationale:** Skill patches change eval baselines. Do them before measuring AI features, or deltas will be muddied. Also validates the eval-author agent on known skill territory.
**Delivers:** Patched entities-fields (bundle_of), caching (lazy_builder), and forms-api (#ajax) skills. Validated eval-author output on existing skill evals.
**Addresses:** Skill gap fixes (P1), eval-author validation
**Avoids:** Pitfall #6 (missing hook_update_N for new base fields) by adding the update hook pattern to entities-fields skill; Pitfall #15 (missing #ajax content) by adding concrete #ajax patterns to forms-api skill; Pitfall #4 (no runtime assertions) by validating eval-author produces runtime checks

### Phase 24: AI Task Service + NL Task Creation
**Rationale:** AiTaskService is the foundation all AI features depend on. NL task creation is the most user-visible feature and follows the established AiFunctionCall plugin pattern. Service layer must exist before tools or controllers can consume it.
**Delivers:** AiTaskService (with optional AI dependency), AiTaskController with POST ai-create endpoint, CreateTaskTool AiFunctionCall plugin, Vue AiCreateDialog component
**Addresses:** CreateTaskTool (P2), AI service layer
**Avoids:** Pitfall #7 (provider not validated) by checking configuration before AI calls; Pitfall #10 (plugin annotation namespace) by following existing plugin patterns exactly

### Phase 25: Batch AI Operations + Agent Tools
**Rationale:** Builds on Phase 24's AiTaskService. Batch operations exercise the queue + AI integration pattern that is unique to v5.0. Remaining AI tools (UpdateTaskStatusTool, SuggestAssigneeTool, BatchUpdateTool) round out the AI agent capabilities.
**Delivers:** 3 additional AiFunctionCall plugins, batch AI endpoint with queue-based processing, Vue BatchActionBar component, dry-run mode
**Addresses:** BatchUpdateTool (P2), remaining AI tools (P3)
**Avoids:** Pitfall #3 (rate limit exceptions swallowed) by implementing three-catch pattern; Pitfall #14 (PHP timeout in batch) by using Queue API instead of Batch API for AI operations

### Phase 26: Task History Analytics
**Rationale:** History recording via `hook_entity_presave()` captures changes from both manual and AI-driven task mutations. Placing this after AI features ensures history captures the full range of operations.
**Delivers:** `group_ai_pm_task_history` custom table with composite indexes, `hook_entity_presave()` recording, `hook_views_data()` integration, analytics REST endpoint, dashboard metrics integration
**Addresses:** Task history table (P2), analytics dashboard (P3), Views integration (P3)
**Avoids:** Pitfall #5 (missing indexes) by designing composite indexes upfront; Pitfall #13 (missing hook_update_N for table) by pairing hook_schema with update hook

### Phase 27: Cross-Cutting Eval + Final Report
**Rationale:** Measures the cumulative effect of all v5.0 changes. The eval-author generates comprehensive assertions, and the full A/B pipeline validates that the Drush skill, gap fixes, and AI features actually improve code generation quality.
**Delivers:** v5.0 aggregate eval results, final report with delta measurements per phase, MEMORY.md updates
**Addresses:** Cross-cutting eval pass (P2), milestone validation

### Phase Ordering Rationale

- **Tooling before features** (Phases 22-23 before 24-26): The eval-author agent and Drush skill are force-multipliers. Every hour invested in them saves 30 minutes of manual eval design per subsequent phase.
- **Skill fixes before measurements** (Phase 23 before 24): Patching skills changes baselines. Measuring AI feature deltas against un-patched skills would confound results.
- **Service layer before consumers** (Phase 24 before 25): AiTaskService must exist before controllers, tools, and batch workers can inject it.
- **AI features before analytics** (Phases 24-25 before 26): History recording captures AI-driven mutations. Building analytics first would miss AI operations.
- **Eval last** (Phase 27): Cross-cutting measurement requires all features complete.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 22 (Drush Skill):** Verify `#[AsCommand]` vs `#[CLI\Command]` deprecation status for Drush 13.7 specifically. ARCHITECTURE.md and STACK.md provide slightly different recommendations on the preferred base class (`Command` vs `DrushCommands`). Needs confirmation against Drush 13.7.1 source.
- **Phase 24 (AI Task Service):** The `ai.provider` service name needs verification -- it may be `plugin.manager.ai_provider` in the actual AI module. The `@?` optional injection syntax is standard Symfony but verify Drupal 10.6 wires it correctly for plugin managers.
- **Phase 25 (Batch AI Operations):** AiRateLimitException class hierarchy needs verification. PITFALLS.md notes it does NOT extend SuspendQueueException but the exact class path and catch behavior should be tested against AI 1.2.11 before coding.

Phases with standard patterns (skip deeper research):
- **Phase 23 (Skill Gap Fixes):** Pure SKILL.md editing. Patterns are documented in existing skills and Sipos book.
- **Phase 26 (Task History Analytics):** `hook_schema()`, `hook_entity_presave()`, and `hook_views_data()` are thoroughly documented in official Drupal docs and covered by existing skills.
- **Phase 27 (Cross-Cutting Eval):** Runs the existing eval pipeline with the new eval-author agent. No new research needed.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Zero new dependencies. All versions verified in composer.lock. Decision to stay on AI 1.2.11 is sound. |
| Features | MEDIUM | Drush skill and skill gaps are well-defined. Eval-author agent is novel (no precedent for automated assertion generation in this project). AI features follow established plugin patterns but AI module API docs are incomplete on error handling. |
| Architecture | MEDIUM | AiTaskService pattern is standard. STACK.md and ARCHITECTURE.md conflict on history storage (custom table vs entity) -- resolved in favor of custom table. Optional AI dependency via `@?` needs runtime verification. |
| Pitfalls | HIGH | 15 pitfalls identified with concrete prevention strategies. Critical pitfalls (#1-#6) have specific code patterns for avoidance. Recovery strategies documented for each. |

**Overall confidence:** MEDIUM-HIGH

### Gaps to Address

- **ai.provider service name**: ARCHITECTURE.md flags uncertainty about whether the service is `ai.provider` or `plugin.manager.ai_provider`. Must verify against installed AI 1.2.11 before Phase 24 planning.
- **AsCommand vs CLI\Command**: Both STACK.md and ARCHITECTURE.md recommend `#[AsCommand]` but STACK.md also shows `#[CLI\Command]` examples. Need to verify which is current for Drush 13.7.1 and whether both work.
- **Eval-author assertion quality**: No empirical data exists for automated assertion generation in this project. Phase 22 is inherently experimental. Build a validation loop (Phase 23) to catch quality issues before relying on the agent for all subsequent phases.
- **AiRateLimitException hierarchy**: The exact exception class path and inheritance in AI 1.2.11 needs verification before implementing the three-catch pattern in Phase 25.
- **forms-api #ajax content scope**: The skill has room (~100 lines available) but adding comprehensive AJAX coverage risks pushing past the 500-line budget. Scope to the most impactful patterns only (callback, wrapper, AjaxResponse).

## Sources

### Primary (HIGH confidence)
- [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/) -- File location, PHP attributes, AutowireTrait, deprecated patterns
- [Drush 13.x Dependency Injection](https://www.drush.org/13.x/dependency-injection/) -- AutowireTrait, constructor injection
- [Anthropic: Demystifying Evals for AI Agents](https://www.anthropic.com/engineering/demystifying-evals-for-ai-agents) -- Assertion design, grader patterns, rigid step-checking anti-pattern
- [Drupal Update API](https://www.drupal.org/docs/drupal-apis/update-api/updating-database-schema-andor-data-in-drupal) -- hook_update_N(), installFieldStorageDefinition()
- [Drupal AI Module Project Page](https://www.drupal.org/project/ai) -- v1.2.11 stable, provider API, submodule list
- v4.0 Phase 18 Evals -- Gold standard: 17 assertions, +23.3% delta (project-specific empirical data)
- v4.0 Phase 20 Results -- forms-api #ajax gap identified (project-specific)

### Secondary (MEDIUM confidence)
- [AI Agents Documentation](https://project.pages.drupalcode.org/ai_agents) -- Plugin architecture, AiFunctionCallBase
- [Claude Code Sub-Agent Docs](https://code.claude.com/docs/en/sub-agents) -- Frontmatter spec, model options
- [Drupalize.Me: Expose Custom Table to Views](https://drupalize.me/tutorial/expose-custom-database-table-views) -- hook_views_data() patterns
- [Drupal AI Rate Limits Issue](https://www.drupal.org/project/ai/issues/3492086) -- AiRateLimitException behavior
- [Drupal AI Error Handling Issue](https://www.drupal.org/project/ai/issues/3499597) -- Exception hierarchy

### Tertiary (LOW confidence)
- [Sana Labs: AI Task Managers](https://sanalabs.com/agents-blog/ai-task-managers-to-boost-productivity) -- Market context only
- [ScienceDirect: ML for Task Allocation](https://www.sciencedirect.com/science/article/pii/S2405844024159579) -- Academic precedent for AI assignment

---
*Research completed: 2026-03-09*
*Supersedes v4.0 SUMMARY.md (2026-03-08)*
*Ready for roadmap: yes*
