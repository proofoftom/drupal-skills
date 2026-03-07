# Phase 5: Eval, Optimization, and Packaging - Research

**Researched:** 2026-03-05
**Domain:** Claude Skills evaluation, trigger optimization, and distribution packaging
**Confidence:** HIGH

## Summary

Phase 5 is the final phase of the Drupal Skills project. All 13 skills are complete and installed in `skills/`. This phase focuses on three areas: (1) evaluating each skill against real-world Drupal prompts grounded in the os-knowledge-garden project, (2) optimizing trigger descriptions holistically so the right skill(s) activate without loading irrelevant ones, and (3) packaging the repository for GitHub distribution with install.sh and README.

The eval/optimization work is the core intellectual challenge. Trigger descriptions must be tuned so that Claude's LLM-based skill discovery selects the correct subset of skills for any given Drupal prompt. This requires understanding how Claude scans ~100 tokens of description metadata per skill to decide relevance, and ensuring 13 descriptions partition the Drupal development space cleanly without overlap or gaps. The packaging work is straightforward shell scripting and documentation.

**Primary recommendation:** Use evaluation-driven development -- write eval prompts first (grounded in os-knowledge-garden tasks), measure baseline activation, then tune descriptions iteratively. Package and document last.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| EVAL-01 | Each skill passes eval loop (with-skill vs baseline comparison) | Eval prompt design patterns, os-knowledge-garden module analysis for grounded prompts |
| EVAL-02 | Eval prompts grounded in os-knowledge-garden project tasks | Custom module inventory below provides concrete task material |
| EVAL-03 | Trigger descriptions optimized holistically across all 13 skills | Trigger analysis, overlap identification, and optimization patterns |
| EVAL-04 | Multi-skill interaction testing with cross-domain prompts | Multi-skill prompt design patterns and expected activation maps |
| PACK-01 | skills/ folder contains all 13 skill directories ready for GitHub | Already complete -- verify structure |
| PACK-02 | install.sh copies/symlinks skills to ~/.claude/skills/ | Install script patterns and symlink vs copy tradeoffs |
| PACK-03 | Repository README documents skill inventory, installation, usage | README structure template |
</phase_requirements>

## Standard Stack

### Core
| Component | Purpose | Why Standard |
|-----------|---------|--------------|
| SKILL.md with YAML frontmatter | Skill definition format | Claude Code standard; `name` + `description` in frontmatter, body under 500 lines |
| `~/.claude/skills/` | Personal skill installation target | Official Claude Code skills directory |
| `.claude/skills/` | Project-level skill directory | Official Claude Code project skills path |
| Bash install.sh | Installation script | Standard for GitHub-distributed developer tools |
| Markdown README.md | Repository documentation | Standard for GitHub repositories |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| `references/` subdirectory | Progressive disclosure for large skills | Already used by 5 skills (entities, routing, theming, config, batch) |
| Symlinks | Install without duplication | When users want to keep skills updated from git repo |

## Architecture Patterns

### Current Repository Structure
```
drupal-skills/
├── skills/
│   ├── drupal-module-scaffold/
│   │   ├── SKILL.md
│   │   └── references/
│   ├── drupal-routing-controllers/
│   │   ├── SKILL.md
│   │   └── references/
│   │       └── menus.md
│   ├── ... (13 total skill directories)
├── os-knowledge-garden/        # Test project for eval prompts
├── install.sh                  # TO CREATE
└── README.md                   # TO CREATE
```

### Pattern 1: Evaluation-Driven Trigger Optimization

**What:** Write eval prompts first, measure which skills activate, then tune descriptions until activation is correct.

**Why:** Claude uses LLM reasoning (not keyword matching) to select skills from ~100 tokens of description per skill. You cannot predict activation patterns from description text alone -- you must test empirically.

**Process:**
1. Write eval prompts covering all 13 skills (single-skill and multi-skill)
2. For each prompt, define expected activation set
3. Test with skills installed, observe actual activation
4. Identify under-triggering (skill should activate but does not) and over-triggering (skill activates when irrelevant)
5. Adjust descriptions and re-test

### Pattern 2: Trigger Description Holistic Optimization

**What:** Tune all 13 descriptions as a system, not individually.

**Why:** Each description competes with 12 others for activation. Changing one description affects activation patterns of all others. Per the official docs: "Claude has a tendency to 'undertrigger' skills -- to not use them when they'd be useful. To combat this, make descriptions a little bit 'pushy'."

**Current descriptions analysis (overlap risks):**

