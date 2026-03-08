# Project Research Summary

**Project:** Drupal Skills v3.0 -- Group AI Project Management
**Domain:** Claude Code plugin packaging + Drupal contrib module development + AI integration eval
**Researched:** 2026-03-07
**Confidence:** MEDIUM

## Executive Summary

v3.0 is a three-workstream milestone that converges plugin packaging, real-world module development, and auto-trigger evaluation into a single deliverable. The project packages the existing 14 Drupal skills as a Claude Code plugin, then proves the plugin's value by building a real Drupal contrib module (Group-based project management with AI Agents integration) where each development phase exercises specific skill domains. The module combines Group 3.x (entity grouping and access control), Drupal AI 1.2.x (provider abstraction), and AI Agents 1.2.x (tool calling framework) -- three mature but evolving contrib modules with no existing integration in the ecosystem.

The recommended approach is to package the plugin first (low effort, high leverage -- unlocks all subsequent eval phases), then build the module incrementally with each phase targeting 1-3 skill domains. The existing repo structure already matches Claude Code's plugin layout, so plugin packaging requires only adding `.claude-plugin/plugin.json` and optionally a root CLAUDE.md. The module itself should live in `modules/group_ai_pm/` to maintain separation between the plugin (the skills) and the deliverable (the module built using those skills). The critical path is: plugin packaging -> entity types -> Group integration -> AI integration. UI, theming, caching, testing, and batch processing can be parallelized once Group integration is complete.

The two highest risks are: (1) skill auto-triggering failing silently, producing 0% eval delta not because skills lack value but because they never activate -- mitigated by validating auto-trigger rates before running any phase eval; and (2) Group 3.x API terminology confusion, where Claude's training data contains more Group 2.x examples than 3.x, leading to code that uses deprecated `GroupContent`/`addContent()` calls instead of `GroupRelationship`/`addRelationship()` -- mitigated by including Group 3.x API terminology in skill references or a new dedicated skill. The AI module ecosystem is also pre-1.0 conceptually despite version numbers; all AI integration code should sit behind a service abstraction to limit blast radius when APIs change.

## Key Findings

### Recommended Stack

The stack combines three Drupal contrib ecosystems plus the Claude Code plugin system. All modules target Drupal 10.4+ (bottleneck is AI module 1.2.x requiring ^10.4) with Drupal 11 + PHP 8.3 as the recommended target. See [STACK.md](STACK.md) for full details.

**Core technologies:**
- **Drupal Core ^10.4 || ^11:** Base framework. 10.4 is the minimum due to AI module dependency.
- **Group 3.3.x:** Entity grouping framework providing projects, teams, and group-scoped permissions. v3.x uses `GroupRelationship` (renamed from `GroupContent`). The standard for arbitrary entity grouping in Drupal.
- **AI (Artificial Intelligence) 1.2.11:** Provider-agnostic AI abstraction layer. All AI Agents functionality depends on this. Requires Key module for credential storage.
- **AI Agents 1.2.3:** Agent + tool calling framework. Custom agents via config entities, custom tools via AiFunctionCall plugins. Ships with built-in agents for content types, taxonomies, views.
- **Claude Code Plugin System:** `.claude-plugin/plugin.json` manifest + `skills/` directory. Skills auto-trigger from descriptions (2% context window budget). Plugin namespace: `/drupal-skills:skill-name`.

**Critical version note:** Pin exact versions (`drupal/ai: "1.2.11"`, `drupal/ai_agents: "1.2.3"`) rather than ranges. The AI ecosystem is evolving rapidly and point releases may introduce breaking changes.

### Expected Features

Three feature domains converge. See [FEATURES.md](FEATURES.md) for full prioritization matrix and dependency graph.

**Must have (table stakes):**
- Plugin manifest (`.claude-plugin/plugin.json`) and skill description optimization for auto-triggering
- Module scaffold with Group and AI module dependencies
- Project and Task custom content entities with Group relation plugins
- Group-scoped permissions (CRUD per group type)
- Basic CRUD routes via entity route providers
- Config forms with schema
- AI provider configuration and custom AI Agent tools (AiFunctionCall plugins)

**Should have (differentiators):**
- Views integration for task lists and project dashboards
- Cache metadata with group membership cache contexts
- ProjectAssistant AI Agent plugin with task CRUD tools
- Task summarization service using provider-agnostic API
- Twig templates for task cards and project views
- Cron-based overdue task notifications

**Defer to v4+:**
- Automated test suite (kernel + functional)
- Analytics database schema (task history tracking)
- Batch AI operations from natural language
- AI-powered task creation from free text
- Gantt charts, real-time collaboration, sprint/scrum workflows, drag-and-drop kanban

