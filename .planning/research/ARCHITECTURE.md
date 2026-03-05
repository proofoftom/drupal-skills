# Architecture Research

**Domain:** Claude skills collection for Drupal module development
**Researched:** 2026-03-05
**Confidence:** HIGH

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                    GitHub Repository (skills/)                       │
├─────────────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ drupal-module-    │  │ drupal-routing-   │  │ drupal-entities- │  │
│  │ scaffold/         │  │ controllers/      │  │ fields/          │  │
│  │ ├─ SKILL.md      │  │ ├─ SKILL.md      │  │ ├─ SKILL.md      │  │
│  │ └─ references/   │  │ └─ references/   │  │ └─ references/   │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ drupal-forms-api/ │  │ drupal-plugins-  │  │ drupal-config-   │  │
│  │ └─ SKILL.md      │  │ blocks/          │  │ storage/         │  │
│  │                   │  │ └─ SKILL.md      │  │ ├─ SKILL.md      │  │
│  │                   │  │                   │  │ └─ references/   │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ drupal-access-   │  │ drupal-theming/  │  │ drupal-caching/  │  │
│  │ security/        │  │ ├─ SKILL.md      │  │ └─ SKILL.md      │  │
│  │ └─ SKILL.md      │  │ └─ references/   │  │                   │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ drupal-testing/  │  │ drupal-database- │  │ drupal-views-dev/│  │
│  │ └─ SKILL.md      │  │ api/             │  │ └─ SKILL.md      │  │
│  │                   │  │ └─ SKILL.md      │  │                   │  │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  │
│                                                                     │
│  ┌──────────────────┐                                               │
│  │ drupal-batch-    │  ┌──────────────────────────────────────┐    │
│  │ queue-cron/      │  │ shared/                              │    │
│  │ ├─ SKILL.md      │  │ ├─ drupal-module-anatomy.md          │    │
│  │ └─ references/   │  │ └─ drupal-coding-standards.md        │    │
│  └──────────────────┘  └──────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────────────────┤
│                    Installation target                               │
│                    ~/.claude/skills/drupal-*                         │
└─────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | File Structure |
|-----------|----------------|----------------|
| `drupal-module-scaffold` | Module creation, .info.yml, directory layout, Composer, D10/D11 differences | SKILL.md only (foundational, no references needed) |
| `drupal-routing-controllers` | Routes, controllers, services, DI, menu links | SKILL.md + references/menus.md |
| `drupal-forms-api` | Form API, form altering, validation, submit handlers | SKILL.md only |
| `drupal-plugins-blocks` | Plugin system, Block plugins, custom plugin types | SKILL.md only |
| `drupal-entities-fields` | Content/config entities, TypedData, custom fields, field types | SKILL.md + references/files-images.md |
| `drupal-config-storage` | Config API, State API, TempStore, config schemas | SKILL.md + references/i18n.md |
| `drupal-access-security` | Permissions, access control, CSRF, input sanitization | SKILL.md only |
| `drupal-theming` | Render arrays, Twig templates, theme hooks, libraries | SKILL.md + references/js-ajax.md |
| `drupal-caching` | Cache tags, contexts, max-age, cache backends, render caching | SKILL.md only |
| `drupal-testing` | PHPUnit, Kernel tests, Functional tests, BrowserTestBase | SKILL.md only |
| `drupal-database-api` | Database abstraction, queries, schema API, migrations | SKILL.md only |
| `drupal-views-dev` | Views plugins (fields, filters, arguments, relationships) | SKILL.md only |
| `drupal-batch-queue-cron` | Batch API, Queue API, cron hooks, logging, mail | SKILL.md + references/logging-mail-tokens.md |
| `shared/` | Common reference files used by multiple skills | Shared markdown files |

## Recommended Project Structure

