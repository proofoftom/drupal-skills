# Project Research Summary

**Project:** Drupal Skills for Claude
**Domain:** Claude Code skills for Drupal 10/11 module development
**Researched:** 2026-03-05
**Confidence:** HIGH

## Executive Summary

This project creates 13 Claude Code skills that encode expert Drupal module development knowledge, sourced primarily from Sipos's "Drupal 10 Module Development" (4th ed, 2023) and validated against the `os-knowledge-garden` test project. The skills use Anthropic's official skill-creator plugin and the standard SKILL.md format with progressive disclosure: thin metadata for triggering, a sub-500-line body for core decision logic, and reference files for detailed API patterns. This is not a documentation project -- it is a behavioral correction project. Claude already knows Drupal APIs from training data, but it gets structural patterns wrong (missing DI boilerplate, D7-era function calls, incomplete YAML ecosystems, shallow entity annotations). The skills exist to override these specific failure modes.

The recommended approach is a 4-wave build following dependency order: foundational skills first (module scaffold, routing, entities), then core workflow skills (forms, blocks, config, access), then presentation/quality skills (theming, caching, testing, database), and finally specialized skills (views, batch/queue/cron). Each wave uses the skill-creator eval loop to validate that skills produce measurable improvement over Claude's baseline. The most architecturally important decision is self-contained skills with no shared file dependencies -- each skill directory works independently when copied to `~/.claude/skills/`.

The primary risks are: (1) writing reference-doc skills instead of decision-guide skills, which produces no improvement over baseline Claude; (2) the entities-fields skill exceeding the 500-line limit due to Drupal's massive entity API surface; and (3) trigger description overlap across 13 co-existing skills causing context window bloat. All three are mitigated by the eval loop -- baseline comparison catches ineffective skills, line counts are measurable, and description optimization is a dedicated post-drafting step.

## Key Findings

### Recommended Stack

The entire stack is Markdown files processed by Claude Code's built-in skill loader. No packages, no build tools, no runtime dependencies. Skills are plain `SKILL.md` files with YAML frontmatter (`name` and `description` only, max 1024 chars) plus a Markdown body under 500 lines. Reference files live in a `references/` subdirectory per skill. The skill-creator plugin provides the eval framework: parallel with-skill vs. baseline testing, grader agents, benchmark aggregation, and description optimization.

**Core technologies:**
- **SKILL.md format**: Skill definition -- only format Claude Code recognizes; frontmatter triggers loading, body provides guidance
- **skill-creator plugin**: Eval and iteration -- built-in eval loop with grader, comparator, and benchmark aggregation
- **Progressive disclosure (3-level)**: Context management -- metadata always loaded, body on trigger, references on demand; critical because Drupal's API surface would otherwise blow context windows
- **`drupal-` prefix convention**: Discovery -- ensures skills trigger on any Drupal-related prompt

**Critical version requirements:**
- All code patterns target Drupal 10 baseline with D11 attribute syntax shown alongside D10 annotations
- Skills must explicitly suppress D7/D8 patterns that Claude over-represents from training data

### Expected Features

**Must have (table stakes -- all 13 skills):**
- Correct PSR-4 file placement and namespace conventions
- YAML syntax accuracy for .routing.yml, .services.yml, .permissions.yml, etc.
- D10 baseline code with D11 PHP attribute annotations alongside D10 Doctrine annotations
- Dependency injection patterns (`create()` + `__construct()`) correct per context type (Controller, Form, Block, Service)
- Cross-references to related skills (advisory, never load-bearing)
- Complete file ecosystem (PHP classes always paired with required YAML)