**Skill coverage:** 14/14 skills (100%) are exercised by the planned feature set, with explicit phase-to-skill mapping.

### Architecture Approach

The repo IS the plugin -- no restructuring needed beyond adding `.claude-plugin/plugin.json`. The module lives in `modules/group_ai_pm/` as a self-contained Drupal module. Each development phase doubles as an auto-trigger eval: build without-plugin baseline first, then build the real module with the plugin installed. See [ARCHITECTURE.md](ARCHITECTURE.md) for full component boundaries, data flow diagrams, and build order.

**Major components:**
1. **Plugin manifest + CLAUDE.md** -- Plugin identity and cross-cutting instructions for all skills
2. **Project entity + Task entity** -- Custom content entities with base fields, form/list/access handlers
3. **GroupRelation plugins** -- `GroupProject` and `GroupTask` classes linking entities to Group 3.x
4. **AiFunctionCall tool plugins** -- CRUD tools for AI Agents (CreateProject, CreateTask, UpdateTaskStatus, QueryProjects)
5. **Project Manager agent config** -- Config entity defining the AI agent with system prompt and tool set
6. **Views + Block plugins** -- Group-scoped dashboards and project status blocks
7. **Service layer** -- ProjectManager and TaskWorkflowManager services abstracting business logic and AI provider calls

### Critical Pitfalls

See [PITFALLS.md](PITFALLS.md) for the full 11-pitfall analysis with recovery strategies.

1. **Auto-triggering fails silently** -- Skills install but never activate from natural prompts; eval shows 0% delta because skills are ignored, not because they lack value. Avoid by validating auto-trigger rates (target >80%) BEFORE running any phase eval. Use imperative directive descriptions.
2. **Eval conflates activation failure with content failure** -- Cannot distinguish "skill didn't help" from "skill didn't activate." Avoid by designing a three-tier eval (no-plugin, plugin-auto-trigger, plugin-explicit) per phase.
3. **Group 3.x API terminology mismatch** -- Claude generates Group 2.x code (`GroupContent`, `addContent()`) that fails at runtime on Group 3.x. Avoid by including a terminology mapping reference and grepping for 2.x terms in all generated code.
4. **Plugin directory structure silently ignored** -- Wrong skill locations produce zero errors and zero loaded skills. Avoid by testing with `claude --debug` and verifying all 14 skills appear in `/context`.
5. **Drupal AI API instability** -- AI module is evolving rapidly despite stable version numbers. Avoid by wrapping all AI calls in a service abstraction layer and making AI integration an optional enhancement, not a hard dependency for core PM features.

## Implications for Roadmap

Based on dependency analysis, skill coverage mapping, and pitfall prevention, the following phase structure is recommended. The critical path is Phases 1-2-4-5; other phases can be reordered within dependency constraints.

### Phase 1: Plugin Packaging
**Rationale:** Every subsequent phase depends on skills being installed as a plugin that auto-triggers. This is the prerequisite for the entire v3.0 eval methodology. Low effort (repo structure already correct), high leverage.
**Delivers:** Installable Claude Code plugin with all 14 skills, plugin.json manifest, optional CLAUDE.md, install.sh migration path for existing users
**Addresses:** Plugin packaging features (P1), install.sh dual-install prevention
**Avoids:** Pitfall #4 (silent skill loading failure), Pitfall #6 (install.sh migration)
**Skills exercised:** None directly -- this is infrastructure

### Phase 2: Module Scaffold + Entity Types
**Rationale:** Core entity types are the foundation for everything else. Routes, forms, views, AI tools, and tests all depend on Project and Task entities existing. Must come before any UI or integration work.
**Delivers:** `modules/group_ai_pm/` with .info.yml, Project entity, Task entity, base fields, form/list/access handlers
**Addresses:** Module scaffold, entity creation, config schema (P1 features)
**Avoids:** Pitfall #3 (Group API terminology -- entities defined with correct 3.x terms from day one)
**Skills exercised:** drupal-module-scaffold, drupal-entities-fields, drupal-config-storage

### Phase 3: Routing + Forms
**Rationale:** With entities defined, the module needs CRUD routes and config forms to be functional. This is a natural next step before Group integration adds the access control layer.
**Delivers:** Entity CRUD routes via route providers, custom dashboard controller, entity form classes, settings form with config schema
**Addresses:** Basic routes, config forms (P1 features)
**Skills exercised:** drupal-routing-controllers, drupal-forms-api