```
drupal-skills/
├── .planning/                     # GSD planning files
│   ├── PROJECT.md
│   └── research/
├── skills/                        # Published skills (GitHub source of truth)
│   ├── shared/                    # Shared reference material
│   │   ├── drupal-module-anatomy.md    # Standard module file layout
│   │   └── drupal-coding-standards.md  # Drupal CS + naming conventions
│   ├── drupal-module-scaffold/
│   │   └── SKILL.md
│   ├── drupal-routing-controllers/
│   │   ├── SKILL.md
│   │   └── references/
│   │       └── menus.md
│   ├── drupal-forms-api/
│   │   └── SKILL.md
│   ├── drupal-plugins-blocks/
│   │   └── SKILL.md
│   ├── drupal-entities-fields/
│   │   ├── SKILL.md
│   │   └── references/
│   │       └── files-images.md
│   ├── drupal-config-storage/
│   │   ├── SKILL.md
│   │   └── references/
│   │       └── i18n.md
│   ├── drupal-access-security/
│   │   └── SKILL.md
│   ├── drupal-theming/
│   │   ├── SKILL.md
│   │   └── references/
│   │       └── js-ajax.md
│   ├── drupal-caching/
│   │   └── SKILL.md
│   ├── drupal-testing/
│   │   └── SKILL.md
│   ├── drupal-database-api/
│   │   └── SKILL.md
│   ├── drupal-views-dev/
│   │   └── SKILL.md
│   └── drupal-batch-queue-cron/
│       ├── SKILL.md
│       └── references/
│           └── logging-mail-tokens.md
├── evals/                         # Skill-creator eval configs
│   └── evals.json
├── os-knowledge-garden/           # Test project (existing)
└── install.sh                     # Script to symlink/copy skills to ~/.claude/skills/
```

### Structure Rationale

- **skills/ as top-level:** Each skill is a self-contained directory. This mirrors the `~/.claude/skills/` installation target and makes GitHub publishing straightforward (users clone and copy).
- **shared/ alongside skills:** Shared reference files live outside individual skill directories. Skills reference them via relative paths. This avoids duplication of common Drupal knowledge (module anatomy, coding standards) that multiple skills need.
- **references/ inside skills:** Skill-specific reference files (menus, i18n, js-ajax) live inside the skill that owns them. These are "thin chapter" content that supplements the main SKILL.md but is only relevant to that skill's domain.
- **evals/ at root:** Eval configs span all skills and belong to the project, not individual skills.
- **install.sh:** Bridges `skills/` (repo) and `~/.claude/skills/` (runtime). Handles copying or symlinking, resolving shared references.

## Skill Dependency Graph

This is the critical architectural decision: which skills reference which, and in what order must they be built?

### Cross-Reference Map

```
                    drupal-module-scaffold
                    /    |    \        \
                   /     |     \        \
                  v      v      v        v
    drupal-routing-  drupal-  drupal-    drupal-config-
    controllers      forms-   entities-  storage
         |           api      fields        |
         |            |       / |  \        |
         |            |      /  |   \       |
         v            v     v   v    v      v
    drupal-plugins-blocks   |  drupal-   drupal-access-
         |                  |  theming   security
         |                  |     |         |
         v                  v     v         v
    drupal-views-dev   drupal-  drupal-   drupal-
                       testing  caching   batch-queue-cron
                                            |
                                            v
                                       drupal-database-api
```

### Dependency Table (What Each Skill References)