| Overlap Area | Skills Involved | Risk |
|-------------|-----------------|------|
| "render arrays" | drupal-caching, drupal-theming | MEDIUM -- both mention render arrays, but caching = metadata, theming = output |
| "routes" / "controllers" | drupal-routing-controllers, drupal-access-security | LOW -- access focuses on restrictions, routing on creation |
| "blocks" | drupal-plugins-blocks, drupal-caching | LOW -- plugins = creation, caching = metadata on blocks |
| "entity" | drupal-entities-fields, drupal-database-api | LOW -- database explicitly says "Do NOT use for entity data" |
| "services" / "DI" | drupal-routing-controllers, drupal-plugins-blocks | MEDIUM -- both mention DI patterns |

**Optimization principles:**
- Each description should state what the skill does AND when to use it (third person)
- Include negative triggers where disambiguation is needed ("Do NOT use for X")
- Use specific Drupal terms as signals (e.g., "hook_views_data" vs generic "data display")
- Keep under 1024 characters (hard limit), aim for 2-4 lines

### Pattern 3: Multi-Skill Activation Testing

**What:** Test prompts that should activate 2-4 skills simultaneously.

**Why:** Real Drupal development tasks span multiple domains. "Create a module with a custom entity, form, and themed output" should activate: drupal-module-scaffold + drupal-entities-fields + drupal-forms-api + drupal-theming.

**Expected multi-skill activation maps:**

| Prompt Pattern | Expected Skills |
|---------------|-----------------|
| "Create a module with a custom entity and admin form" | scaffold, entities, forms |
| "Add a block that queries the database and displays themed output" | plugins-blocks, database-api, theming, caching |
| "Create a settings form with config schema" | forms, config-storage |
| "Add a route with access control" | routing, access-security |
| "Write kernel tests for a custom entity" | testing, entities |
| "Expose entity data to Views" | views-dev, entities |
| "Process items in a queue triggered by cron" | batch-queue-cron |
| "Create a custom entity with form, display, and Views integration" | scaffold, entities, forms, theming, views-dev |

### Anti-Patterns to Avoid
- **Tuning descriptions in isolation:** Changing one skill's description without checking impact on all 13
- **Keyword-stuffing descriptions:** Claude uses semantic understanding, not keyword matching. Adding more terms can cause over-triggering
- **Testing only with single-skill prompts:** Real usage is multi-skill; must test cross-domain scenarios
- **Manual eval without documentation:** Eval results must be documented so improvements can be measured

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Skill installation | Custom package manager | Simple bash cp/ln -s to ~/.claude/skills/ | Claude Code has a standard path; just copy files there |
| Eval framework | Custom test harness | Manual prompts with documented expected vs actual results | Project constraint says "use existing skill-creator eval loop" |
| Trigger optimization | Algorithmic keyword extraction | Iterative human-in-the-loop testing with Claude | LLM-based discovery means only LLM testing reveals true behavior |

**Key insight:** There is no automated eval pipeline to build. The skill-creator eval loop is manual: prompt Claude with and without skills, compare output quality, document improvements.

## Common Pitfalls

### Pitfall 1: Under-triggering from Overly Specific Descriptions
**What goes wrong:** A skill has a very specific description that only activates on exact-match prompts, missing natural language variations.
**Why it happens:** Descriptions written from the skill author's perspective rather than the user's vocabulary.
**How to avoid:** Include user-facing phrases: "Use when asked to..." covers how users actually phrase requests. Include synonyms (e.g., "settings page" alongside "configuration form").
**Warning signs:** A skill never activates unless you use its exact domain terminology.

### Pitfall 2: Over-triggering from Generic Descriptions
**What goes wrong:** A skill activates on too many prompts because its description uses overly broad terms.
**Why it happens:** Using terms like "Drupal module" (which matches everything) or "data" (matches too many skills).
**How to avoid:** Use specific Drupal API names and patterns as anchors. "hook_views_data" is more precise than "expose data."
**Warning signs:** A skill activates on unrelated prompts.

### Pitfall 3: Install Script Assumes Directory Structure
**What goes wrong:** install.sh fails because ~/.claude/skills/ does not exist or has different permissions.
**Why it happens:** Not checking for directory existence before copying.
**How to avoid:** Always `mkdir -p ~/.claude/skills/` before copying. Check for existing skills and warn about overwrites.
**Warning signs:** Fresh machine install fails.

### Pitfall 4: Symlink vs Copy Confusion
**What goes wrong:** Using symlinks means moving/deleting the repo breaks all skills. Using copies means updates require re-running install.
**Why it happens:** Not thinking through the user's workflow.
**How to avoid:** Default to copying (simpler, more robust). Offer `--symlink` flag for power users who want live updates.
**Warning signs:** Users report skills "disappeared" after moving the repo.

