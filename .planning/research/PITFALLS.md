# Pitfalls Research

**Domain:** Group-based project management with Drupal AI/AI Agents integration + Claude Code plugin packaging + phase-level eval methodology
**Researched:** 2026-03-07
**Confidence:** HIGH for plugin packaging, MEDIUM for Group/AI integration (APIs are evolving), HIGH for eval methodology (empirical v2.0 data)

**Scope:** Pitfalls specific to ADDING v3.0 features (Group module, Drupal AI integration, plugin restructuring, new eval methodology) to the existing Drupal Skills project. v2.0 eval pipeline pitfalls remain in git history.

---

## Critical Pitfalls

Mistakes that cause rewrites, invalidate the eval, or produce a non-functional plugin.

### Pitfall 1: Plugin Skill Auto-Triggering Is Unreliable Without Description Engineering

**What goes wrong:**
Skills packaged in a plugin do not auto-trigger when developers ask natural Drupal questions. The plugin installs fine, skills appear in `/context`, but Claude ignores them and works from training data alone. The entire v3.0 eval (plugin-installed vs no-plugin) produces 0% delta -- not because skills lack value, but because they never activate.

**Why it happens:**
Claude Code loads only skill **descriptions** (name + description field) into the system prompt at session start, with a budget of 2% of context window (~16,000 chars fallback). With 14 skills, each description gets roughly 1,100 characters max. If descriptions are passive ("Covers cache metadata patterns") rather than directive ("Use WHENEVER producing render arrays that display entity or config data"), Claude's relevance matching fails. Community testing shows ~50% activation rate with standard descriptions, improving to ~95% with imperative directive descriptions. Additionally, 14 drupal skills at ~200 chars each = ~2,800 chars, but the description character limit per skill is 1,024 chars -- staying well within budget. The risk is not budget overflow but description quality.

**How to avoid:**
- Use imperative directive descriptions: "Use WHENEVER [trigger condition]. Do NOT use for [anti-pattern]." This is the pattern already used in existing SKILL.md files (confirmed in drupal-caching and drupal-module-scaffold).
- Test auto-triggering empirically: write 10 natural developer prompts per skill, run each with the plugin installed, measure activation rate. Target >80%.
- If activation is low, add a `UserPromptSubmit` hook that evaluates each prompt against skill triggers. This is a fallback, not a first resort.
- Set `SLASH_COMMAND_TOOL_CHAR_BUDGET=30000` if skills are excluded from context (check `/context` for warnings).
- Write descriptions in third person ("Applies correct cache metadata...") per official Anthropic best practices -- inconsistent point-of-view causes discovery problems.

**Warning signs:**
- `/context` shows skills excluded or truncated
- Natural prompts like "add caching to this block" do not trigger drupal-caching skill
- v3.0 eval shows 0% delta across all phases despite v2.0 showing HIGH deltas on same skills

**Phase to address:**
Plugin packaging phase (description optimization). Must validate auto-triggering BEFORE running phase-level evals, or eval results are meaningless.

---

### Pitfall 2: Eval Methodology Conflates Plugin Activation Failure with Skill Content Failure

**What goes wrong:**
v3.0 shifts from "explicit `read SKILL.md`" (v2.0) to "plugin installed, skills must auto-trigger from natural prompts" (v3.0). When a phase-level eval shows 0% delta, you cannot distinguish between: (a) the skill content is not useful for this task, (b) the skill never activated, or (c) the skill activated but the prompt was too easy. This ambiguity makes eval results unactionable.

**Why it happens:**
v2.0 controlled for activation by explicitly telling the agent to read SKILL.md. v3.0 removes this control, introducing a new variable (activation) on top of the existing variable (content value). Two uncontrolled variables in one experiment = no causal attribution.

**How to avoid:**
- **Three-tier eval design per phase:**
  1. **Without-plugin baseline:** No plugin installed. Headless `claude -p` with haiku. Measures baseline capability.
  2. **With-plugin (auto-trigger):** Plugin installed but no explicit skill instruction. Measures full product experience (activation + content).
  3. **With-plugin (explicit):** Plugin installed AND prompt includes "use the drupal-X skill". Measures content value independent of activation.