| Skill | References (cross-links to) | Referenced By |
|-------|----------------------------|---------------|
| `drupal-module-scaffold` | (none -- foundational) | All 12 other skills |
| `drupal-routing-controllers` | scaffold | forms, plugins-blocks, access-security, theming |
| `drupal-forms-api` | scaffold, routing | config-storage, testing |
| `drupal-entities-fields` | scaffold | plugins-blocks, config-storage, views-dev, theming, database-api, testing, access-security |
| `drupal-plugins-blocks` | scaffold, routing, entities | views-dev, theming |
| `drupal-config-storage` | scaffold, entities | batch-queue-cron, testing |
| `drupal-access-security` | scaffold, routing, entities | testing |
| `drupal-theming` | scaffold, routing, entities, plugins-blocks | caching |
| `drupal-caching` | scaffold, theming | (terminal -- referenced for cache tag awareness by others inline) |
| `drupal-testing` | scaffold, entities, forms, config-storage, routing | (terminal) |
| `drupal-database-api` | scaffold, entities | batch-queue-cron, views-dev |
| `drupal-views-dev` | scaffold, entities, plugins-blocks, database-api | (terminal) |
| `drupal-batch-queue-cron` | scaffold, config-storage, database-api | (terminal) |

### Cross-Reference Strategy

Skills should cross-reference each other in the SKILL.md body using a consistent pattern:

```markdown
## Related Skills

When building [specific thing], also consult:
- **drupal-module-scaffold** for initial module setup
- **drupal-routing-controllers** for defining routes that use this form
```

This is guidance text, not a file import. Claude's skill loading system loads skills individually based on trigger descriptions. Cross-references tell Claude "you should also check that other skill" rather than importing content.

**Key principle:** Cross-references are advisory, not dependencies. Each skill must be self-contained enough to produce correct code on its own. Cross-references improve the result when multiple skills are relevant to a task.

## Architectural Patterns

### Pattern 1: Thin SKILL.md + Reference File Spillover

**What:** Keep SKILL.md under 500 lines (loaded into context when triggered). Move secondary/adjacent content into `references/` files that SKILL.md points to with clear "when to read" guidance.

**When to use:** When a skill covers a primary domain plus related sub-domains (e.g., routing + menus, theming + JS/Ajax, batch processing + logging/mail).

**Trade-offs:** Adds a read step for the LLM but keeps context window lean. The alternative (cramming everything into SKILL.md) risks exceeding the 500-line guideline and diluting focus.

**Example:**
```markdown
## JavaScript and Ajax (Advanced)

For JavaScript behaviors, Ajax forms, and Ajax commands, read
[references/js-ajax.md](references/js-ajax.md). Consult this when:
- Adding Drupal.behaviors JavaScript
- Building Ajax-enabled forms or links
- Using Ajax commands (ReplaceCommand, InvokeCommand, etc.)
```

### Pattern 2: Shared References for Common Knowledge

**What:** Extract knowledge that multiple skills need into a `shared/` directory. Individual skills reference these with explicit read-when guidance.

**When to use:** When 3+ skills would otherwise duplicate the same content. Candidates:
- **Module file anatomy** (every skill needs to know about .info.yml, .module, .services.yml, etc.)
- **Drupal coding standards** (naming conventions, PHP version requirements, deprecation patterns)

**Trade-offs:** Creates a dependency on shared files during installation. The install script must resolve these references. Keeps total package size smaller and ensures consistency.

**What NOT to share:** Domain-specific patterns. If only 1-2 skills use it, keep it local. Sharing too aggressively creates coupling and makes individual skills harder to understand in isolation.

### Pattern 3: D10 Baseline with D11 Callouts

**What:** All code examples use Drupal 10 patterns (matching the book). D11 differences are noted inline with a consistent marker pattern.

**When to use:** Every skill. This is a project-wide convention.

**Example:**
```markdown
## Block Plugin

### D10 (Annotations)
```php
/**
 * @Block(
 *   id = "my_block",
 *   admin_label = @Translation("My Block"),
 * )
 */
```

### D11+ (PHP Attributes)
```php
#[Block(
  id: 'my_block',
  admin_label: new TranslatableMarkup('My Block'),
)]
```
```

### Pattern 4: Test Prompts Grounded in os-knowledge-garden

**What:** Each skill's eval test prompts should reference realistic tasks from the `os-knowledge-garden` project (routes, services, blocks, event subscribers, templates, Search API processors).

**When to use:** During eval creation for every skill.

