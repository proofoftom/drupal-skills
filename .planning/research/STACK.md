# Stack Research

**Domain:** Group-based project management module with AI/AI Agents integration + Claude Code plugin packaging
**Researched:** 2026-03-07
**Confidence:** MEDIUM — Drupal module versions verified via drupal.org; Claude Code plugin system verified via official docs; AI Agents custom agent patterns partially verified

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Drupal Core | ^10.4 \|\| ^11 | Base CMS framework | Common denominator of all contrib module requirements. 10.4+ needed because AI module 1.2.x requires ^10.4. PHP 8.1+ (D10) or 8.3+ (D11). |
| Group | 3.3.5 | Group entity framework -- projects, teams, workspaces | The standard for arbitrary entity grouping in Drupal. v3.x uses `GroupRelationship` entity (renamed from `GroupContent`), supports both content and config entities in groups. Fresh installs should always use 3.x over 2.x. |
| AI (Artificial Intelligence) | 1.2.11 (stable) | Unified AI abstraction layer | Provider-agnostic AI integration. All AI Agents functionality depends on this. Includes AI Core, AI Automators, AI Assistants API, AI CKEditor, AI Logging. Requires `drupal/key` for credential storage. |
| AI Agents | 1.2.3 (stable) | Agent framework with tool calling | Provides the agent + tool calling infrastructure for AI-powered project management. Ships with Field Type, Content Type, Taxonomy, Views, Module, and Webform agents. Custom agents created via admin UI or code (AIFunctionCall plugins). |
| AI Provider Anthropic | 1.2.1 | Claude model integration | Connects AI module to Anthropic Claude models. Required for Claude as the LLM backend for Drupal AI agents. Requires API key via Key module. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Key | ^1.18 | Secure API key storage | Always -- required by AI module for storing provider API keys (Anthropic, OpenAI, etc.) |
| AI Agents Explorer | (ships with ai_agents) | Agent testing UI | During development -- provides admin interface for testing agent configurations at /admin/config/ai/agents |
| AI Agents Extra | (ships with ai_agents) | Additional agent tools | When built-in tools are insufficient -- extends the tool set available to agents |
| AI Agents Form Integration | (ships with ai_agents) | Form-aware agent tools | When agents need to interact with Drupal forms |
| Inline Entity Form | ^3.0 | Editing related entities inline | If project management UI needs inline task creation within groups |
| Token | ^1.13 | Token replacement for templates | If AI prompts need dynamic token replacement from Drupal entities |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| DDEV | Local Drupal development environment | Already used in eval pipeline. Target: `--project-type=drupal --php-version=8.3` |
| Drush | CLI for Drupal operations | Module enable/disable, config export, entity queries, cache rebuild |
| PHPUnit | Drupal testing | Kernel and Functional tests for custom module code |
| phpcs + drupal/coder | Coding standards | Already set up via coding-standards skill |
| Claude Code | AI-assisted development | The tool we're building the plugin for |

### Claude Code Plugin Structure

| Component | Location | Purpose |
|-----------|----------|---------|
| Plugin manifest | `.claude-plugin/plugin.json` | Plugin identity, version, metadata |
| Skills | `skills/<skill-name>/SKILL.md` | 14 Drupal skills packaged as plugin skills |
| Supporting files | `skills/<skill-name>/references/` | Reference docs per skill (auto-discovered by Claude when referenced in SKILL.md) |

**Plugin manifest format** (verified from official docs):
```json
{
  "name": "drupal-skills",
  "description": "13 Drupal domain skills + coding-standards for Claude Code. Covers module scaffolding, routing, entities, forms, caching, testing, theming, and more for Drupal 10/11 module development.",
  "version": "1.0.0",
  "author": {
    "name": "proofoftom"
  },
  "repository": "https://github.com/proofoftom/drupal-skills",
  "license": "MIT",
  "keywords": ["drupal", "drupal-10", "drupal-11", "module-development", "php"]
}
```

**Skill invocation after plugin install:** `/drupal-skills:drupal-caching`, `/drupal-skills:drupal-routing-controllers`, etc.

**Auto-triggering:** Skills with good `description` fields in SKILL.md frontmatter will auto-trigger when Claude detects relevant context. This is the key v3.0 eval metric -- skills must trigger from natural prompts without explicit `/skill-name` invocation. Claude loads skill descriptions into context (2% of context window budget), then loads full skill content when it decides a skill is relevant.

## Installation