- Compare tier 2 vs tier 3: if tier 3 shows delta but tier 2 does not, the problem is activation (description), not content.
- Compare tier 1 vs tier 3: should match v2.0 deltas (validated baseline).
- Log whether skills activated: check Claude's tool use for `Skill(drupal-*)` calls.

**Warning signs:**
- Phase eval shows 0% delta on a skill that had HIGH delta in v2.0 (caching, scaffold, routing-controllers, testing)
- No `Skill()` tool calls visible in session transcript
- Tier 2 and tier 3 produce different results

**Phase to address:**
Eval methodology design phase. Must be decided before ANY phase-level eval runs.

---

### Pitfall 3: Group Module 3.x API Terminology Has Changed -- Old Documentation Misleads

**What goes wrong:**
Development uses Group 2.x API names (`GroupContent`, `GroupContentEnabler`, `addContent()`, `GroupContentEnablerManager`) because most tutorials and StackOverflow answers reference the older API. The code compiles against Group 3.x but produces EntityNotFoundException errors at runtime, or silently creates wrong entity types.

**Why it happens:**
Group 3.x renamed fundamental concepts:
- `GroupContent` entity -> `GroupRelationship` entity
- `GroupContentType` entity -> `GroupRelationshipType` entity
- `GroupContentEnabler` plugin -> `GroupRelationType` plugin
- `GroupContentEnablerManager` service -> `GroupRelationTypeManager` service
- `Group::addContent()` -> `Group::addRelationship()` (returns created entity in 3.x)
- Plugin directory: `src/Plugin/Group/ContentEnabler/` -> `src/Plugin/Group/Relation/`
- Plugin handlers replaced most methods on the plugin base class

Most documentation, tutorials, and even some Drupal.org issue queue discussions still reference 2.x terminology. Claude's training data likely contains more 2.x examples than 3.x.

**How to avoid:**
- Pin Group module version in composer.json: `"drupal/group": "^3.3"`. Never `^2 || ^3`.
- Create a SKILL.md reference card mapping old to new terminology. Include it in the drupal-entities-fields skill or a new Group-specific skill.
- Validate ALL Group API calls against 3.x codebase, not documentation. The source at `modules/contrib/group/src/` is authoritative.
- Use `GroupRelationBase` for custom relation plugins, NOT `GroupContentEnablerBase`.
- Handler pattern: define handlers as services with naming convention `group.relation_handler.[HANDLER_TYPE].[PLUGIN_ID]`.

**Warning signs:**
- Code references `GroupContent` class or `group_content` entity type
- Plugin files in `src/Plugin/Group/ContentEnabler/` directory
- Method calls to `$group->addContent()` instead of `$group->addRelationship()`
- `@GroupContentEnabler` annotation instead of `@GroupRelationType`

**Phase to address:**
Entity/data model phase (first phase that touches Group module). Must have correct API terms from day one.

---

### Pitfall 4: Plugin Directory Structure Has Strict Requirements -- Skills in Wrong Location Are Silently Ignored

**What goes wrong:**
Restructuring the existing `skills/` repo into a Claude Code plugin results in skills not loading. Plugin installs, `plugin.json` validates, but zero skills appear in `/context` or autocomplete. No error messages -- completely silent failure.

**Why it happens:**
Claude Code plugins require a specific directory structure:
- `plugin.json` MUST be inside `.claude-plugin/` directory
- Skills MUST be at plugin root in `skills/` directory (NOT inside `.claude-plugin/`)
- Each skill needs `skills/<skill-name>/SKILL.md` structure
- Plugin is cached to `~/.claude/plugins/cache/` on install -- external references via `../` are stripped

The existing repo structure (`skills/drupal-*/SKILL.md`) is already correct for the skills directory layout. But the common mistake is putting skills inside `.claude-plugin/skills/` or using absolute paths in plugin.json.

**How to avoid:**
- Target plugin structure:
  ```
  drupal-skills/                    # Plugin root
  ├── .claude-plugin/
  │   └── plugin.json              # Only manifest here
  ├── skills/                       # At root level
  │   ├── drupal-caching/
  │   │   ├── SKILL.md
  │   │   └── references/
  │   ├── drupal-module-scaffold/
  │   │   └── SKILL.md
  │   └── ... (14 total)
  ├── agents/                       # Optional
  ├── hooks/                        # Optional (for auto-trigger hooks)
  └── settings.json                 # Optional defaults
  ```
