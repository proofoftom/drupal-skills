# Technology Stack

**Project:** Drupal Skills for Claude
**Researched:** 2026-03-05

## Recommended Stack

### Skill Framework: Claude Skill-Creator Plugin (Official)

| Component | Version/Source | Purpose | Why |
|-----------|---------------|---------|-----|
| skill-creator plugin | `skill-creator@claude-plugins-official` | Skill drafting, eval loop, description optimization | Already installed locally; official Anthropic tooling with grader, comparator, and analyzer agents built in |
| SKILL.md format | Frontmatter + Markdown body | Core skill definition | Only format Claude Code recognizes; three-level progressive disclosure (metadata -> body -> references) |
| `~/.claude/skills/` | Local install target | Runtime skill loading | Claude Code auto-discovers skills here; instant availability without plugin install |
| `skills/` repo directory | GitHub publish target | Distribution and versioning | Standard pattern for sharing skills; users clone and copy to `~/.claude/skills/` |

**Confidence: HIGH** -- Verified from installed skill-creator plugin source code and multiple official example skills on this machine.

### Skill Anatomy Rules

Each Drupal skill follows this exact structure:

```
drupal-{domain}/
  SKILL.md              # Required. YAML frontmatter + Markdown body
  references/           # Domain-specific Drupal API docs, code patterns
    {topic}.md          # Loaded by Claude on-demand, not upfront
  examples/             # Optional. Complete working code examples
    {example}.php       # Copy-paste ready Drupal code
```

**Confidence: HIGH** -- Derived from official skill-creator SKILL.md (lines 76-84), skill-development reference, and 15+ example skills examined on disk.

#### SKILL.md Requirements

| Rule | Specification | Rationale |
|------|--------------|-----------|
| **Frontmatter fields** | `name` (required), `description` (required) only | Claude Code parses only these two fields; `version` is tolerated but ignored by runtime |
| **Frontmatter max** | 1024 characters total | Hard limit in Claude Code's skill loader |
| **Name format** | Letters, numbers, hyphens only | No spaces, underscores, or special characters; e.g., `drupal-routing-controllers` |
| **Description style** | Pushy, third-person, trigger-focused | skill-creator docs explicitly say to make descriptions "a little bit pushy" to combat under-triggering; include specific user phrases that should activate the skill |
| **Description content** | When to use ONLY, never workflow summary | Writing-skills research proved Claude follows description shortcuts and skips body content if description summarizes the workflow |
| **Body size** | <500 lines (~3,000-5,000 words max) | skill-creator says "<500 lines ideal"; skill-development says "1,500-2,000 words ideal, <5k max" |
| **Writing style** | Imperative/infinitive form, not second person | "Create the route file" not "You should create the route file" -- consistency for AI consumption |
| **Reference pointers** | Explicit references to `references/*.md` with guidance on when to read | Claude does not auto-discover reference files; SKILL.md must tell it they exist and when to consult them |

**Confidence: HIGH** -- Cross-verified across skill-creator SKILL.md, writing-skills SKILL.md, and skill-development SKILL.md.

#### Progressive Disclosure (Three Levels)

This is the most important architectural concept for Drupal skills. Drupal's API surface is enormous; cramming everything into SKILL.md will blow context windows and degrade performance.

| Level | What | When Loaded | Size Target | Drupal Application |
|-------|------|-------------|-------------|-------------------|
| 1. Metadata | `name` + `description` | Always in context | ~100 words | Skill name + trigger phrases ("create a Drupal route", "build a custom entity") |
| 2. SKILL.md body | Core workflow, decision trees, quick reference | When skill triggers | <500 lines | Drupal patterns overview, when-to-use guidance, common code structures, pointers to references |
| 3. References | Detailed API docs, code examples, edge cases | On-demand when Claude needs them | Unlimited (2,000-5,000+ words each) | Full entity annotation reference, form API element catalog, hook signatures, D10 vs D11 differences |

**Why this matters for Drupal skills specifically:** Each of the 13 skills covers multiple Drupal subsystems. For example, `drupal-entities-fields` spans content entities, config entities, field types, field widgets, field formatters, and the Entity API -- easily 10,000+ words of reference material. Without progressive disclosure, the skill would consume the entire context window and leave no room for the user's actual code.

**Confidence: HIGH** -- Progressive disclosure documented identically in skill-creator, skill-development, and writing-skills sources.

### Reference File Organization

For Drupal skills specifically, organize references by sub-domain within each skill:

```
drupal-routing-controllers/
  SKILL.md
  references/
    route-definitions.md       # .routing.yml patterns, parameters, requirements
    controllers.md             # Controller class patterns, DI, response types
    services-di.md             # services.yml, dependency injection, service tags
    menu-links.md              # Menu plugin types, derivatives

drupal-entities-fields/
  SKILL.md
  references/
    content-entities.md        # Entity type annotations, base fields, handlers
    config-entities.md         # Config entity patterns, schema, list builders
    field-types.md             # FieldType plugin, widget, formatter annotations
    entity-api-patterns.md     # Entity queries, access, storage
```

**Why this pattern:** The claude-api skill (examined on disk) uses exactly this approach -- language-specific subdirectories under a single skill. The mcp-builder skill does the same with `reference/aws.md`, `reference/gcp.md`. For Drupal, the sub-domains are Drupal API subsystems rather than programming languages.

**Key rule:** Each reference file should be self-contained for its sub-domain. Claude reads only the relevant reference file, not all of them.

**For large reference files (>10k words):** Include grep search patterns in SKILL.md so Claude can search rather than read the entire file. Example: "For entity annotation details, grep `references/content-entities.md` for `@ContentEntityType`".

**Confidence: HIGH** -- Pattern verified in claude-api, mcp-builder, and hook-development example skills.

### Eval Framework

Use the skill-creator plugin's built-in eval loop. Do NOT build a custom pipeline.

#### Eval Workflow Per Skill

```
1. Draft SKILL.md + references
2. Write 2-3 test prompts in evals/evals.json
   - Realistic Drupal development requests
   - Example: "Create a custom block plugin that displays recent articles with a config form for the count"
3. Spawn parallel subagents:
   - WITH skill: Claude + SKILL.md -> outputs/
   - WITHOUT skill (baseline): Claude alone -> outputs/
4. Grade outputs with grader agent (agents/grader.md)
5. Aggregate benchmark (scripts/aggregate_benchmark)
6. Launch eval viewer (eval-viewer/generate_review.py)
7. Review outputs qualitatively + quantitatively
8. Iterate on skill based on feedback
```

#### Eval Test Prompt Design for Drupal

| Quality | Characteristic | Example |
|---------|---------------|---------|
| Realistic | What a developer would actually ask | "Add a route that shows user profiles at /user/{uid}/profile with access checking" |
| Multi-step | Requires multiple Drupal concepts | "Create a config entity with a list builder, add/edit forms, and menu links" |
| Verifiable | Output can be checked against Drupal conventions | Generated .routing.yml follows correct schema, controller extends ControllerBase, services.yml has correct syntax |
| Specific | Not vague or abstract | Include file paths, module names, specific Drupal APIs |

#### Assertions for Drupal Skills

Drupal code has highly verifiable structure. Good assertions include:

- File existence: `.module`, `.routing.yml`, `.services.yml`, `.info.yml` created
- Namespace correctness: `\Drupal\{module}\Controller\{Class}` pattern followed
- Annotation validity: `@Block`, `@ContentEntityType` annotations present with required keys
- Service definitions: Correct class, arguments with `@` service references
- Hook signatures: Correct parameters for `hook_theme()`, `hook_form_alter()`, etc.

**Confidence: HIGH** -- Eval framework documented in detail in skill-creator SKILL.md with schemas for all JSON structures.

### Drupal-Specific Considerations

| Consideration | Approach | Rationale |
|--------------|----------|-----------|
| **D10 baseline, D11 notes** | All code patterns use D10 APIs from the book; D11 differences noted in dedicated section per reference file | Book is D10 (2023); D11 adds attributes-based routing, new hooks, etc. Noting differences prevents confusion without rewriting everything |
| **API complexity** | Use reference files heavily; SKILL.md provides decision trees, references provide API details | Drupal's annotation-based plugin system alone has dozens of required/optional keys per plugin type |
| **Namespace conventions** | Include PSR-4 namespace patterns in every reference file | Drupal's directory structure IS its namespace; wrong directory = broken code |
| **YAML-heavy configuration** | Reference files should include complete YAML examples, not fragments | Drupal routes, services, permissions, schema all use YAML with strict indentation requirements |
| **Cross-skill references** | Use skill name only: "Consult `drupal-routing-controllers` for route setup" | Do NOT use `@` file links (burns context); do NOT summarize the other skill's content |
| **Book accuracy** | Skill content must be traceable to specific book chapters | Source material is `Sipos D. Drupal 10 Module Development` (4th ed, 2023); skills should not hallucinate APIs |

**Confidence: MEDIUM** -- Drupal-specific patterns based on training knowledge of D10 architecture. D11 differences should be verified against official Drupal.org docs during skill creation.

### Test Project Integration

The `os-knowledge-garden/` project provides real-world validation material:

| Module | Drupal Concepts Exercised | Useful For Validating |
|--------|--------------------------|----------------------|
| `social_ai_indexing` | Search API processors, services, configuration | `drupal-config-storage`, `drupal-plugins-blocks` |
| `localnodes_platform` | Routes, controllers, services, DI | `drupal-routing-controllers`, `drupal-module-scaffold` |
| Demo modules | Blocks, event subscribers, templates | `drupal-plugins-blocks`, `drupal-theming` |

Use these modules as input for eval prompts: "Given this existing module structure, add X feature" tests whether the skill produces code consistent with real project conventions.

**Confidence: MEDIUM** -- Module names from PROJECT.md; actual module contents not verified during this research.

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Eval framework | skill-creator plugin eval loop | Custom pytest/PHPUnit pipeline | Unnecessary complexity; skill-creator handles the full draft-test-grade-iterate cycle with built-in viewer |
| Skill format | SKILL.md + references/ | Single monolithic SKILL.md | Drupal API surface too large; would exceed 500-line body limit and degrade Claude's performance |
| Skill format | SKILL.md + references/ | CLAUDE.md project instructions | Skills are portable across projects; CLAUDE.md is project-specific. Skills also get progressive disclosure which CLAUDE.md does not |
| Description style | Pushy/trigger-focused | Conservative/minimal | skill-creator explicitly warns about under-triggering; Drupal skills need aggressive trigger phrases because developers may not say "Drupal" in every prompt |
| Reference organization | Sub-domain files (one per API area) | Single large reference file | Claude reads entire reference files; smaller focused files = less context waste |
| D11 handling | Notes within D10 reference files | Separate D11 skill variants | D11 changes are additive, not rewrites; separate skills would duplicate 90% of content |

## Skill Naming Convention

All 13 skills use the `drupal-` prefix for consistent discovery:

```
drupal-module-scaffold
drupal-routing-controllers
drupal-forms-api
drupal-plugins-blocks
drupal-entities-fields
drupal-config-storage
drupal-access-security
drupal-theming
drupal-caching
drupal-testing
drupal-database-api
drupal-views-dev
drupal-batch-queue-cron
```

**Why `drupal-` prefix:** Ensures Claude discovers these skills whenever a user mentions Drupal. Without the prefix, a skill named `routing-controllers` might not trigger for "help me build a Drupal route."

## Installation

No package installation required. Skills are plain Markdown files.

```bash
# Development: skills live in the repo
ls skills/drupal-module-scaffold/SKILL.md

# Local install: copy to Claude's skills directory
cp -r skills/drupal-* ~/.claude/skills/

# Verification: check skill is discovered
# (Claude Code auto-discovers on next session start)
```

## Description Template for Drupal Skills

Based on skill-creator's guidance to be "pushy" about triggering:

```yaml
---
name: drupal-{domain}
description: >-
  Guide for {specific Drupal capability}. Use this skill when building
  Drupal modules that need {capability 1}, {capability 2}, or {capability 3}.
  Also use when the user mentions {specific Drupal terms like "routing.yml",
  ".module file", "block plugin"}, asks to create {specific artifacts},
  or needs help with {related Drupal APIs}. Covers Drupal 10 patterns
  with Drupal 11 differences noted.
---
```

## Sources

- **skill-creator SKILL.md** (PRIMARY): `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/SKILL.md` -- Official skill creation workflow, anatomy rules, eval framework, description optimization
- **skill-creator schemas.md**: `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/references/schemas.md` -- JSON schemas for evals.json, grading.json, benchmark.json
- **skill-creator grader.md**: `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/skill-creator/205b6e0b3036/skills/skill-creator/agents/grader.md` -- Grading agent specification
- **writing-skills SKILL.md**: `/home/proofoftom/.claude/plugins/cache/claude-plugins-official/superpowers/4.3.1/skills/writing-skills/SKILL.md` -- TDD approach to skills, CSO (Claude Search Optimization), anti-patterns
- **skill-development SKILL.md**: `/home/proofoftom/.claude/plugins/marketplaces/claude-plugins-official/plugins/plugin-dev/skills/skill-development/SKILL.md` -- Plugin-specific skill patterns, progressive disclosure details
- **claude-api SKILL.md** (example): `/home/proofoftom/.claude/plugins/cache/anthropic-agent-skills/example-skills/7029232b9212/skills/claude-api/SKILL.md` -- Reference-heavy skill with language-specific subdirectories (pattern model for Drupal sub-domain references)
- **mcp-builder SKILL.md** (example): `/home/proofoftom/.claude/plugins/cache/anthropic-agent-skills/example-skills/7029232b9212/skills/mcp-builder/SKILL.md` -- Multi-phase skill with reference file organization (pattern model)
- **context7 SKILL.md** (example): `/home/proofoftom/.claude/skills/context7/SKILL.md` -- Locally installed skill showing real-world structure