### Phase 4: Group Integration
**Rationale:** This is the highest-complexity and highest-risk phase. Group relation plugins, group-scoped permissions, and group-aware access control are the module's core value proposition. Must come before AI integration (agents need group context).
**Delivers:** GroupProject and GroupTask relation plugins, GroupRelationshipType configs, group-scoped CRUD permissions, group membership roles
**Addresses:** GroupRelation plugins, group-scoped permissions (P1 features)
**Avoids:** Pitfall #3 (Group 3.x API), Pitfall #7 (Group access overrides), Pitfall #9 (REST group context)
**Skills exercised:** drupal-plugins-blocks, drupal-entities-fields, drupal-access-security

### Phase 5: AI Agents Integration
**Rationale:** Depends on entities (Phase 2) and group context (Phase 4). AI tools need to create/query entities within groups. This is the novel integration that no existing Drupal module provides.
**Delivers:** AiFunctionCall tool plugins (CRUD operations), Project Manager agent config entity, AI provider configuration, task summarization service
**Addresses:** AI Agent plugin, custom tools, AI provider config (P2 features)
**Avoids:** Pitfall #5 (AI API instability -- service abstraction layer)
**Skills exercised:** drupal-plugins-blocks, drupal-routing-controllers

### Phase 6: Views + Blocks
**Rationale:** With entities, group context, and AI tools in place, the module needs user-facing displays. Views integration and block plugins provide dashboard capabilities.
**Delivers:** Task list views, project dashboard views, group-scoped Views filters, status/task list/AI assistant block plugins
**Addresses:** Views integration, dashboard blocks (P2 features)
**Skills exercised:** drupal-views-dev, drupal-plugins-blocks

### Phase 7: Theming + Caching
**Rationale:** Theming requires routes and blocks to exist (something to theme). Caching is tightly coupled to render output. Combining them ensures cache metadata is applied to all themed output from the start.
**Delivers:** Twig templates for task cards and project views, .libraries.yml, cache tags on entities, group membership cache contexts, block caching
**Addresses:** Templates/theming, cache metadata (P2 features)
**Skills exercised:** drupal-theming, drupal-caching

### Phase 8: Background Processing
**Rationale:** Cron and queue features are independent of the UI layer but need entities and optionally AI integration.
**Delivers:** Cron hook for overdue task detection, queue worker for notifications, optional AI batch operations
**Addresses:** Cron notifications, batch AI operations (P3 features)
**Skills exercised:** drupal-batch-queue-cron

### Phase 9: Testing + Database
**Rationale:** Testing comes last because it validates all other features. Database schema for analytics is a P3 feature that can be combined here.
**Delivers:** Kernel tests (entity CRUD, Group relations, access), functional tests (forms, views, AI tools), task history tracking table
**Addresses:** Testing suite, analytics schema (P3 features)
**Skills exercised:** drupal-testing, drupal-database-api

### Phase 10: Polish + Integration Eval
**Rationale:** Final phase validates the full module works end-to-end and produces the v3.0 delta report.
**Delivers:** phpcs compliance pass, full module install test, end-to-end workflow verification, final per-phase delta report
**Addresses:** Cross-cutting quality, coding-standards (all phases)
**Skills exercised:** drupal-coding-standards (cross-cutting)

### Phase Ordering Rationale

- **Plugin first (Phase 1):** All eval methodology depends on plugin being functional. Validate auto-triggering before investing in module development.
- **Entities before UI (Phases 2-3 before 6-7):** Routes, Views, templates, and AI tools all depend on entity types being defined.
- **Group integration before AI (Phase 4 before 5):** AI agents need group context to scope their operations. Group permissions determine what the agent can do.
- **Theming + caching together (Phase 7):** Cache metadata must be applied at the point of rendering. Doing them separately risks missing cache tags on themed output.
- **Testing last (Phase 9):** Cannot write tests for features that do not exist yet. Testing validates the entire module.

### Research Flags

Phases likely needing `/gsd:research-phase` during planning:
- **Phase 1 (Plugin Packaging):** Auto-trigger behavior needs empirical validation. Research whether `--plugin-dir` works with `-p` mode for eval compatibility.
- **Phase 4 (Group Integration):** Group 3.x relation plugin API is complex and under-documented. Need to research GroupRelationBase, handler services, and permission calculation at implementation time.
- **Phase 5 (AI Agents Integration):** AiFunctionCall plugin API is documented as "WIP." Need to research actual tool plugin implementation from AI Agents source code, not just docs.