- All paths in plugin.json MUST be relative starting with `./`
- Use `${CLAUDE_PLUGIN_ROOT}` in any scripts/hooks, never absolute paths
- Test with `claude --plugin-dir .` during development (bypasses cache)
- Validate with `claude --debug` to see plugin loading messages
- Verify skills appear: ask Claude "What skills are available?" after enabling plugin

**Warning signs:**
- `claude --debug` shows plugin loading but no skill registration messages
- `/context` shows no drupal-* skills
- Plugin installs without error but skills don't appear in autocomplete

**Phase to address:**
Plugin packaging phase. This is the FIRST thing to get right -- all subsequent phases depend on skills loading.

---

### Pitfall 5: Drupal AI Module Is Pre-1.0 Conceptually Despite Version Numbers -- API Will Change

**What goes wrong:**
Building tight integration with Drupal AI module's provider API or AI Agents framework, then discovering the API changed in a point release. Custom tools, agent configurations, or provider integrations break on `composer update`.

**Why it happens:**
While Drupal AI module is at version 1.2.11 (stable), and AI Agents at 1.2.3, the ecosystem is rapidly evolving. The 2026 roadmap lists 8 new capabilities (page generation, context management, background agents, design system integration). The AI Agents documentation explicitly states it is "WIP". The Tool API module (separate from AI core) is a recent addition providing `tool_ai_connector` for bridging to AI function calling. Module interdependencies are shifting: AI -> AI Agents -> Tool API is an emerging but not yet stable dependency chain.

**How to avoid:**
- Pin exact versions in composer.json: `"drupal/ai": "1.2.11"`, `"drupal/ai_agents": "1.2.3"`. Do NOT use `^1.2`.
- Write integration code against the provider abstraction layer (`\Drupal::service('ai.provider')`) not specific provider implementations.
- Wrap ALL AI module API calls in a service class with a clear interface. When APIs change, only the wrapper changes.
- Design the module to work WITHOUT AI integration as a baseline (Group project management), with AI as an enhancement layer that can be enabled/disabled.
- Subscribe to AI module release notes. Test against `dev` branch periodically.
- Consider the Tool API module for defining custom tools that AI Agents can call, rather than building custom agent code directly.

**Warning signs:**
- Direct calls to provider-specific methods scattered throughout the codebase
- No service abstraction layer between your module and `\Drupal::service('ai.provider')`
- Module fails to install if AI module is not present (hard dependency when it should be optional)

**Phase to address:**
Architecture phase (service layer design). AI integration phase (implementation). Design the abstraction BEFORE writing integration code.

---

### Pitfall 6: Existing install.sh and Skill Paths Break When Restructured as Plugin

**What goes wrong:**
The existing `install.sh` script copies skills to `~/.claude/skills/`. When the repo is restructured as a plugin, you now have two install paths: the old `install.sh` (copies to `~/.claude/skills/`) and the new plugin system (via `claude plugin install`). Users who installed via `install.sh` and then install the plugin have duplicate skills -- one set in `~/.claude/skills/` and another from the plugin cache. Claude loads both, wastes context budget, and may get conflicting instructions from two copies of the same skill.

**Why it happens:**
The project evolved from "repo of skills you manually install" (v1.0/v2.0) to "packaged plugin you install via Claude Code" (v3.0). The transition creates a dual-installation surface.

**How to avoid:**
- Phase the transition: first release as plugin, then deprecate `install.sh` in the README.
- Add a migration check to the plugin's `SessionStart` hook: if `~/.claude/skills/drupal-*` exists, warn the user to run `install.sh --uninstall` or manually remove the old skills.
- Plugin skills use namespace `drupal-skills:drupal-caching` (plugin-name:skill-name), which cannot conflict with personal skills at `~/.claude/skills/drupal-caching`. BUT context budget is still consumed by both.
- Alternatively: update `install.sh` to detect if the plugin is installed and abort with a message.
- Add `--uninstall` flag to `install.sh` that removes all `~/.claude/skills/drupal-*` directories.

**Warning signs:**
- Users report skills appearing twice in `/context`
- Context budget warnings about excluded skills
- Total description budget consumed by 28 skills (14 x 2) instead of 14