### Pitfall 5: README Documents Internal Structure, Not User Workflow
**What goes wrong:** README explains how skills are built rather than how to install and use them.
**Why it happens:** Author perspective vs user perspective.
**How to avoid:** Lead with installation, then usage examples, then skill inventory. Build instructions belong in a separate section or CONTRIBUTING.md.
**Warning signs:** Users can't figure out how to get started from the README.

### Pitfall 6: Multi-Skill Prompts Get Incoherent Output
**What goes wrong:** When multiple skills activate, their guidance conflicts or produces duplicated/contradictory code patterns.
**Why it happens:** Skills were written independently without considering how they compose.
**How to avoid:** Test specific multi-skill scenarios and verify coherent output. Ensure cross-references between skills align.
**Warning signs:** Generated code has duplicate service definitions, conflicting DI patterns, or mixed module file patterns.

## Code Examples

### install.sh Pattern

```bash
#!/usr/bin/env bash
# Install Drupal skills to ~/.claude/skills/
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILLS_SOURCE="$SCRIPT_DIR/skills"
SKILLS_TARGET="${HOME}/.claude/skills"

# Validate source exists
if [ ! -d "$SKILLS_SOURCE" ]; then
  echo "Error: skills/ directory not found at $SKILLS_SOURCE"
  exit 1
fi

# Create target directory
mkdir -p "$SKILLS_TARGET"

# Parse arguments
USE_SYMLINKS=false
if [ "${1:-}" = "--symlink" ]; then
  USE_SYMLINKS=true
fi

# Install each skill
installed=0
for skill_dir in "$SKILLS_SOURCE"/drupal-*/; do
  skill_name=$(basename "$skill_dir")
  target="$SKILLS_TARGET/$skill_name"

  if [ -e "$target" ] || [ -L "$target" ]; then
    echo "  Updating: $skill_name"
    rm -rf "$target"
  else
    echo "  Installing: $skill_name"
  fi

  if [ "$USE_SYMLINKS" = true ]; then
    ln -s "$skill_dir" "$target"
  else
    cp -r "$skill_dir" "$target"
  fi
  ((installed++))
done

echo ""
echo "Installed $installed Drupal skills to $SKILLS_TARGET"
echo "Skills will be available in your next Claude Code session."
```

### README.md Structure

```markdown
# Drupal Skills for Claude

13 Claude skills that teach Claude to generate correct, production-ready
Drupal 10/11 module code.

## Quick Start

git clone <repo>
cd drupal-skills
./install.sh

## What's Included

| Skill | What It Does |
|-------|-------------|
| drupal-module-scaffold | Scaffold modules with correct .info.yml and PSR-4 |
| ... | ... |

## Usage

Once installed, skills activate automatically when you ask Claude
about Drupal module development:

- "Create a new Drupal module called my_events"
- "Add a settings form with config schema"
- "Create a custom content entity type"

## Uninstall

rm -rf ~/.claude/skills/drupal-*
```

### Eval Prompt Template (for EVAL-01 and EVAL-02)

```markdown
## Eval Prompt: [Skill Name]
**Grounded in:** os-knowledge-garden [module name]
**Expected skills:** [list]

**Prompt:**
"[Natural language Drupal task grounded in real project context]"

**Without skills (baseline):**
[Document what Claude produces without skills -- common mistakes, missing patterns]

**With skills (expected improvement):**
[Document what correct output looks like when skills are active]

**Verdict:** PASS / FAIL / PARTIAL
```

## os-knowledge-garden Eval Material

The os-knowledge-garden project provides real-world grounding for eval prompts. Key modules and their Drupal patterns:

### social_ai_indexing Module
Exercises: services/DI, routing/controllers, blocks, event subscribers, theme hooks, preprocess functions, Twig templates, permissions, config YAML.

**Potential eval prompts:**
- "Create a Drupal service that loads related content using entity type manager and a permission filter" (routing, access)
- "Create a block plugin that displays AI-related content with a configurable bundle" (plugins-blocks, theming)
- "Implement hook_theme() and hook_preprocess_block() for a custom block" (theming)
- "Add a route that returns JSON from a controller using DI" (routing)
- "Create an event subscriber service with tagged services.yml" (routing)

### localnodes_platform Module
Exercises: config/install YAML, Drush commands, deploy hooks.

**Potential eval prompts:**
- "Create config/install YAML files for block placement and search index configuration" (config-storage)

### Demo Modules (localnodes_demo, boulder_demo, portland_demo)
Exercise: Custom plugin types (DemoContent plugin), entity CRUD.

**Potential eval prompts:**
- "Create a custom plugin type for demo content with a plugin manager" (plugins-blocks)
- "Create entity content programmatically from YAML data files" (entities)