```bash
# Drupal project setup (via DDEV)
ddev config --project-type=drupal --php-version=8.3
ddev start
ddev composer create drupal/recommended-project

# Core contrib modules for the Group PM module
ddev composer require 'drupal/group:^3.3'
ddev composer require 'drupal/ai:^1.2'
ddev composer require 'drupal/ai_agents:^1.2'
ddev composer require 'drupal/ai_provider_anthropic:^1.2'
ddev composer require 'drupal/key:^1.18'

# Development dependencies
ddev composer require --dev drupal/coder squizlabs/php_codesniffer

# Enable modules
ddev drush en group ai ai_agents ai_provider_anthropic key -y

# Claude Code plugin (local testing during development)
claude --plugin-dir /home/proofoftom/Code/drupal-skills
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| Group 3.3.x | Organic Groups (og) | Never for new projects -- OG is legacy, Group is the modern standard |
| Group 3.3.x | Group 2.3.x | Only if migrating from Group 1.x (2.x has migration path from 1.x, 3.x from 2.x via 3.3.1+) |
| AI module 1.2.x | AI module 1.3.0-rc2 | Only if you need Drupal ^10.5 \|\| ^11.2 features and are willing to use RC |
| AI Agents 1.2.x | AI Agents 1.3.0-beta2 | Only if you need bleeding-edge agent features and accept beta instability |
| AI Provider Anthropic | AI Provider OpenAI | If project requires GPT models instead of Claude. Both work through AI module abstraction layer. |
| Custom entities for tasks | Content types (nodes) for tasks | If simplicity matters more than clean entity design. Nodes work but custom entities are more appropriate for non-content data like tasks/milestones. |
| Claude Code plugin | `~/.claude/skills/` install via install.sh | For personal use only. Plugin is for distribution; `install.sh --symlink` remains for local dev convenience. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Drupal PM module | No stable D10/D11 release, last dev update Nov 2024, no active maintainer | Build custom entities on Group module |
| Group 1.x (8.x-1.x) | Security fixes only, deprecated GroupContent API | Group 3.3.x with GroupRelationship API |
| AI module 1.1.x | Older stable branch, missing features in 1.2.x, requires only ^10.2 but lacks newer APIs | AI 1.2.11 (stable) |
| Direct Anthropic SDK calls | Bypasses AI module abstraction, tightly couples to single provider | AI module provider plugin system |
| `.claude/commands/` directory | Legacy format, merged into skills but skills preferred for new work | `skills/<name>/SKILL.md` format |
| Hardcoded API keys in settings.php | Security risk, breaks deployment portability | Key module with file-based or environment provider |
| AI module 1.3.x | RC/beta quality, not covered by security advisory team yet | AI 1.2.11 (stable, security-covered) |
| Separate marketplace repo | Over-engineering for initial release | Submit directly to anthropics/claude-plugins-official after validation |

## Stack Patterns by Variant

**If building the Group PM module as a standalone contrib:**
- Use Group 3.3.x as the base, define custom group types (Project, Sprint)
- Define custom content entities (Task, Milestone) with GroupRelation plugins in `src/Plugin/Group/Relation/`
- AI Agents integration via custom AIFunctionCall plugins that operate within group scope
- Keep AI module as a soft dependency (module works without it, enhanced with it)
- Export default config in `config/install/` and `config/optional/` (optional for AI-dependent config)

**If packaging skills as a Claude Code plugin:**
- Add `.claude-plugin/plugin.json` manifest at repo root
- Keep `skills/` directory at repo root (already correct location for both plugin and install.sh)
- Skills auto-discovered from `skills/<name>/SKILL.md` when plugin enabled
- Plugin namespace: all skills become `/drupal-skills:<skill-name>`
- `references/` and `evals/` subdirectories remain -- Claude reads references when SKILL.md links them; evals/ ignored by plugin system
- Install command: `/plugin install drupal-skills@<marketplace>` or `claude --plugin-dir ./`

**If evaluating skill auto-triggering (v3.0 eval):**
- Install plugin via `claude --plugin-dir /path/to/drupal-skills` for local testing
- Use natural language prompts (no `/drupal-skills:skill-name` invocation)
- Compare: plugin-installed vs no-plugin baseline
- Key metric: does Claude auto-load the right skill from its description field?
- Budget awareness: skill descriptions consume 2% of context window. With 14 skills, each description should be concise.

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| drupal/group:^3.3 | drupal/core:^10.3 \|\| ^11 | Requires PHP 8.1+ (D10) or 8.3+ (D11) |
| drupal/ai:^1.2 | drupal/core:^10.4 \|\| ^11 | Requires drupal/key. **Bottleneck**: highest core minimum (10.4 vs 10.3 for others) |
| drupal/ai_agents:^1.2 | drupal/core:^10.3 \|\| ^11 | Requires drupal/ai (version constraint inferred from ecosystem) |
| drupal/ai_provider_anthropic:^1.2 | drupal/core:^10.3 \|\| ^11 | Requires drupal/ai. API key via Key module. |
| Claude Code plugin system | Claude Code >= 1.0.33 | Plugin support including `/plugin` command. Skills require SKILL.md with YAML frontmatter. |

**Effective minimum Drupal core:** 10.4 (bottleneck is AI module 1.2.x requiring ^10.4).

**Recommended target:** Drupal 11 with PHP 8.3 -- all modules support it, best forward compatibility, simplest dependency resolution.

## Key Integration Points

### Group <-> AI Agents Integration

The Group module provides the entity framework (projects, tasks as group relationships). AI Agents provides the automation layer. Integration happens through:

1. **Custom AIFunctionCall plugins** -- Drupal plugins that can query/modify Group entities (create tasks, assign users to groups, update task status)
2. **Custom AI Agents** -- Agents configured via admin UI or code that combine multiple tools for project management workflows
3. **Tool property restrictions** -- Limiting agent tools to specific group types or entity bundles via the AI Agents config UI
4. **Group-scoped agent access** -- Agents operating within a specific group's permission context

### Group Module Architecture (v3.x)

Key entities and concepts for the custom module:
- `Group` entity -- the container (Project, Sprint, Team)
- `GroupType` config entity -- bundle definition (like a content type for groups)
- `GroupRelationship` entity -- the join entity linking content/users to a group (renamed from GroupContent in v3)
- `GroupRelationType` config entity -- bundle definition for relationships
- `GroupRelation` plugins -- define what can be added to a group (`src/Plugin/Group/Relation/`)
- `RelationHandler` services -- handler classes in `src/Plugin/Group/RelationHandler/`
- `Group::addRelationship()` -- API method (renamed from addContent() in v3), returns the created entity
- Group permissions -- per-group-type permission layer on top of Drupal core permissions

### AI Module Architecture

- **AI Core** -- abstraction layer, operation types (chat, embeddings, image gen, speech-to-text, etc.)
- **Providers** -- plugin system for LLM backends (Anthropic, OpenAI, Ollama, Huggingface, etc.)
- **Key module** -- secure credential storage for API keys
- **AI Agents** -- agent framework with tool calling, loops (max_loops config), and sub-agent delegation
- **AIFunctionCall plugins** -- custom tool definitions for agents (the extension point for our module)
- **Default prompts** -- agents ship with default prompts that can be overridden via config UI; once overridden, file-based prompts are not used

### Claude Code Plugin Architecture

- **Plugin manifest** -- `.claude-plugin/plugin.json` at repo root (the only file in `.claude-plugin/`)
- **Skills directory** -- `skills/<name>/SKILL.md` with YAML frontmatter (description triggers auto-loading)
- **Supporting files** -- `skills/<name>/references/` (existing structure, Claude reads when SKILL.md references them)
- **Namespace** -- all skills prefixed with plugin name: `/drupal-skills:skill-name`
- **Auto-triggering** -- description always in context; full skill loads when Claude determines it's relevant
- **Distribution** -- via marketplace (GitHub repo with `.claude-plugin/marketplace.json`) or direct `--plugin-dir` flag
- **Installation scopes** -- user (personal), project (team via VCS), local (gitignored)

## Sources

- [Group module project page](https://www.drupal.org/project/group) -- version 3.3.5, verified 2026-03-07 (HIGH confidence)
- [Group 3.3.5 release notes](https://www.drupal.org/project/group/releases/3.3.5) -- requirements verified (HIGH confidence)
- [Group v3 API changes: addRelationship()](https://www.drupal.org/node/3292844) -- API rename confirmed (MEDIUM confidence)
- [Group documentation](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/group) -- architecture concepts (MEDIUM confidence)
- [AI module project page](https://www.drupal.org/project/ai) -- version 1.2.11, verified 2026-03-07 (HIGH confidence)
- [AI module documentation](https://project.pages.drupalcode.org/ai/1.2.x/) -- sub-modules and architecture (MEDIUM confidence)
- [AI Agents project page](https://www.drupal.org/project/ai_agents) -- version 1.2.3, verified 2026-03-07 (HIGH confidence)
- [AI Agents documentation](https://ai-agents-project-eb5f6489e826e45857a7585a7d05c3e39463e30c9c8d5.pages.drupalcode.org/) -- built-in agents and config (MEDIUM confidence)
- [QED42 AI Agents practical guide](https://www.qed42.com/insights/exploring-drupals-ai-agents-a-practical-guide-for-site-builders) -- agent structure and tool calling (MEDIUM confidence)
- [AI Provider Anthropic](https://www.drupal.org/project/ai_provider_anthropic) -- version 1.2.1, verified 2026-03-07 (HIGH confidence)
- [Claude Code Skills documentation](https://code.claude.com/docs/en/skills) -- skill anatomy, auto-triggering, frontmatter reference (HIGH confidence)
- [Claude Code Plugins documentation](https://code.claude.com/docs/en/plugins) -- plugin structure, manifest, quickstart (HIGH confidence)
- [Claude Code Plugins reference](https://code.claude.com/docs/en/plugins-reference) -- full plugin.json schema, directory structure, CLI commands (HIGH confidence)
- [Official Anthropic plugin marketplace](https://github.com/anthropics/claude-plugins-official) -- marketplace structure and submission (HIGH confidence)
- [Drupal PHP requirements](https://www.drupal.org/docs/getting-started/system-requirements/php-requirements) -- D10 needs PHP 8.1+, D11 needs PHP 8.3+ (HIGH confidence)

---
*Stack research for: Drupal Skills v3.0 -- Group AI Project Management*
*Researched: 2026-03-07*