**Phase to address:**
Plugin packaging phase. Must handle migration path as part of the packaging work.

---

## Moderate Pitfalls

### Pitfall 7: Group Module Access Control Overrides Core Node Access

**What goes wrong:**
Group module returns `AccessResult::forbidden()` for grouped content when the user lacks group-level permissions, even if they have core node permissions. This means existing Drupal roles and permissions do not work as expected for content within groups. Developers assume core access controls still apply and are surprised when admin users with "bypass node access" permission still get denied on grouped content.

**How to avoid:**
- Understand that Group module's access model is intentionally strict: group permissions REPLACE core permissions for grouped content. This is a feature, not a bug.
- Configure Group role permissions explicitly for every content operation (view, create, edit, delete) on every group type.
- Test access control for: anonymous, authenticated, group member, group admin, site admin. All five must be verified.
- If AI Agents need to manipulate grouped content, they need group-level permissions, not just core permissions. The agent's user account must be a member of relevant groups.

**Warning signs:**
- "Access denied" errors for admin users on grouped content
- AI Agents failing to create/edit content within groups despite having core permissions
- REST/JSON:API requests for grouped content returning 403

**Phase to address:**
Access/security phase. Must be addressed when building the Group entity types and permissions.

---

### Pitfall 8: Phase-Level Eval Environment Accumulates State Across Phases

**What goes wrong:**
v3.0 builds a real module across multiple phases. Phase 3 (routing) depends on entities from Phase 2. Phase 5 (caching) depends on blocks from Phase 4. If the "without-plugin baseline" approach creates code in Phase 2 that is structurally incompatible with Phase 3's requirements, the entire baseline chain breaks and subsequent phase comparisons are invalid.

**How to avoid:**
- **Each phase baseline must start from a known-good snapshot.** After Phase N is built with the plugin, snapshot the ddev environment (export DB + files). The Phase N+1 baseline starts from this snapshot, not from Phase N's baseline output.
- Alternative: define each phase as self-contained with explicit input fixtures. Phase 3 does not depend on Phase 2's output -- it depends on a pre-built fixture that provides the necessary entities.
- Document phase dependencies explicitly: which phases require prior phase output vs which are independently evaluable.
- Consider making some phases independent "skill islands" that can be evaluated without sequential dependency.

**Warning signs:**
- Baseline breaks in later phases because earlier baseline code was structurally wrong
- Accumulating technical debt in the baseline that makes fair comparison impossible
- Phase eval results vary wildly depending on which baseline code was generated

**Phase to address:**
Eval methodology design phase. Must decide phase independence vs chain before any eval runs.

---

### Pitfall 9: Group Module REST/JSON:API Content Creation Requires Group Context

**What goes wrong:**
Trying to create group content (tasks, milestones) via REST or JSON:API fails because the Group module requires `$context['group']` for access checks during entity creation. Standard `POST /jsonapi/node/task` does not provide this context, resulting in access denied errors even for authenticated users with correct permissions.

**How to avoid:**
- Use Group module's relationship API for content creation: create the node first, then add it to the group via `$group->addRelationship($node, 'group_node:task')`.
- For REST/JSON:API, create a custom REST resource or controller that handles the group context.
- AI Agents that create content in groups must use a two-step process: entity creation + group relationship creation.
- Consider the EntityGroupField contrib module for providing a computed field that simplifies the group assignment UX.

**Warning signs:**
- 403 errors on JSON:API POST requests for group-enabled content types
- `TypeError: GroupRelationBase::createAccess()` errors when group context is null
- AI Agents can create content but it does not appear within groups

**Phase to address:**
Routing/API phase. Must be addressed when building REST endpoints for project management.

---

### Pitfall 10: Headless `claude -p` vs Agent Subagent for v3.0 Eval Is a Fundamental Methodology Decision

**What goes wrong:**
v2.0 used headless `claude -p --model claude-haiku-4-5-20251001` for code generation because it produced clean A/B data (37.5% delta on caching vs 0% with agent harness). v3.0 wants to test the "full product experience" with plugin auto-triggering. These are fundamentally incompatible: `claude -p` cannot load plugins, and interactive sessions cannot be scripted reproducibly.

