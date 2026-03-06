# Roadmap: Drupal Skills

## Overview

This project delivers 13 Claude Code skills for Drupal module development in 5 phases following dependency order. Foundational skills (module scaffold, routing, entities) come first because every subsequent skill cross-references them. Each wave phase builds skills that depend on prior waves, with cross-cutting quality standards (SKIL-01 through SKIL-07) verified within every wave. The final phase optimizes trigger descriptions holistically and packages everything for distribution.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Foundations** - Build 3 foundational skills (scaffold, routing, entities) and establish skill template patterns (completed 2026-03-05)
- [x] **Phase 2: Core Workflow** - Build 4 daily-use skills (forms, blocks, config, access) that depend on foundations (completed 2026-03-06)
- [x] **Phase 3: Presentation and Quality** - Build 4 skills (theming, caching, testing, database) covering output and verification (completed 2026-03-06)
- [x] **Phase 4: Specialized Patterns** - Build 2 advanced skills (views, batch/queue/cron) for less-common workflows (completed 2026-03-06)
- [x] **Phase 5: Eval, Optimization, and Packaging** - Optimize trigger descriptions holistically, run multi-skill eval, package for distribution (completed 2026-03-06)
- [ ] **Phase 6: Live Eval Loop** - Run 4 representative skills through real functional eval with Sonnet 4.6 subagents against live Drupal instances

## Phase Details

### Phase 1: Foundations
**Goal**: Developers can use Claude to scaffold Drupal modules, define routes with controllers, and build content/config entities -- the three capabilities every other skill depends on
**Depends on**: Nothing (first phase)
**Requirements**: SKIL-01, SKIL-02, SKIL-03, SKIL-04, SKIL-05, SKIL-06, SKIL-07, FOUN-01, FOUN-02, FOUN-03
**Success Criteria** (what must be TRUE):
  1. drupal-module-scaffold skill generates correct .info.yml, PSR-4 namespaces, and .module files when Claude is asked to create a module
  2. drupal-routing-controllers skill produces complete route definitions with paired controllers, services, and DI patterns when Claude is asked to add a page or endpoint
  3. drupal-entities-fields skill generates content and config entity classes with all required annotations/attributes and base field definitions when Claude is asked to create an entity
  4. All three skills follow SKILL.md anatomy (frontmatter, sub-500-line body, references/ subdirectory), use decision-guide format, include wrong-way callouts, produce complete file ecosystems (PHP + YAML), show D10/D11 dual syntax, work independently when installed, and include cross-references
  5. Each skill passes skill-creator eval loop showing measurable improvement over Claude's baseline
**Plans:** 3/3 plans complete

Plans:
- [x] 01-01-PLAN.md -- Create skills directory structure and drupal-module-scaffold skill (establishes template pattern)
- [x] 01-02-PLAN.md -- Create drupal-routing-controllers skill with menus reference
- [x] 01-03-PLAN.md -- Create drupal-entities-fields skill with files-images reference

### Phase 2: Core Workflow
**Goal**: Developers can use Claude to build forms, block plugins, config/state management, and access control -- the daily-use patterns that compose with foundational skills
**Depends on**: Phase 1
**Requirements**: CORE-01, CORE-02, CORE-03, CORE-04
**Success Criteria** (what must be TRUE):
  1. drupal-forms-api skill produces correct Form API lifecycle code (buildForm, validateForm, submitForm) with proper form altering patterns when Claude is asked to create or modify forms
  2. drupal-plugins-blocks skill generates block plugins with correct annotations/attributes and custom plugin type boilerplate when Claude is asked to create blocks or plugins
  3. drupal-config-storage skill produces correct Config API, State API, and TempStore patterns with config schemas when Claude is asked to store or manage configuration
  4. drupal-access-security skill generates correct permission definitions, access handlers, route access checks, and CSRF/XSS prevention when Claude is asked about access control
  5. All four skills satisfy SKIL-01 through SKIL-07 quality standards and pass skill-creator eval
**Plans:** 4/4 plans complete

Plans:
- [x] 02-01-PLAN.md -- Create drupal-forms-api skill (Form API lifecycle, form altering, ConfigFormBase)
- [x] 02-02-PLAN.md -- Create drupal-config-storage skill with i18n reference (Config API, State API, TempStore)
- [x] 02-03-PLAN.md -- Create drupal-plugins-blocks skill (block plugins, custom plugin types, D10/D11 dual syntax)
- [x] 02-04-PLAN.md -- Create drupal-access-security skill (permissions, access handlers, CSRF/XSS prevention)

### Phase 3: Presentation and Quality
**Goal**: Developers can use Claude to build themed output, apply caching correctly, write tests, and use the database API -- completing the full module development workflow
**Depends on**: Phase 2
**Requirements**: PRES-01, PRES-02, PRES-03, PRES-04
**Success Criteria** (what must be TRUE):
  1. drupal-theming skill produces correct render arrays, Twig templates, theme hooks, and preprocess functions when Claude is asked to theme output
  2. drupal-caching skill generates correct cache tags, contexts, max-age, and lazy builder patterns when Claude is asked about caching or produces render arrays
  3. drupal-testing skill produces correct PHPUnit test classes (unit, kernel, functional, browser) with appropriate base classes and assertions when Claude is asked to write tests
  4. drupal-database-api skill generates correct dynamic queries, schema definitions, and database abstraction patterns when Claude is asked to work with the database directly
  5. All four skills satisfy SKIL-01 through SKIL-07 quality standards and pass skill-creator eval
