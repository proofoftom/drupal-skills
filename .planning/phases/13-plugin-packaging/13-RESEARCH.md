# Phase 13: Plugin Packaging - Research

**Researched:** 2026-03-07
**Domain:** Claude Code plugin system, skill auto-triggering, plugin manifest authoring
**Confidence:** HIGH

## Summary

Phase 13 converts the existing `drupal-skills` repository into a Claude Code plugin by adding a `.claude-plugin/plugin.json` manifest, writing a minimal CLAUDE.md, deprecating `install.sh`, and validating that skills auto-trigger from natural Drupal development prompts. This is structurally simple -- the repo already matches Claude Code's expected plugin layout with `skills/drupal-*/SKILL.md` at the root level. The only new file required is the plugin manifest; all 14 existing skills remain untouched in their current locations.

The primary risk is not the packaging itself but the auto-triggering validation (EVAL-01). Skills must activate from natural prompts at >80% rate without explicit `/drupal-skills:skill-name` invocation. Current skill descriptions already follow the imperative directive pattern ("Use WHENEVER...", "Do NOT use for...") that official docs recommend. Total description budget usage is approximately 6,478 characters across 14 skills, well within the 16,000-character fallback budget (2% of context window). The main unknowns are: (1) whether plugin-namespaced skills auto-trigger differently than standalone skills, and (2) whether `--plugin-dir` works with headless `-p` mode for eval compatibility.

**Primary recommendation:** Create `.claude-plugin/plugin.json` with minimal metadata, write a concise developer-authored CLAUDE.md at repo root, update `install.sh` with deprecation notice and migration instructions, then empirically validate auto-triggering with 10+ natural Drupal prompts before proceeding to Phase 14.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| PLUG-01 | Plugin manifest (.claude-plugin/plugin.json) registers all 14 skills with correct namespace | Plugin directory structure verified from official docs -- `skills/` at root is auto-discovered, manifest just needs name/description/version fields |
| PLUG-02 | Skill descriptions optimized for auto-triggering from natural Drupal development prompts (>80% activation rate) | Current descriptions already use imperative directive pattern per best practices; total budget ~6,478/16,000 chars; empirical validation needed |
| PLUG-03 | Minimal CLAUDE.md at plugin root with only non-obvious, project-specific rules (developer-written, not LLM-generated) | Official docs confirm CLAUDE.md is loaded alongside skills; must contain only cross-cutting rules not covered by individual skills |
| PLUG-04 | install.sh deprecated with migration path documented for plugin-based installation | Current install.sh copies to ~/.claude/skills/; must add deprecation notice, --uninstall flag, and dual-install detection |
| EVAL-01 | Auto-trigger validation confirming skills activate from natural development prompts with plugin installed | Need empirical test with 10+ natural prompts; can use `claude --plugin-dir .` for local testing; check `/context` for skill visibility |
</phase_requirements>

## Standard Stack

### Core
| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| Claude Code | >= 1.0.33 | Plugin host platform | Plugin support including `/plugin` command requires this minimum version |
| `.claude-plugin/plugin.json` | N/A | Plugin manifest | Required file for Claude Code to recognize a directory as a plugin |
| `skills/*/SKILL.md` | Agent Skills standard | Skill definitions | Open standard format with YAML frontmatter; already used by existing skills |

### Supporting
| Component | Purpose | When to Use |
|-----------|---------|-------------|
| `--plugin-dir` flag | Local plugin testing | During development -- loads plugin without installation to cache |
| `/context` command | Debug skill loading | Verify all 14 skills appear in available skills list |
| `claude --debug` | Debug plugin loading | See plugin registration and any loading errors |
| `SLASH_COMMAND_TOOL_CHAR_BUDGET` env var | Override description budget | Only if skills are being excluded from context (unlikely at 6,478 chars) |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Plugin distribution | Keep install.sh only | Loses namespacing, versioning, marketplace distribution; not viable for v3.0 goals |
| Marketplace distribution | Direct `--plugin-dir` | Simpler but requires manual path management; use marketplace for public release later |

**Installation (for testing):**
```bash
claude --plugin-dir /home/proofoftom/Code/drupal-skills
```

**Installation (for distribution, later):**
```bash
claude plugin install drupal-skills@<marketplace>
```

## Architecture Patterns