**How to avoid:**
- For "content value" evaluation (does the skill help?): continue using headless `claude -p` with explicit skill injection, same as v2.0. This is the validated methodology.
- For "activation" evaluation (does the skill auto-trigger?): use interactive Claude Code sessions with the plugin installed via `--plugin-dir`. This requires a different eval harness.
- For "integration" evaluation (does the full module work?): build the real module with the plugin installed. Quality is assessed by code review and functional testing, not A/B comparison.
- Do NOT try to run A/B activation tests via `claude -p`. Plugins are not loaded in headless mode.
- Consider using `claude --plugin-dir /path/to/plugin -p "build the routing for project tasks"` if `--plugin-dir` works with `-p`. This needs empirical validation.

**Warning signs:**
- Attempting to measure auto-trigger rate via headless pipeline
- Conflating "skill activated" with "skill helped" in eval results
- No clear separation between activation eval and content eval

**Phase to address:**
Eval infrastructure phase. Must test whether `--plugin-dir` works with `-p` before designing the full eval.

---

### Pitfall 11: SKILL.md Content May Need Group-Specific Patterns Not in Sipos Book

**What goes wrong:**
The existing 14 skills are extracted from the Sipos D10 book. The Group module, Drupal AI module, and project management patterns are NOT in the book. Building a Group-based module may require patterns that no existing skill covers: custom GroupRelationType plugins, AI provider service injection, Tool API definitions, group-scoped Views, group-aware access checking.

**How to avoid:**
- Identify knowledge gaps early: list every Group/AI API pattern the module will need, cross-reference against existing SKILL.md files.
- Do NOT modify existing skills to add Group-specific content. The 14 skills are book-derived and validated via v2.0 evals. Adding non-book content risks regression.
- If Group-specific patterns are needed, create a NEW skill (e.g., `drupal-group-integration`) that covers Group module development patterns. This keeps the book-sourced skills clean.
- For AI module integration: the patterns are standard Drupal (services, plugins, dependency injection). The existing `drupal-plugins-blocks` and `drupal-routing-controllers` skills likely cover most of it. Validate empirically.

**Warning signs:**
- Phases requiring Group-specific patterns produce 0% delta because no skill covers them
- Temptation to add Group patterns to existing skills (scope creep)
- Multiple phases blocked by missing Group API knowledge that existing skills cannot provide

**Phase to address:**
Architecture phase. Identify which phases need Group-specific knowledge. Create a new skill if gap analysis warrants it.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Hard-coding AI provider (e.g., Anthropic only) | Faster implementation | Locks out OpenAI/Gemini users, breaks when provider changes | Never -- use `ai.provider` abstraction from day one |
| Skipping Group access control testing | Faster feature delivery | Users discover broken permissions in production | Never -- Group access is the module's core value |
| Putting all 14 skills in plugin without description optimization | Ship plugin fast | Auto-trigger rate is ~50%, making the plugin feel broken | Only for internal testing; optimize descriptions before public release |
| Using Group 2.x API calls that happen to work on 3.x | Code works today | Breaks on Group 3.4+ when deprecated 2.x shims are removed | Never -- use 3.x API from the start |
| Tight coupling between project management entities and AI features | One codebase, simpler dev | Cannot install project management without AI module | Never -- AI should be an optional submodule or separate module |
| Using existing v2.0 eval methodology for v3.0 without adaptation | Reuse proven pipeline | Cannot measure auto-triggering, which is v3.0's core question | For content value validation only; need separate activation eval |

## Integration Gotchas