**Plans:** 4/4 plans complete

Plans:
- [x] 03-01-PLAN.md -- Create drupal-theming skill with js-ajax reference (render arrays, Twig templates, hook_theme, preprocess, libraries)
- [x] 03-02-PLAN.md -- Create drupal-caching skill (cache tags, contexts, max-age, lazy builders, two-tier page caching)
- [x] 03-03-PLAN.md -- Create drupal-database-api skill (Schema API, dynamic/static queries, update hooks)
- [x] 03-04-PLAN.md -- Create drupal-testing skill (Unit, Kernel, Functional, FunctionalJavascript test types)

### Phase 4: Specialized Patterns
**Goal**: Developers can use Claude to build Views integrations and batch/queue/cron workflows -- completing the full 13-skill coverage of the book
**Depends on**: Phase 3
**Requirements**: SPEC-01, SPEC-02
**Success Criteria** (what must be TRUE):
  1. drupal-views-dev skill produces correct hook_views_data implementations and Views field/filter/sort plugins when Claude is asked to integrate with Views
  2. drupal-batch-queue-cron skill generates correct Batch API operations, queue worker plugins, and cron hook implementations when Claude is asked about background processing
  3. Both skills satisfy SKIL-01 through SKIL-07 quality standards and pass skill-creator eval
**Plans:** 2/2 plans complete

Plans:
- [x] 04-01-PLAN.md -- Create drupal-views-dev skill (hook_views_data, Views field/filter/sort plugins, EntityViewsData)
- [x] 04-02-PLAN.md -- Create drupal-batch-queue-cron skill with logging-mail-tokens reference (Batch API, QueueWorker, hook_cron, Lock API)

### Phase 5: Eval, Optimization, and Packaging
**Goal**: All 13 skills work coherently together with optimized trigger descriptions and are packaged for installation and distribution
**Depends on**: Phase 4
**Requirements**: EVAL-01, EVAL-02, EVAL-03, EVAL-04, PACK-01, PACK-02, PACK-03
**Success Criteria** (what must be TRUE):
  1. Each skill's trigger description is tuned so that simple Drupal prompts activate the right skill(s) without loading irrelevant ones
  2. Multi-skill prompts (e.g., "create a module with a custom entity, form, and themed output") produce coherent output drawing from multiple skills simultaneously
  3. Eval prompts grounded in os-knowledge-garden project tasks show measurable improvement for all 13 skills
  4. skills/ folder contains all 13 complete skill directories and install.sh copies them to ~/.claude/skills/ correctly
  5. Repository README documents the full skill inventory, installation instructions, and usage examples
**Plans:** 2/2 plans complete

Plans:
- [ ] 05-01-PLAN.md -- Eval prompts, trigger description optimization, and eval results for all 13 skills
- [ ] 05-02-PLAN.md -- Create install.sh and README.md for packaging and distribution

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundations | 3/3 | Complete    | 2026-03-05 |
| 2. Core Workflow | 4/4 | Complete   | 2026-03-06 |
| 3. Presentation and Quality | 4/4 | Complete | 2026-03-06 |
| 4. Specialized Patterns | 2/2 | Complete | 2026-03-06 |
| 5. Eval, Optimization, and Packaging | 2/2 | Complete   | 2026-03-06 |

### Phase 6: Live Eval Loop

**Goal:** Run 4 representative skills (scaffold, entities, caching, testing) through real functional evaluation with Sonnet 4.6 subagents against live Drupal ddev instances, producing graded benchmarks and HTML viewers that prove skills make a measurable difference
**Requirements**: LIVE-01, LIVE-02, LIVE-03, LIVE-04
**Depends on:** Phase 5
**Success Criteria** (what must be TRUE):
  1. Each of the 4 skills under test has evals.json with functional assertions grounded in os-knowledge-garden tasks
  2. Setup/teardown scripts manage isolated ddev Drupal environments for eval runs
  3. All 8 eval runs (4 skills x with/without skill) produce outputs and transcripts in correct workspace directory structure
  4. Graded benchmarks show with-skill pass rates higher than without-skill for at least 3 of 4 skills
  5. HTML eval viewers and analysis summary are available for human review
**Plans:** 2/4 plans executed

Plans:
- [ ] 06-01-PLAN.md -- Create eval infrastructure (evals.json files + setup/teardown scripts)
- [ ] 06-02-PLAN.md -- Run batch 1 eval subagents (scaffold + entities)
- [ ] 06-03-PLAN.md -- Run batch 2 eval subagents (caching + testing)
- [ ] 06-04-PLAN.md -- Grade, aggregate benchmarks, generate HTML viewers, analyze results