**Should have (differentiators -- what makes skills better than baseline Claude):**
- "Wrong way" callouts with explicit anti-patterns Claude commonly generates
- Complete entity annotation reference (Claude typically generates 5-6 of 20+ required keys)
- Config schema completeness (Claude almost never generates schema files)
- Cache metadata in render arrays (Claude almost never adds #cache)
- Decision trees for when-to-use-what (config entity vs content entity, State API vs TempStore)
- Production checklist per skill (access checks, error handling, input validation)

**Defer (not in scope):**
- Migration API (not in source book, would require a 14th skill)
- Contrib module patterns (Views, Paragraphs, Commerce -- stale quickly)
- Drupal installation/setup, Drush command catalog, Composer management
- Exhaustive form element or Twig syntax references (covered by api.drupal.org)

### Architecture Approach

Each of the 13 skills is a fully self-contained directory with a SKILL.md body and optional `references/` subdirectory. There is no shared file directory -- cross-skill knowledge sharing happens through advisory text references ("consult drupal-module-scaffold for module setup"), not file imports. This eliminates installation complexity and ensures any subset of skills works independently. Five skills have reference files for secondary content: routing (menus), entities (files/images), config (i18n), theming (JS/Ajax), and batch-queue-cron (logging/mail/tokens).

**Major components:**
1. **skills/ directory** -- 13 self-contained skill directories, each installable independently to `~/.claude/skills/`
2. **evals/ directory** -- skill-creator eval configs spanning all skills, with prompts grounded in `os-knowledge-garden`
3. **install.sh** -- bridges repo structure to Claude Code runtime (`~/.claude/skills/`)

**Key architectural decisions:**
- No `shared/` directory -- self-containment wins over deduplication
- Reference files are skill-local, not cross-skill
- Cross-references degrade gracefully (missing skill = less context, not errors)
- D10/D11 dual syntax is a project-wide convention, not per-skill decision

### Critical Pitfalls

1. **Reference-doc skills instead of decision guides** -- Skills that compress book chapters into mini-references produce no improvement over baseline Claude. Structure every skill around decision trees and anti-patterns. Recovery cost is HIGH (full rewrite). Detect via eval: if with-skill output barely differs from no-skill, the skill is a reference doc.

2. **500-line overflow on complex skills** -- `drupal-entities-fields` covers 4 book chapters spanning ~3000 lines of source. Use reference files aggressively: body as routing layer ("for config entities, see references/config-entities.md"), references for detailed API patterns. Establish this pattern in Wave 1.

3. **Trigger description overlap across 13 skills** -- Without deliberate tuning, multiple skills trigger on simple prompts, wasting context. Must run description optimization holistically across all 13 skills after all are drafted, not per-skill.

4. **D10 annotation-only examples in a D11 world** -- Book uses Doctrine annotations exclusively. Skills must show both annotation and PHP 8 attribute syntax, with attributes as the primary/preferred form. Mechanical fix (LOW recovery cost) but must be established as convention in Wave 1.

5. **Missing YAML file ecosystem** -- Skills that teach PHP classes without corresponding YAML produce code that does not actually work. Every PHP example must include its routing, services, permissions, and schema YAML counterparts.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Foundations and Patterns
**Rationale:** All 12 other skills depend on module scaffold. Routing and entities are the two most fundamental Drupal concepts and the areas where Claude's baseline is most wrong. Building these first establishes the skill template, progressive disclosure pattern, D10/D11 dual-syntax convention, and eval workflow that all subsequent phases inherit.
**Delivers:** 3 skills (drupal-module-scaffold, drupal-routing-controllers, drupal-entities-fields)
**Addresses:** Module structure, PSR-4 namespaces, route definitions, controllers, DI patterns, services, content/config entities, base fields, entity handlers, entity queries
**Avoids:** Reference-doc trap (P2) by establishing decision-guide template; 500-line overflow (P5) by establishing reference-file pattern on the hardest skill first; Version confusion (P6) by setting D10/D11 convention; Missing YAML (P7) by establishing file-ecosystem pattern
**Note:** `drupal-entities-fields` is the highest-risk skill due to API surface size. Its reference file structure becomes the model for all complex skills.

### Phase 2: Core Workflow
**Rationale:** Forms, blocks, config, and access are the daily-use patterns that depend on Phase 1 foundations. These four skills cover the most common developer tasks after initial module setup. Access-security is included here (moved from Wave 3 in FEATURES.md) because it cross-references routing and entities heavily and should be drafted while those skills are fresh.
**Delivers:** 4 skills (drupal-forms-api, drupal-plugins-blocks, drupal-config-storage, drupal-access-security)
**Addresses:** Form API lifecycle, form altering, block plugins, custom plugin types, config vs state vs tempstore, permissions, access handlers, CSRF/XSS prevention
**Avoids:** Cross-reference loops (P4) by defining concept ownership before drafting; Training data conflicts (P9) by running baseline comparisons

### Phase 3: Presentation and Quality
**Rationale:** Theming, caching, testing, and database skills depend on patterns established in Phases 1-2. Testing in particular needs all domain skills to exist first since test patterns exercise routes, forms, entities, and config.
**Delivers:** 4 skills (drupal-theming, drupal-caching, drupal-testing, drupal-database-api)
**Addresses:** Render arrays, Twig templates, theme hooks, cache tags/contexts/max-age, lazy builders, PHPUnit test types, kernel/functional tests, database abstraction, schema API
**Avoids:** Monolithic skills (P10) by validating each skill maps to distinct developer intent

### Phase 4: Specialized Patterns
**Rationale:** Views development and batch/queue/cron are less frequently needed and depend on entities, plugins, and database skills from earlier phases. These are the most likely candidates for skill splitting if trigger overlap becomes an issue.
**Delivers:** 2 skills (drupal-views-dev, drupal-batch-queue-cron)
**Addresses:** hook_views_data, Views plugins, batch API, queue workers, cron hooks, logging
**Avoids:** Monolithic skills (P10) -- evaluate whether batch-queue-cron should split into separate skills based on trigger testing

### Phase 5: Description Optimization and Multi-Skill Testing
**Rationale:** Trigger descriptions must be tuned holistically across all 13 skills, not per-skill. Multi-skill eval prompts must verify that skills produce coherent output when loaded simultaneously. This phase cannot happen until all skills exist.
**Delivers:** Optimized trigger descriptions, multi-skill eval results, final polish
**Addresses:** Trigger overlap (P3), skill interaction conflicts (P11), description optimization
**Avoids:** Context window bloat from multiple skills triggering on simple prompts

### Phase Ordering Rationale

- **Dependency-driven:** Module scaffold must exist before any other skill. Routing and entities must exist before forms, blocks, config, and access can cross-reference them.
- **Hardest-first:** `drupal-entities-fields` is the most complex skill and is in Phase 1. If the progressive disclosure pattern works for entities, it works for everything.
- **Eval-validated:** Each phase runs the skill-creator eval loop before proceeding, preventing error propagation across phases.
- **Holistic optimization last:** Description optimization and multi-skill testing require all skills to exist. Doing this per-phase would miss cross-skill interactions.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1 (drupal-entities-fields):** The reference file structure for this skill is high-risk. Need to research exactly which entity annotation keys are required vs optional and how to split content vs config entity patterns across body and references.
- **Phase 2 (drupal-access-security):** Permission/access layering (route + entity + field + node grants) is complex and security-critical. Need to verify correct patterns against Drupal.org docs, not just book content.
- **Phase 4 (drupal-batch-queue-cron):** This skill bundles 4+ loosely related concepts (batch, queue, cron, logging, mail). May need research to determine if splitting is warranted.

Phases with standard patterns (skip research-phase):
- **Phase 1 (drupal-module-scaffold, drupal-routing-controllers):** Well-documented, established patterns. The skill-creator anatomy is thoroughly documented.
- **Phase 2 (drupal-forms-api, drupal-plugins-blocks, drupal-config-storage):** Standard Drupal patterns with clear book chapter mappings.
- **Phase 3 (all skills):** Straightforward domain extraction following patterns established in earlier phases.
- **Phase 5 (description optimization):** The skill-creator plugin has a documented optimization workflow.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Verified from installed skill-creator plugin source code, official example skills, and skill-development references on disk |
| Features | HIGH | Feature list derived from book table of contents cross-referenced with real project (os-knowledge-garden) and known Claude baseline weaknesses |
| Architecture | HIGH | Self-contained skill structure verified across multiple official and community skill examples; dependency graph derived from Drupal's actual API dependencies |
| Pitfalls | MEDIUM | Novel domain (framework-specific Claude skills at 13-skill scale) with few public precedents. Pitfalls identified through analysis, not observed failures. Eval loop is the primary validation mechanism. |

**Overall confidence:** HIGH

### Gaps to Address

- **D11 attribute syntax accuracy:** Book is D10-only. D11 PHP attribute syntax for each plugin type should be verified against Drupal.org API docs during skill drafting, not assumed from training knowledge.
- **os-knowledge-garden module contents:** Module names are known from PROJECT.md but actual code contents were not verified during research. Eval prompts grounded in these modules need the real code as context.
- **Claude's current Drupal baseline:** The specific patterns Claude gets wrong were identified from general knowledge, not systematic baseline testing. Phase 1 should include formal baseline eval runs to calibrate where skills add value.
- **Skill-creator eval viewer:** The eval viewer (eval-viewer/generate_review.py) is referenced in skill-creator docs but its current working state was not verified.
- **Multi-skill loading behavior:** How Claude Code handles loading 2-3 triggered skills simultaneously (context budget, ordering) is not documented. Phase 5 testing will reveal this empirically.

## Sources

### Primary (HIGH confidence)
- skill-creator SKILL.md -- skill anatomy, eval framework, description optimization, schemas
- writing-skills SKILL.md -- TDD approach, CSO (Claude Search Optimization), anti-patterns
- skill-development SKILL.md -- progressive disclosure, plugin-specific skill patterns
- claude-api SKILL.md (example) -- reference-heavy skill structure model
- mcp-builder SKILL.md (example) -- multi-phase skill organization model

### Secondary (MEDIUM confidence)
- Sipos D. "Drupal 10 Module Development" 4th ed, 2023 -- primary domain content source (D10 patterns verified, D11 patterns need validation)
- os-knowledge-garden project -- real-world validation material (module names verified, contents not deeply inspected)
- polished-tickling-owl.md -- project execution plan with skill definitions and wave structure

### Tertiary (LOW confidence)
- Claude's Drupal training data tendencies -- inferred from general knowledge of common LLM mistakes with Drupal, not systematically measured. Baseline eval in Phase 1 will validate.

---
*Research completed: 2026-03-05*
*Ready for roadmap: yes*