**Trade-offs:** Ties evals to a specific project, but that project exercises most Drupal patterns. Ensures skills produce code that works in a real context, not just abstract examples.

## Data Flow

### Skill Creation Flow

```
Book Chapter(s)
    |
    v
Extract patterns, APIs, code examples
    |
    v
SKILL.md (< 500 lines)
    |
    ├──> references/*.md (secondary content)
    └──> Cross-reference annotations to other skills
    |
    v
Eval test prompts (grounded in os-knowledge-garden)
    |
    v
skill-creator eval loop (with-skill vs baseline)
    |
    v
Iterate SKILL.md based on feedback
    |
    v
Description optimization (run_loop.py)
```

### Skill Installation Flow

```
GitHub repo (skills/)
    |
    v
install.sh
    |
    ├──> Copy skill dirs to ~/.claude/skills/
    ├──> Resolve shared/ references (copy into each skill or symlink)
    └──> Verify structure
    |
    v
Claude Code loads skill metadata (name + description)
    |
    v
User prompt matches description → SKILL.md body loaded
    |
    v
SKILL.md references/ → Claude reads on-demand
```

### Key Data Flows

1. **Book-to-skill extraction:** Book chapters map to skills 1:N (some chapters split across skills, some skills combine chapters). The mapping table in PROJECT.md is the authoritative source.
2. **Cross-skill references:** Advisory only. Claude may load multiple skills for a complex task (e.g., "create a custom entity with access control" triggers entities-fields AND access-security).
3. **Shared references:** Loaded on-demand when a skill's SKILL.md instructs Claude to read them. Not auto-loaded with the skill.

## Reference File Sharing Strategy

### Decision: Inline Over Share for Most Content