Phases with standard patterns (skip research-phase):
- **Phase 2 (Entities):** Well-documented Drupal entity API. Existing drupal-entities-fields skill covers this thoroughly.
- **Phase 3 (Routing + Forms):** Standard Drupal patterns. Existing skills cover this.
- **Phase 6 (Views + Blocks):** Standard Views integration and Block plugins. Existing skills cover this.
- **Phase 7 (Theming + Caching):** Standard Drupal patterns. These are among the highest-performing skills from v2.0.
- **Phase 8 (Batch/Queue):** Standard cron + queue worker patterns. Existing skill covers this.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All module versions verified on drupal.org. Claude Code plugin format verified from official docs. Version compatibility matrix confirmed. |
| Features | MEDIUM | Feature set is well-defined but prioritization depends on eval methodology decisions not yet validated. 100% skill coverage is achievable but integration patterns between Group + AI are novel. |
| Architecture | MEDIUM | Plugin packaging is straightforward (repo already matches). Module architecture follows standard Drupal patterns. Group 3.x + AI Agents integration is uncharted territory -- no existing module combines these. |
| Pitfalls | HIGH | Pitfalls grounded in v2.0 empirical data (headless vs agent delta), official docs (plugin structure), and verified API changes (Group 3.x renames). Recovery strategies are concrete. |

**Overall confidence:** MEDIUM -- The individual components (plugin system, Group module, AI module) are well-understood, but their integration is novel. The eval methodology shift from v2.0 (forced skill activation) to v3.0 (organic auto-triggering) introduces new variables that need empirical validation.

### Gaps to Address

- **Auto-trigger empirical data:** No data yet on how reliably skills auto-trigger from a plugin install with natural prompts. Current descriptions may need tuning. Must validate in Phase 1 before proceeding.
- **`--plugin-dir` + `-p` compatibility:** Unknown whether headless mode supports plugin loading. This determines whether the v3.0 eval can use the validated v2.0 headless pipeline or needs a fundamentally different approach.
- **Group 3.x relation plugin implementation details:** The handler service pattern (replacing annotation methods) is documented in change records but lacks tutorial-quality examples. Phase 4 will need source code research against `modules/contrib/group/src/`.
- **AiFunctionCall plugin contract:** AI Agents docs are marked WIP. The `execute()` and `getArguments()` method signatures need validation against actual AI Agents source code, not just the QED42 tutorial.
- **Missing Group-specific skill:** None of the 14 existing skills cover Group module patterns. Phases touching Group API (4, 7, 8) may show 0% delta because no skill provides Group-specific knowledge. Consider creating a `drupal-group-integration` skill or adding Group API references to existing skills.
- **Three-tier eval design validation:** The proposed three-tier eval (baseline, auto-trigger, explicit) triples eval time per phase. Need to determine if this is feasible or if a simpler two-tier design suffices.

## Sources

### Primary (HIGH confidence)
- [Group module project page](https://www.drupal.org/project/group) -- v3.3.5, entity architecture, compatibility
- [AI module project page](https://www.drupal.org/project/ai) -- v1.2.11, provider abstraction, sub-modules
- [AI Agents project page](https://www.drupal.org/project/ai_agents) -- v1.2.3, agent framework, built-in agents
- [Claude Code Plugins docs](https://code.claude.com/docs/en/plugins) -- Plugin structure, manifest, quickstart
- [Claude Code Plugins reference](https://code.claude.com/docs/en/plugins-reference) -- Full plugin.json schema, directory structure
- [Claude Code Skills docs](https://code.claude.com/docs/en/skills) -- Auto-triggering, description matching, frontmatter
- [Anthropic skills repo](https://github.com/anthropics/skills) -- skill-creator anatomy reference
- [Group 3.x API changes](https://www.drupal.org/node/3292844) -- addContent -> addRelationship rename
- v2.0 empirical eval data (MEMORY.md) -- headless pipeline validated, tier classifications

### Secondary (MEDIUM confidence)
- [QED42 AI Agents guide](https://www.qed42.com/insights/exploring-drupals-ai-agents-a-practical-guide-for-site-builders) -- AiFunctionCall plugin pattern
- [AI Agents developer docs](https://project.pages.drupalcode.org/ai_agents) -- Agent architecture, tool calling
- [AI module documentation](https://project.pages.drupalcode.org/ai/1.2.x/) -- Provider API, operation types
- [Skill auto-trigger research](https://mikhail.io/2025/10/claude-code-skills/) -- How available_skills list is built
- [Anthropic skill authoring best practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices) -- Description writing guidelines
- [Group permissions documentation](https://www.drupal.org/docs/contributed-modules/group/the-permission-layers-explained) -- PBAC via flexible_permissions

### Tertiary (LOW confidence)
- [Skills activation community workarounds](https://dev.to/oluwawunmiadesewa/claude-code-skills-not-triggering-2-fixes-for-100-activation-3b57) -- UserPromptSubmit hook pattern, unverified activation rates
- [Document AI module architecture issue](https://www.drupal.org/project/ai/issues/3566997) -- Architecture docs in progress, not yet available

---
*Research completed: 2026-03-07*
*Supersedes v2.0 SUMMARY.md (2026-03-07)*
*Ready for roadmap: yes*