### Plugin Directory Structure (Target)
```
drupal-skills/                          # Plugin root
  .claude-plugin/
    plugin.json                         # Plugin manifest (NEW)
  skills/                               # UNCHANGED -- already correct location
    drupal-access-security/
      SKILL.md
      evals/evals.json
      references/
    drupal-batch-queue-cron/
      SKILL.md
      evals/evals.json
    drupal-caching/
      SKILL.md
      evals/evals.json
      references/
    drupal-coding-standards/
      SKILL.md
    drupal-config-storage/
      SKILL.md
      evals/evals.json
      references/
    drupal-database-api/
      SKILL.md
      evals/evals.json
    drupal-entities-fields/
      SKILL.md
      evals/evals.json
    drupal-forms-api/
      SKILL.md
      evals/evals.json
    drupal-module-scaffold/
      SKILL.md
      evals/evals.json
      references/
    drupal-plugins-blocks/
      SKILL.md
      evals/evals.json
      references/
    drupal-routing-controllers/
      SKILL.md
      evals/evals.json
      references/
    drupal-testing/
      SKILL.md
      evals/evals.json
    drupal-theming/
      SKILL.md
      evals/evals.json
      references/
    drupal-views-dev/
      SKILL.md
      evals/evals.json
  CLAUDE.md                             # NEW -- minimal cross-cutting rules
  install.sh                            # UPDATED -- deprecation notice + --uninstall
  README.md                             # UPDATED -- plugin install as primary method
  eval/                                 # NOT part of plugin (ignored by plugin system)
  .planning/                            # NOT part of plugin (ignored by plugin system)
  .claude/                              # NOT part of plugin (local agents, separate)
```

### Pattern 1: Minimal Plugin Manifest
**What:** Plugin.json contains only identity fields -- no custom component paths.
**When to use:** When skills are already in the default `skills/` location at plugin root.
**Why:** Claude Code auto-discovers components in default locations. Custom paths supplement but don't replace defaults. Since skills are already at `skills/drupal-*/SKILL.md`, no path overrides needed.

```json
// .claude-plugin/plugin.json
{
  "name": "drupal-skills",
  "description": "14 skills for generating correct Drupal 10/11 module code. Covers module scaffolding, entities, routing, forms, plugins, caching, theming, testing, Views, batch processing, access control, config management, database API, and coding standards.",
  "version": "3.0.0",
  "author": {
    "name": "proofoftom"
  },
  "repository": "https://github.com/proofoftom/drupal-skills",
  "license": "MIT",
  "keywords": ["drupal", "drupal-10", "drupal-11", "module-development", "php"]
}
```