After analyzing the 13 skills, **most reference content should be skill-local** (in the skill's own `references/` directory), not shared. Here's why:

1. **Skill-creator anatomy expects self-contained skills.** Each skill directory should work independently when copied to `~/.claude/skills/`.
2. **Shared references create installation complexity.** Symlinks break on Windows. Copying duplicates defeats the purpose. Path resolution differs between repo and installed locations.
3. **Only 2 candidates genuinely warrant sharing:** module file anatomy and coding standards. Everything else is domain-specific.

### Recommendation

| Content | Strategy | Rationale |
|---------|----------|-----------|
| Module file anatomy (.info.yml, .module, src/ layout) | **Embed in drupal-module-scaffold SKILL.md** | This IS the scaffold skill's primary content. Other skills cross-reference the scaffold skill rather than reading a shared file. |
| Drupal coding standards | **Embed as a brief section in drupal-module-scaffold** | 10-15 lines of naming conventions. Not worth a separate file. |
| Menu system (Ch 5) | **references/menus.md in drupal-routing-controllers** | Only routing skill needs detailed menu content |
| File/image handling (Ch 16) | **references/files-images.md in drupal-entities-fields** | Only entity skill needs file field details |
| i18n patterns (Ch 13) | **references/i18n.md in drupal-config-storage** | Only config skill needs translation API details |
| JS/Ajax (Ch 12) | **references/js-ajax.md in drupal-theming** | Only theming skill needs JS behavior details |
| Logging/mail/tokens (Ch 3) | **references/logging-mail-tokens.md in drupal-batch-queue-cron** | Only batch/cron skill needs logging context |

### Revised Structure (No shared/ Directory)

```
skills/
├── drupal-module-scaffold/
│   └── SKILL.md                        # Includes module anatomy + coding standards
├── drupal-routing-controllers/
│   ├── SKILL.md
│   └── references/menus.md
├── drupal-forms-api/
│   └── SKILL.md
├── drupal-plugins-blocks/
│   └── SKILL.md
├── drupal-entities-fields/
│   ├── SKILL.md
│   └── references/files-images.md
├── drupal-config-storage/
│   ├── SKILL.md
│   └── references/i18n.md
├── drupal-access-security/
│   └── SKILL.md
├── drupal-theming/
│   ├── SKILL.md
│   └── references/js-ajax.md
├── drupal-caching/
│   └── SKILL.md
├── drupal-testing/
│   └── SKILL.md
├── drupal-database-api/
│   └── SKILL.md
├── drupal-views-dev/
│   └── SKILL.md
└── drupal-batch-queue-cron/
    ├── SKILL.md
    └── references/logging-mail-tokens.md
```

Each skill is fully self-contained. Cross-referencing happens through advisory text in the SKILL.md body ("also consult drupal-module-scaffold for..."), not through file imports.

## Suggested Build Order

The wave structure from PROJECT.md is sound, but the ordering within waves matters for cross-referencing quality.

### Wave 1: Foundations (build first, all others reference these)

| Order | Skill | Why First |
|-------|-------|-----------|
| 1.1 | `drupal-module-scaffold` | Every other skill assumes module structure exists. Must define the canonical file layout, .info.yml patterns, and directory structure that all skills reference. |
| 1.2 | `drupal-routing-controllers` | Routes + services + DI are the second most fundamental concept. Forms, blocks, access control, and theming all build on routes. |
| 1.3 | `drupal-entities-fields` | Entities are Drupal's core data model. Config, access, views, theming, and database skills all reference entity patterns. |

**Why this sub-order:** Scaffold must come first because routing and entities both reference module setup. Routing and entities can be parallel (no mutual dependency at the skill level), but routing is simpler and a good pattern-setter.

### Wave 2: Core Patterns (depend on Wave 1)

| Order | Skill | Dependencies |
|-------|-------|-------------|
| 2.1 | `drupal-forms-api` | Needs scaffold (module structure) and routing (form routes). |
| 2.2 | `drupal-plugins-blocks` | Needs scaffold and routing. References entity patterns. |
| 2.3 | `drupal-config-storage` | Needs scaffold. Heavy entity cross-references (config entities). |
| 2.4 | `drupal-access-security` | Needs scaffold and routing. References entity access. |

**Note:** Forms was Wave 1 in PROJECT.md but moved to Wave 2 here. Forms depend on routing (form controllers, form routes) and benefit from having the routing skill finalized first. However, forms are simple enough to move back to Wave 1 if the team prefers parallelism.

### Wave 3: Presentation and Quality (depend on Waves 1-2)

| Order | Skill | Dependencies |
|-------|-------|-------------|
| 3.1 | `drupal-theming` | Needs routing (render in controllers), entities (entity display), plugins-blocks (block rendering). |
| 3.2 | `drupal-caching` | Needs theming (render cache), entities (cache tags on entities). |
| 3.3 | `drupal-testing` | Needs all Wave 1-2 skills (tests exercise routes, forms, entities, config). |
| 3.4 | `drupal-database-api` | Needs scaffold. Light entity cross-reference (schema vs entity storage). |

### Wave 4: Advanced Patterns (depend on earlier waves)

| Order | Skill | Dependencies |
|-------|-------|-------------|
| 4.1 | `drupal-views-dev` | Needs entities (entity data), plugins-blocks (Views plugin system), database-api (query alterations). |
| 4.2 | `drupal-batch-queue-cron` | Needs config-storage (state API), database-api (batch operations on data). |

## Packaging Strategy for GitHub Publishing

### Repository Layout

The repository IS the package. Users install by cloning and copying:

```bash
git clone https://github.com/<user>/drupal-skills.git
cp -r drupal-skills/skills/drupal-* ~/.claude/skills/
```

### install.sh Script

```bash
#!/bin/bash
# Install all Drupal skills to ~/.claude/skills/
SKILL_DIR="$HOME/.claude/skills"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

mkdir -p "$SKILL_DIR"

for skill in "$SCRIPT_DIR"/skills/drupal-*/; do
  skill_name=$(basename "$skill")
  echo "Installing $skill_name..."
  cp -r "$skill" "$SKILL_DIR/$skill_name"
done

echo "Installed $(ls -d "$SCRIPT_DIR"/skills/drupal-*/ | wc -l) Drupal skills to $SKILL_DIR"
```

### Selective Installation

Some users may only want specific skills. The self-contained structure supports this:

```bash
# Install only entity and routing skills
cp -r skills/drupal-entities-fields ~/.claude/skills/
cp -r skills/drupal-routing-controllers ~/.claude/skills/
```

Cross-references degrade gracefully -- if a referenced skill is not installed, Claude simply does not have that additional context. No errors, just slightly less comprehensive output.

## Anti-Patterns

### Anti-Pattern 1: Shared File Imports

**What people do:** Create a `shared/` directory with common content and have skills import it.
**Why it's wrong:** Breaks skill self-containment. Installation requires resolving paths. Different behavior in repo vs installed location.
**Do this instead:** Each skill is fully self-contained. Common knowledge goes in `drupal-module-scaffold` which other skills cross-reference by name (advisory text, not file imports).

### Anti-Pattern 2: Monolithic Skills

**What people do:** Cram an entire Drupal domain into one massive SKILL.md (800+ lines).
**Why it's wrong:** Exceeds the 500-line guideline. Floods Claude's context with irrelevant content when only part of the domain is needed.
**Do this instead:** Keep SKILL.md focused on the primary domain. Move secondary content to `references/` with clear "when to read" guidance. Claude reads reference files on demand.

### Anti-Pattern 3: Tight Cross-Skill Coupling

**What people do:** Skill A's instructions assume Skill B is loaded and available.
**Why it's wrong:** Users may install skills selectively. Skills trigger independently based on prompt matching.
**Do this instead:** Each skill must produce correct code independently. Cross-references are additive ("for better results, also consult X"), never required.

### Anti-Pattern 4: Book-Verbatim Content

**What people do:** Copy large blocks of book text verbatim into skills.
**Why it's wrong:** Wastes context window on prose. Skills should be distilled, actionable instructions, not textbook chapters.
**Do this instead:** Extract patterns, APIs, and code examples. Write imperative instructions ("Use X when Y"). Include code snippets, not explanatory paragraphs.

## Integration Points

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Skill A ↔ Skill B | Advisory cross-reference in SKILL.md text | No file-level dependency |
| SKILL.md ↔ references/ | Explicit read guidance with conditions | Claude reads on demand |
| skills/ ↔ ~/.claude/skills/ | Copy via install.sh | One-directional (repo → installed) |
| skills/ ↔ evals/ | Eval configs reference skill paths | Evals are project-level, not per-skill |

### External Integration

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| skill-creator eval loop | `evals/evals.json` per skill, workspace in temp dir | Uses existing infrastructure |
| os-knowledge-garden | Test prompts reference real module tasks | Not a runtime dependency |
| GitHub | Repository is the distribution format | Users clone + copy |
| Claude Code | Skills installed to `~/.claude/skills/` | Trigger via description matching |

## Sources

- Skill-creator anatomy: `/home/proofoftom/.claude/plugins/marketplaces/claude-plugins-official/plugins/skill-creator/skills/skill-creator/SKILL.md` (HIGH confidence, primary source)
- Skills reference: `/home/proofoftom/.claude/plugins/marketplaces/claude-plugins-official/plugins/claude-code-setup/skills/claude-automation-recommender/references/skills-reference.md` (HIGH confidence)
- Existing skill example: `/home/proofoftom/.claude/skills/context7/SKILL.md` (HIGH confidence)
- Project plan: `/home/proofoftom/Code/drupal-skills/polished-tickling-owl.md` (HIGH confidence, project-specific)
- Test project structure: `/home/proofoftom/Code/drupal-skills/os-knowledge-garden/` (HIGH confidence, direct observation)

---
*Architecture research for: Claude skills for Drupal module development*
*Researched: 2026-03-05*