Common mistakes when connecting to external services and APIs.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Group module + custom entities | Using `entity_type_id` in GroupRelationType annotation without enabling the relation in group type config | After creating the plugin, must also create a GroupRelationshipType config entity via UI or config install |
| AI module + provider service | Calling `$provider->chat()` without checking if the provider supports the Chat operation type | Check `$provider->isUsable()` and handle the case where no provider is configured |
| AI Agents + Group content | Assuming AI agent's user has group permissions because they have core permissions | Add the AI agent's user to relevant groups with appropriate group roles |
| Plugin + existing install.sh | Shipping plugin without migration path for existing `~/.claude/skills/` users | Add uninstall instructions, migration hook, or dual-install detection |
| Claude Code plugin + headless `claude -p` | Assuming `-p` mode loads plugins for eval | Verify empirically; likely need `--plugin-dir` flag explicitly |
| Tool API + AI Agents | Defining tools without JSON Schema for input/output | Tool API requires typed schemas for AI function calling to work |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Loading all Group relationships eagerly | Slow page loads, high memory | Use paged queries, lazy loading of relationships | >100 relationships per group |
| AI provider calls on every page load | Timeout errors, API rate limits, high cost | Cache AI responses with appropriate cache tags, use batch/queue for bulk operations | >10 concurrent users triggering AI |
| No cache metadata on Group-related render arrays | Stale content after group membership changes | Add `group:ID` cache tags, `user.group_permissions` cache context | Any multi-user scenario |
| Eval running 14 skills x 3 tiers sequentially | 6+ hour eval run | Run independent evaluations in parallel where possible, skip self-evident phases | Every full eval run |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| AI agent with admin permissions creating content in any group | Privilege escalation -- AI creates content users cannot access | Scope AI agent permissions to specific groups; never give site-wide admin to AI service account |
| Exposing AI provider API keys via Group content fields | API key theft, financial loss | Use Drupal Key module for credential storage; never store keys in config or content |
| Group permissions not tested for anonymous users | Public access to private project data | Write explicit access tests for anonymous, authenticated, and non-member roles |
| Trusting AI-generated content without sanitization | XSS via AI output injected into render arrays | Always use `#markup` with `Xss::filterAdmin()` or proper render element types for AI output |
| Custom REST endpoints bypassing Group access checks | Ungrouped content creation, access control bypass | Use `$group->hasPermission()` in custom controllers, not just core `$account->hasPermission()` |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Plugin skills auto-trigger on unrelated tasks | Claude uses drupal-caching skill when user is working on a Node.js project | Make skill descriptions highly specific with clear negative constraints ("Do NOT use for non-Drupal projects") |
| No feedback when AI agent fails within Group context | User sees generic error, cannot debug | Surface AI agent errors in Drupal messages with contextual information about which tool failed and why |
| Requiring Group module for basic project management | Users who want simple task tracking must install complex access control system | Make Group integration optional -- basic project management should work without Group module |
| Phase-level eval prompts that are too specific to the module | Eval measures prompt memorization, not skill value | Write phase prompts that describe the GOAL, not the implementation |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Plugin packaging:** Skills load in `/context` -- verify each of 14 skills appears, not just the first few
- [ ] **Plugin packaging:** Skills auto-trigger -- test with 5+ natural prompts per skill, not just `/skill-name` invocation
- [ ] **Plugin packaging:** Old `install.sh` users are handled -- migration path documented, dual-install detection works
- [ ] **Group integration:** Custom GroupRelationType plugin -- verify it appears in Group Type configuration UI, not just that the plugin class exists
- [ ] **Group integration:** Access control -- test all 5 user types (anon, auth, member, group-admin, site-admin), not just admin
- [ ] **AI integration:** Provider abstraction -- verify module installs and basic features work WITHOUT AI module enabled
- [ ] **AI integration:** Tool definitions -- verify tools appear in AI Agent Explorer, not just that the code compiles
- [ ] **Eval methodology:** Activation logging -- verify you can determine which skills activated from eval transcripts
- [ ] **Eval methodology:** Baseline validity -- verify without-plugin baseline produces working (if less optimal) code, not broken code
- [ ] **Eval methodology:** Phase independence -- verify Phase N+1 baseline does not depend on Phase N baseline's specific implementation

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Skills not auto-triggering (#1) | LOW | Optimize descriptions iteratively. Add hooks if needed. Does not require code changes. |
| Eval conflates activation and content (#2) | MEDIUM | Add explicit-trigger tier to eval. Re-run affected phases only. |
| Wrong Group API version (#3) | HIGH | Find-and-replace all 2.x terms. Rewrite plugin classes. Rebuild config entities. |
| Plugin structure wrong (#4) | LOW | Move directories to correct locations. Re-install plugin. |
| AI API breaks on update (#5) | MEDIUM | Service abstraction layer limits blast radius. Update wrapper, not callsites. |
| Dual install confusion (#6) | LOW | Ship `install.sh --uninstall`. Document in README. |
| Group access blocks AI agent (#7) | MEDIUM | Create dedicated AI service account, add to groups, assign group roles. |
| Phase eval chain breaks (#8) | HIGH | Redesign as independent phases with fixtures. Significant rework. |
| REST/JSON:API group context missing (#9) | MEDIUM | Add custom controller or two-step creation pattern. |
| Headless eval cannot test auto-trigger (#10) | MEDIUM | Accept different methodology for activation vs content evals. |

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Auto-trigger unreliability (#1) | Plugin packaging + description optimization | Run activation test suite: 10 prompts x 14 skills, >80% activation rate |
| Eval methodology conflation (#2) | Eval design phase | Document three-tier eval design, validate with one calibration phase |
| Group 3.x API terminology (#3) | Entity/data model phase | grep for 2.x terms in codebase, zero matches required |
| Plugin directory structure (#4) | Plugin packaging phase | `claude --debug` shows all 14 skills registered |
| Drupal AI API instability (#5) | Architecture phase | AI service abstraction interface defined, module installs without AI module |
| install.sh migration (#6) | Plugin packaging phase | Test: install via install.sh, then install plugin, verify no duplicates |
| Group access overrides (#7) | Access/security phase | Automated tests for all 5 user types on grouped content |
| Phase eval state accumulation (#8) | Eval design phase | Each phase has documented fixtures or snapshot strategy |
| REST group context (#9) | Routing/API phase | JSON:API POST to create task in group succeeds |
| Headless vs interactive eval (#10) | Eval infrastructure phase | Empirical test: does `--plugin-dir` work with `-p`? |
| Missing Group skill content (#11) | Architecture phase | Gap analysis document: patterns needed vs patterns covered |

## Sources

- [Claude Code Skills Documentation](https://code.claude.com/docs/en/skills) -- Skill loading, description budget (2% of context window), auto-triggering behavior, frontmatter reference
- [Claude Code Plugins Reference](https://code.claude.com/docs/en/plugins-reference) -- Plugin directory structure, plugin.json schema, component locations, common issues table
- [Anthropic Skill Authoring Best Practices](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices) -- Description writing guidelines, third-person requirement, 1024-char limit, testing recommendations
- [Claude Code Skills Activation Issues](https://dev.to/oluwawunmiadesewa/claude-code-skills-not-triggering-2-fixes-for-100-activation-3b57) -- UserPromptSubmit hook workaround, forced evaluation pattern (LOW confidence -- unverified activation rates)
- [Skills Activation Community Research](https://scottspence.com/posts/how-to-make-claude-code-skills-activate-reliably) -- Imperative vs passive description testing, SLASH_COMMAND_TOOL_CHAR_BUDGET env var
- [Drupal Group Module](https://www.drupal.org/project/group) -- Group 3.3.0 release notes, API changes from 2.x to 3.x
- [Drupal Group 3.x API Changes](https://www.drupal.org/node/3292844) -- `addContent()` -> `addRelationship()`, entity renames
- [Drupal Group 3.x GroupRelationTypeManager](https://www.drupal.org/node/3232814) -- Service renames, handler pattern
- [Adding Custom Permissions to Groups](https://www.hashbangcode.com/article/drupal-10-adding-custom-permissions-groups) -- GroupRelationType plugin implementation, Group 3.x code patterns
- [Drupal AI Module](https://www.drupal.org/project/ai) -- Version 1.2.11, provider abstraction API
- [Drupal AI Agents Module](https://www.drupal.org/project/ai_agents) -- Version 1.2.3, tool calling framework, WIP documentation
- [Drupal AI Agents Getting Started](https://project.pages.drupalcode.org/ai/2.0.x/agents/) -- Agent architecture, tool calling, development approach
- [Group Module REST/JSON:API Issue](https://www.drupal.org/project/group/issues/2872645) -- `$context['group']` required for content creation access checks
- [Group Access Control Issue](https://www.drupal.org/project/group/issues/3162511) -- `forbidden()` result breaking regular node grants
- v2.0 empirical eval data from MEMORY.md -- headless pipeline validated, 37.5% vs 0% agent harness delta on caching skill
- Existing SKILL.md files (drupal-caching, drupal-module-scaffold) -- current description format with imperative directives

---
*Pitfalls research for: Group-based project management with AI/AI Agents integration + Claude Code plugin packaging*
*Researched: 2026-03-07*