### Multi-Skill Eval Prompts (EVAL-04)
These prompts should trigger 3+ skills:
- "Create a new Drupal module that defines a custom content entity with an admin form, list builder, and themed display" (scaffold + entities + forms + theming)
- "Add a Views integration for a custom entity with a custom filter plugin and expose data including a computed field" (views-dev + entities + plugins-blocks)
- "Create a module with a settings form that stores API keys in config, a cron job that calls the API, and a block that displays results with proper caching" (scaffold + forms + config + batch-queue-cron + plugins-blocks + caching + theming)

## State of the Art

| Aspect | Current State | Impact |
|--------|--------------|--------|
| Claude Skill discovery | LLM-based semantic matching on description field (~100 tokens per skill) | Cannot predict activation from text alone; must test |
| Description budget | 2% of context window, fallback 16,000 chars for all descriptions | With 13 skills at ~200 chars each (~2,600 total), well within budget |
| Skill composition | Multiple skills can activate simultaneously; Claude merges guidance | Must test multi-skill coherence |
| Install path | `~/.claude/skills/` (personal) or `.claude/skills/` (project) | install.sh targets personal path |

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual eval loop (skill-creator methodology) |
| Config file | None -- manual process |
| Quick run command | Test individual prompt with Claude + skills installed |
| Full suite command | Run all eval prompts and document results |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| EVAL-01 | Each skill shows improvement with-skill vs baseline | manual | Compare Claude output with/without skills | N/A -- manual |
| EVAL-02 | Eval prompts grounded in os-knowledge-garden tasks | manual | Verify prompts reference real module patterns | N/A -- manual |
| EVAL-03 | Trigger descriptions optimized holistically | manual | Test activation with diverse prompts | N/A -- manual |
| EVAL-04 | Multi-skill interaction produces coherent output | manual | Test cross-domain prompts | N/A -- manual |
| PACK-01 | skills/ folder contains all 13 skill directories | smoke | `ls skills/drupal-* \| wc -l` should be 13 | Wave 0 |
| PACK-02 | install.sh works correctly | smoke | `bash install.sh && ls ~/.claude/skills/drupal-* \| wc -l` | Wave 0 |
| PACK-03 | README documents inventory, install, usage | manual | Review README content | N/A -- manual |

### Sampling Rate
- **Per task commit:** Verify file counts and script execution
- **Per wave merge:** Run representative eval prompts
- **Phase gate:** All 13 skills documented in eval results, install.sh verified, README reviewed

### Wave 0 Gaps
- [ ] `install.sh` -- does not exist yet
- [ ] `README.md` -- does not exist yet
- [ ] Eval prompts document -- needs creation to track results

## Open Questions

1. **Eval methodology precision**
   - What we know: Project constraint says "use existing skill-creator eval loop." Skills are tested by prompting Claude with and without skills and comparing output quality.
   - What's unclear: Whether "eval loop" implies a specific tool/script from anthropics/skills repo or just the manual compare methodology.
   - Recommendation: Use the manual compare methodology. Write eval prompts, document baseline vs with-skills output quality, iterate on descriptions.

2. **Symlink vs copy default for install.sh**
   - What we know: Copies are more robust (survive repo moves). Symlinks keep skills auto-updated.
   - What's unclear: Which user workflow is more common.
   - Recommendation: Default to copy. Offer `--symlink` flag for power users.

3. **Uninstall story**
   - What we know: Users need a way to remove skills.
   - What's unclear: Whether to provide uninstall.sh or just document `rm -rf ~/.claude/skills/drupal-*`.
   - Recommendation: Document the rm command in README. A separate uninstall.sh is overkill for 13 files.

## Sources

### Primary (HIGH confidence)
- [Claude Code Skills Documentation](https://code.claude.com/docs/en/skills) - Comprehensive skill format, frontmatter, discovery, and installation
- [Skill Authoring Best Practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices) - Description writing, evaluation-driven development, progressive disclosure

### Secondary (MEDIUM confidence)
- Direct inspection of all 13 skill SKILL.md files in `skills/` directory
- Direct inspection of os-knowledge-garden custom modules for eval material
- [anthropics/skills repo](https://github.com/anthropics/skills) - Reference implementation of skill-creator

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Official Claude Code docs clearly define skill format and paths
- Architecture: HIGH - Eval-driven optimization is recommended by official best practices
- Pitfalls: HIGH - Based on official docs warnings about under/over-triggering and description best practices
- Packaging: HIGH - Standard bash scripting patterns for developer tool distribution

**Research date:** 2026-03-05
**Valid until:** 2026-04-05 (Claude Skills format is stable)