Source: [Claude Code Plugins docs](https://code.claude.com/docs/en/plugins), [Plugins reference](https://code.claude.com/docs/en/plugins-reference)

### Pattern 2: Skill Auto-Triggering via Description
**What:** Claude loads all skill descriptions into context at session start (2% of context window budget). When a user prompt matches a description, Claude automatically loads the full skill content.
**When to use:** Default behavior for all skills that don't have `disable-model-invocation: true`.
**Key details:**
- Description budget: 2% of context window, ~16,000 chars fallback
- Current 14 skills: ~6,478 chars total -- well within budget
- Each skill description is under 1,024 chars (max per skill) -- all current descriptions are 277-504 chars
- Names become namespaced: `/drupal-skills:drupal-caching` instead of `/drupal-caching`
- Auto-triggering matches on description content, not skill name
- Complex multi-step prompts reliably trigger; simple one-step may not

Source: [Claude Code Skills docs](https://code.claude.com/docs/en/skills)

### Pattern 3: Developer-Authored CLAUDE.md
**What:** A minimal CLAUDE.md at plugin root containing only non-obvious cross-cutting rules that apply to ALL Drupal module development but are not covered by individual skills.
**When to use:** Always include with the plugin; loaded alongside skills.
**Constraint from MEMORY.md:** "CLAUDE.md: minimal, developer-written only (LLM-generated hurts performance per Gloaguen 2026)"

**What to include:**
- Drupal version compatibility rule (^10 || ^11 in .info.yml)
- PHP attribute syntax preference for D11 with annotation fallback
- Module naming convention (machine_name format)
- Cross-cutting rules that span multiple skills

**What NOT to include:**
- Anything already in individual SKILL.md files (duplication wastes context)
- LLM-generated boilerplate (empirically shown to hurt performance)
- Skill-specific patterns (those belong in the skill)

### Pattern 4: Deprecation with Migration Path
**What:** Update install.sh to print deprecation warning and provide clear migration to plugin-based installation.
**When to use:** For the existing install.sh that copies skills to `~/.claude/skills/`.

```bash
# At top of install.sh, after set -euo pipefail:
echo ""
echo "WARNING: install.sh is DEPRECATED."
echo "Use Claude Code's plugin system instead:"
echo ""
echo "  claude --plugin-dir /path/to/drupal-skills"
echo ""
echo "Or install from a marketplace:"
echo "  claude plugin install drupal-skills@<marketplace>"
echo ""
echo "To remove previously installed skills:"
echo "  ./install.sh --uninstall"
echo ""
echo "Continuing with legacy installation..."
echo ""
```

### Anti-Patterns to Avoid
- **Putting skills inside `.claude-plugin/`**: Only `plugin.json` goes inside `.claude-plugin/`. All component directories must be at plugin root.
- **Moving or restructuring skills**: Current `skills/drupal-*/SKILL.md` is already in the correct location. Do not move files.
- **LLM-generating CLAUDE.md content**: Empirically shown to hurt performance. Write minimal, developer-authored content only.
- **Modifying existing SKILL.md content**: Skills are locked from v2.0 -- changing content invalidates benchmarks.
- **Using absolute paths in plugin.json**: All paths must be relative, starting with `./`.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Plugin manifest | Custom discovery mechanism | `.claude-plugin/plugin.json` | Claude Code has a specific manifest format; anything else is silently ignored |
| Skill namespacing | Manual skill renaming | Plugin `name` field in manifest | Plugin name auto-prefixes all skills |
| Auto-trigger mechanism | Custom hooks for prompt matching | SKILL.md `description` field | Claude's built-in semantic matching handles this; hooks are a fallback only |
| Dual-install detection | Custom Python/bash script | Check `~/.claude/skills/drupal-*` existence | Simple directory existence check in install.sh is sufficient |

**Key insight:** The entire plugin packaging is an exercise in NOT building things. The repo already has the right structure. Adding one JSON file and a minimal CLAUDE.md is all the new code needed.

## Common Pitfalls

### Pitfall 1: Skills in Wrong Location Are Silently Ignored
**What goes wrong:** Plugin installs, manifest validates, but zero skills appear in `/context`. No error messages.
**Why it happens:** Skills placed inside `.claude-plugin/skills/` instead of `skills/` at plugin root, or plugin.json specifies wrong paths.
**How to avoid:** Keep skills at `skills/` (already correct). Verify with `claude --debug` and `/context` command. Ask "What skills are available?" to confirm all 14 load.
**Warning signs:** `/context` shows no drupal-* skills; `/help` doesn't list any plugin skills.

### Pitfall 2: Auto-Triggering Fails Silently
**What goes wrong:** Plugin loads, skills appear in context, but Claude never activates them from natural prompts. Eval shows 0% delta.
**Why it happens:** Descriptions are too passive ("Covers cache metadata") instead of directive ("Use WHENEVER producing render arrays"). Or prompts are too simple (Claude handles simple tasks without consulting skills).
**How to avoid:** Current descriptions already use imperative directives. Test with complex, multi-step prompts that match the "Use when..." language. If activation is low, review the description budget with `/context`.
**Warning signs:** Natural prompts like "create a custom entity type" don't trigger drupal-entities-fields; v3.0 eval shows 0% where v2.0 showed positive delta.

### Pitfall 3: Dual Installation Creates Duplicate Skills
**What goes wrong:** Users who previously ran `install.sh` now have skills in both `~/.claude/skills/drupal-*` (personal) and from the plugin. Context budget consumed by 28 descriptions instead of 14.
**Why it happens:** install.sh copies skills to personal scope; plugin adds them in plugin scope. Both are loaded. Namespacing prevents conflicts (`/drupal-caching` vs `/drupal-skills:drupal-caching`) but context budget is doubled.
**How to avoid:** Add `--uninstall` flag to install.sh that removes `~/.claude/skills/drupal-*`. Document migration in README. Optionally add a SessionStart hook that warns about dual installation.
**Warning signs:** `/context` shows skills appearing twice; excluded skills warnings from budget overflow.

### Pitfall 4: CLAUDE.md Duplicates Skill Content
**What goes wrong:** CLAUDE.md repeats patterns already in SKILL.md files. Claude loads both, wasting context and potentially getting conflicting instructions.
**Why it happens:** Natural tendency to put "important" rules in CLAUDE.md even when they're already in skills.
**How to avoid:** CLAUDE.md should contain ONLY cross-cutting rules not in any skill. Review each line against all 14 SKILL.md files to ensure no duplication.
**Warning signs:** Same pattern described in CLAUDE.md and a SKILL.md; conflicting guidance between the two.

### Pitfall 5: `--plugin-dir` May Not Work with `-p` (Headless Mode)
**What goes wrong:** The v2.0 eval pipeline uses headless `claude -p` for reproducible code generation. If `--plugin-dir` doesn't load plugins in headless mode, the entire v3.0 auto-trigger eval methodology breaks.
**Why it happens:** Headless mode skips some startup processes for performance. Official docs don't explicitly state whether `--plugin-dir` is compatible with `-p`.
**How to avoid:** Empirically test: run `claude --plugin-dir . -p "What skills are available?"` and check if plugin skills appear. If not, the eval methodology needs redesign (interactive sessions instead of headless).
**Warning signs:** Headless runs produce identical output with and without `--plugin-dir`.

## Code Examples

### Plugin Manifest (Verified)
```json
// Source: https://code.claude.com/docs/en/plugins-reference
// .claude-plugin/plugin.json
{
  "name": "drupal-skills",
  "description": "14 skills for generating correct Drupal 10/11 module code. Covers module scaffolding, entities, routing, forms, plugins, caching, theming, testing, Views, batch processing, access control, config management, database API, and coding standards.",
  "version": "3.0.0",
  "author": {
    "name": "proofoftom"
  },
  "repository": "https://github.com/proofoftom/drupal-skills",
  "license": "MIT",
  "keywords": ["drupal", "drupal-10", "drupal-11", "module-development", "php"]
}
```

### Minimal CLAUDE.md (Template)
```markdown
# Drupal Skills Plugin

When generating Drupal module code:
- Target compatibility: `core_version_requirement: ^10 || ^11`
- Prefer PHP attributes (D11) with annotation fallback shown for D10
- Machine names: lowercase with underscores (e.g., my_module)
- All code must pass `phpcs --standard=Drupal,DrupalPractice`
```

### install.sh Deprecation Banner
```bash
#!/usr/bin/env bash
set -euo pipefail

# --- Deprecation notice ---
echo ""
echo "============================================================"
echo "  DEPRECATED: install.sh is no longer the recommended"
echo "  installation method for Drupal Skills."
echo ""
echo "  Use Claude Code's plugin system instead:"
echo ""
echo "    claude --plugin-dir /path/to/drupal-skills"
echo ""
echo "  To uninstall previously installed skills:"
echo ""
echo "    ./install.sh --uninstall"
echo ""
echo "  See README.md for full migration instructions."
echo "============================================================"
echo ""
```

### Auto-Trigger Test Prompts (for EVAL-01)
```
# 10+ natural Drupal prompts to test auto-triggering
# Run each with: claude --plugin-dir . -p "<prompt>"
# or interactively: claude --plugin-dir .

1. "Create a new Drupal module called event_tracker that depends on node"
   -> Expected: drupal-module-scaffold activates

2. "Add a custom content entity type called Event with title, date, and location fields"
   -> Expected: drupal-entities-fields activates

3. "Create a route and controller that displays a JSON list of events"
   -> Expected: drupal-routing-controllers activates

4. "Build a settings form for the event_tracker module with a config schema"
   -> Expected: drupal-forms-api and drupal-config-storage activate

5. "Write a block plugin that shows upcoming events with proper caching"
   -> Expected: drupal-plugins-blocks and drupal-caching activate

6. "Make the events list vary by user role and invalidate when events change"
   -> Expected: drupal-caching activates

7. "Add a custom permission to restrict who can create events and protect the form against CSRF"
   -> Expected: drupal-access-security activates

8. "Create a Twig template for event cards with a CSS library"
   -> Expected: drupal-theming activates

9. "Expose the Event entity to Views with custom field and filter handlers"
   -> Expected: drupal-views-dev activates

10. "Write kernel tests for the Event entity CRUD operations"
    -> Expected: drupal-testing activates

11. "Add a cron hook to check for expired events and a queue worker to send notifications"
    -> Expected: drupal-batch-queue-cron activates

12. "Create a database table for event analytics with hook_schema and an update hook"
    -> Expected: drupal-database-api activates
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `install.sh` to `~/.claude/skills/` | Plugin via `.claude-plugin/plugin.json` | Claude Code 1.0.33+ (2025) | Namespacing, versioning, marketplace distribution |
| `.claude/commands/` directory | `skills/*/SKILL.md` with frontmatter | Merged in Claude Code | Skills add supporting files, auto-trigger, invocation control |
| Manual skill invocation only | Description-based auto-triggering | Current | Skills load automatically when description matches user prompt |

**Deprecated/outdated:**
- `install.sh` copy/symlink approach: Still works but superseded by plugin system
- `.claude/commands/` format: Merged into skills; existing files still work

## Open Questions

1. **`--plugin-dir` + `-p` compatibility**
   - What we know: `--plugin-dir` works for interactive sessions (verified in official docs)
   - What's unclear: Whether it works with headless `-p` mode for eval pipeline
   - Recommendation: Test empirically before designing eval methodology. If incompatible, EVAL-01 must use interactive sessions only.

2. **Plugin caching behavior with `--plugin-dir`**
   - What we know: Marketplace-installed plugins are cached to `~/.claude/plugins/cache/`; `--plugin-dir` bypasses cache
   - What's unclear: Whether changes to SKILL.md are picked up immediately or require restart
   - Recommendation: Test during development. Official docs say "restart Claude Code to pick up updates" for plugin changes.

3. **CLAUDE.md loading precedence**
   - What we know: CLAUDE.md at plugin root is loaded alongside skills
   - What's unclear: Whether plugin CLAUDE.md conflicts with or complements project-level CLAUDE.md files
   - Recommendation: Keep plugin CLAUDE.md minimal to avoid conflicts. Test with a project that has its own CLAUDE.md.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Manual validation (bash scripts + claude CLI) |
| Config file | none -- see Wave 0 |
| Quick run command | `claude --plugin-dir . -p "What Drupal skills are available?"` |
| Full suite command | `bash eval/v3/test-auto-trigger.sh` (to be created) |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PLUG-01 | Plugin manifest registers 14 skills | smoke | `claude --plugin-dir . -p "List all available skills"` then count drupal-skills: entries | N/A -- CLI-based |
| PLUG-02 | Auto-triggering >80% rate | integration | Run 12 test prompts, count skill activations, calculate rate | Wave 0: create test script |
| PLUG-03 | CLAUDE.md is minimal and non-redundant | manual | Code review: verify no duplication with SKILL.md content | N/A -- manual |
| PLUG-04 | install.sh deprecation works | smoke | `./install.sh --help` shows deprecation notice; `./install.sh --uninstall` removes skills | N/A -- CLI-based |
| EVAL-01 | Auto-trigger validation passes | integration | Same as PLUG-02 but with structured grading | Wave 0: create test script |

### Sampling Rate
- **Per task commit:** Verify plugin loads with `claude --plugin-dir .`
- **Per wave merge:** Run full 12-prompt auto-trigger suite
- **Phase gate:** All 14 skills visible in `/context`, >80% auto-trigger rate

### Wave 0 Gaps
- [ ] `eval/v3/test-auto-trigger.sh` -- script to run 12+ test prompts and measure activation rate
- [ ] Empirical test of `--plugin-dir` + `-p` compatibility -- determines eval methodology

## Sources

### Primary (HIGH confidence)
- [Claude Code Plugins documentation](https://code.claude.com/docs/en/plugins) -- Plugin quickstart, manifest creation, directory structure, --plugin-dir testing
- [Claude Code Plugins reference](https://code.claude.com/docs/en/plugins-reference) -- Complete plugin.json schema, component locations, debugging tools, common issues
- [Claude Code Skills documentation](https://code.claude.com/docs/en/skills) -- Skill frontmatter reference, auto-triggering behavior, description budget (2% context window), invocation control
- Existing `skills/drupal-*/SKILL.md` files -- Current description format and content (14 skills verified)

### Secondary (MEDIUM confidence)
- [Project research: SUMMARY.md](.planning/research/SUMMARY.md) -- Project-level stack and architecture decisions
- [Project research: ARCHITECTURE.md](.planning/research/ARCHITECTURE.md) -- Plugin directory structure and workstream overview
- [Project research: PITFALLS.md](.planning/research/PITFALLS.md) -- 11 pitfalls including plugin packaging specifics
- MEMORY.md -- v2.0 eval results, headless pipeline validation, CLAUDE.md authoring constraint

### Tertiary (LOW confidence)
- `--plugin-dir` + `-p` compatibility -- Not explicitly documented; needs empirical validation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- Plugin system fully documented in official docs; existing skill structure verified
- Architecture: HIGH -- Repo already matches expected layout; one file to add
- Pitfalls: HIGH -- Plugin packaging pitfalls well-documented; auto-trigger testing approach clear
- EVAL-01 methodology: MEDIUM -- Auto-trigger testing approach is sound but `--plugin-dir` + `-p` compatibility unverified

**Research date:** 2026-03-07
**Valid until:** 2026-04-07 (plugin system is stable, 30-day window appropriate)
