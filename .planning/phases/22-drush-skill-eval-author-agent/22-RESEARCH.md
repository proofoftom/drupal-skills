# Phase 22: Drush Skill + Eval-Author Agent - Research

**Researched:** 2026-03-09
**Domain:** Drush 13 command authoring patterns, Claude Code subagent design for eval assertion generation
**Confidence:** HIGH

## Summary

Phase 22 has two distinct deliverables that share no code dependencies but are grouped because both are pure tooling that every subsequent v5.0 phase benefits from: (1) a 15th skill file teaching Drush 13+ command creation patterns, and (2) an Opus-class subagent that automates the design of three-tier eval assertions.

The Drush skill is well-scoped. Drush 13 command authoring has three major breaking changes from Drush 11 and earlier that LLMs consistently get wrong without guidance: directory structure (`src/Drush/Commands/` not `src/Commands/`), dependency injection (`AutowireTrait` not `drush.services.yml`), and command declaration (`#[CLI\Command]` attributes not `@command` annotations for Drush 12, with `#[AsCommand]` as the forward-looking Drush 13.7+ pattern). These are exactly the kind of non-obvious patterns where skills produce measurable delta -- analogous to CacheableJsonResponse in the caching skill (+37.5% delta). The skill also cross-references batch-queue-cron for CLI batch processing and covers Drush commands commonly used in runtime assertions.

The eval-author agent is higher-risk because it is novel -- no precedent exists in this project for automated assertion generation. The dominant failure mode is tautological assertions that test file existence instead of skill-differentiating patterns (Pitfall #2 from PITFALLS.md). The research confirms that the mitigation strategy is sound: enforce a 60/20/20 assertion category distribution (differentiating/wiring/structural), provide Phase 18 evals as gold-standard examples, and include explicit tautology rejection rules in the agent prompt. The agent follows the exact same `.claude/agents/` frontmatter pattern as eval-grader.md and eval-browser.md.

**Primary recommendation:** Build the Drush skill first (it is a well-understood artifact type), then the eval-author agent (using the Drush skill's own evals as a validation target for agent output quality).

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TOOL-01 | Drush skill teaches Drush 13+ command creation patterns (src/Drush/Commands/, AutowireTrait, #[AsCommand]) with WRONG/RIGHT callouts for deprecated patterns | Drush 13 official docs verified all patterns. Three breaking changes from Drush 11 identified: directory, DI, attributes. Skill structure follows existing 14-skill pattern (~400-500 lines). |
| TOOL-02 | Drush skill includes eval assertions targeting non-obvious Drush patterns (file location, DI approach, attribute syntax) | Existing evals.json format verified from drupal-module-scaffold. Non-obvious patterns identified: src/Drush/ directory, AutowireTrait usage, #[CLI\Command] attribute vs @command annotation, parent::__construct() call, Drush\Attributes import alias. |
| TOOL-03 | Eval-author Opus subagent designs three-tier assertions (static + runtime + browser) from skill content, module code, and phase prompt | Subagent frontmatter pattern verified from eval-grader.md and eval-browser.md. Three-tier assertion structure verified from v3/v4 eval files. Agent needs Read/Glob/Grep/Bash tools, model: opus. |
| TOOL-04 | Eval-author enforces assertion category distribution (60% differentiating, 20% wiring, max 20% structural) with explicit tautology rejection | Distribution rationale grounded in empirical data: Phase 18 gold-standard (17 assertions, 100% differentiating, +23.3% delta) vs hypothetical tautological assertions (0% delta). Tautology check is a prompt-level rule, not code enforcement. |
</phase_requirements>

## Standard Stack

### Core

No new dependencies. Both deliverables are pure knowledge artifacts.

| Artifact | Type | Purpose | Pattern Source |
|----------|------|---------|---------------|
| `skills/drupal-drush/SKILL.md` | Skill file | Teaches Drush 13+ command creation | Follows existing 14 skills (see `skills/drupal-*/SKILL.md`) |
| `skills/drupal-drush/evals/evals.json` | Eval assertions | Measures skill impact on Drush command generation | Follows `skills/drupal-module-scaffold/evals/evals.json` |
| `.claude/agents/eval-author.md` | Subagent definition | Automates three-tier assertion design | Follows `.claude/agents/eval-grader.md` |

### Drush Version Compatibility Matrix

| Drush Version | Drupal Version | Base Class | Command Attribute | DI Pattern |
|---------------|----------------|------------|-------------------|------------|
| 12.x | 10.x | `DrushCommands` | `#[CLI\Command]` | `AutowireTrait` (preferred) or `create()` |
| 13.0-13.6 | 10.2+, 11.x | `DrushCommands` | `#[CLI\Command]` | `AutowireTrait` (preferred) |
| 13.7+ | 10.2+, 11.x | `Command` (Symfony) | `#[AsCommand]` (Symfony) | `AutowireTrait` (required in 14) |

**Skill strategy:** Teach the Drush 12 pattern (`extends DrushCommands` + `#[CLI\Command]`) as the D10-compatible primary pattern. Show the Drush 13.7 pure Symfony pattern (`extends Command` + `#[AsCommand]`) as the forward-looking alternative. Both share `src/Drush/Commands/` directory and `AutowireTrait` for DI.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Teaching Drush 12 pattern as primary | Drush 13.7 pure Symfony pattern only | Would break on Drupal 10 sites using Drush 12. Project targets `^10 \|\| ^11`. |
| Opus for eval-author | Sonnet for eval-author | Sonnet generates shallow assertions. Eval design requires deep reasoning about what Haiku will miss -- this is Opus-tier work. |
| Single eval JSON format | Separate files for static/runtime/browser | Existing pipeline uses single evals.json + separate runtime-assertions.json. Do not change what works. |

## Architecture Patterns

### Drush Skill File Structure

The skill follows the established pattern of all 14 existing skills. Target length: ~400-500 lines (average of existing skills is 437 lines).

```
skills/drupal-drush/
  SKILL.md          # ~400-500 lines, WRONG/RIGHT callouts
  evals/
    evals.json      # Assertions targeting non-obvious patterns
```

### Skill Content Structure (Recommended Sections)

```markdown
# Drupal Drush Commands

## Where do Drush commands go?
  - src/Drush/Commands/ directory (CRITICAL -- NOT src/Commands/)
  - Namespace: Drupal\{module}\Drush\Commands
  - Class naming: {Module}Commands extends DrushCommands

## Command declaration
  - #[CLI\Command] attribute (Drush 12+, NOT @command annotation)
  - #[CLI\Argument], #[CLI\Option], #[CLI\Usage] attributes
  - use Drush\Attributes as CLI; import alias

## Dependency injection
  - use AutowireTrait; (NOT drush.services.yml)
  - Constructor injection with type hints
  - parent::__construct() call required
  - #[Autowire] for ambiguous services

## Common Drush patterns
  - Output: $this->io()->writeln(), SymfonyStyle tables
  - Return types: RowsOfFields for formatted output
  - Error handling: $this->logger()->error()

## Drush commands for runtime assertions
  - drush php-eval for arbitrary PHP
  - drush pm:list for module status
  - drush config:get for config inspection
  - drush queue:run for queue processing

## D10/D11 compatibility
  - Drush 12 (D10): #[CLI\Command] + extends DrushCommands
  - Drush 13.7+ (D11): #[AsCommand] + extends Command (Symfony)
  - Both: src/Drush/Commands/, AutowireTrait, parent::__construct()

## Cross-references
  - drupal-batch-queue-cron: drush_backend_batch_process() for CLI batches
  - drupal-testing: DrushTestTrait for functional tests
```

### Drush 12 Command Pattern (Primary -- D10 Compatible)

```php
namespace Drupal\my_module\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;

final class MyModuleCommands extends DrushCommands {
  use AutowireTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'my-module:list-items', aliases: ['mm:list'])]
  #[CLI\Argument(name: 'type', description: 'Entity type to list')]
  #[CLI\Option(name: 'limit', description: 'Max items')]
  #[CLI\Usage(name: 'drush my-module:list-items node --limit=10', description: 'List 10 nodes')]
  public function listItems(string $type, array $options = ['limit' => 50]): void {
    $storage = $this->entityTypeManager->getStorage($type);
    $ids = $storage->getQuery()->accessCheck(TRUE)->range(0, $options['limit'])->execute();
    $this->io()->writeln(count($ids) . ' items found.');
  }
}
```

### Drush 13.7+ Command Pattern (Forward-Looking -- D11)

```php
namespace Drupal\my_module\Drush\Commands;

use Drush\Commands\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'my-module:list-items',
  description: 'List entity items by type',
  aliases: ['mm:list'],
)]
final class ListItemsCommand extends Command {
  use AutowireTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  protected function configure(): void {
    $this
      ->addArgument('type', InputArgument::REQUIRED, 'Entity type to list')
      ->addOption('limit', NULL, InputOption::VALUE_OPTIONAL, 'Max items', 50);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $type = $input->getArgument('type');
    $limit = (int) $input->getOption('limit');
    $storage = $this->entityTypeManager->getStorage($type);
    $ids = $storage->getQuery()->accessCheck(TRUE)->range(0, $limit)->execute();
    $output->writeln(count($ids) . ' items found.');
    return Command::SUCCESS;
  }
}
```

### Eval-Author Agent Architecture

```
.claude/agents/eval-author.md
  - model: opus (deep reasoning for assertion quality)
  - tools: Read, Glob, Grep, Bash (read-only exploration)
  - permissionMode: bypassPermissions (consistent with eval-grader)
```

**Input context the agent receives from orchestrator:**
1. Skill file(s) being tested (`skills/drupal-*/SKILL.md`)
2. Phase prompt (what code generation task will be given to Haiku)
3. Existing module code (current state of `modules/group_ai_pm/`)
4. Gold-standard examples (`eval/v4/phase-18-evals.json`)
5. Previous eval results (to learn what passes/fails)

**Output format the agent produces:**
1. `evals.json` -- static assertions with explanatory rationale per assertion
2. `runtime-assertions.json` -- drush php-eval commands testing functional behavior
3. Browser assertions embedded in evals.json as `(via eval-browser)` prefixed expectations

### Assertion Category Distribution (TOOL-04)

| Category | Target % | What It Tests | Example |
|----------|----------|---------------|---------|
| Differentiating | 60%+ | Non-obvious patterns from SKILL.md that Haiku gets wrong without skill | "Uses CacheableJsonResponse not plain JsonResponse for GET endpoints" |
| Wiring | 20%+ | Components connect correctly -- DI resolves, routes wire to controllers | "(via ddev exec) drush php-eval tests that service container resolves MyService" |
| Structural | max 20% | Files exist, classes are loadable -- necessary but not sufficient | "Controller class exists and is autoloadable" |

**Tautology rejection rule:** If an assertion would pass for any Drupal module regardless of skill usage (e.g., "module has .info.yml"), it is tautological and must be rejected. Every assertion must reference a specific pattern, class, method, or configuration that the skill uniquely teaches.

### Anti-Patterns to Avoid

- **Teaching deprecated Drush patterns as primary:** The skill must lead with Drush 12+ patterns. Old patterns (src/Commands/, drush.services.yml, @command annotations) go in WRONG callouts only.
- **eval-author with Write tool:** The agent must NOT write files directly. It returns JSON to the orchestrator, which writes the files. This prevents the agent from modifying skill content or module code.
- **Mixing structural and differentiating assertions:** Each assertion should be clearly categorizable. "File exists at correct path" is structural. "File uses AutowireTrait instead of create() factory" is differentiating.
- **Omitting assertion rationale:** Every static assertion in evals.json must include parenthetical rationale explaining WHY the pattern matters and what the wrong alternative is (following Phase 18 gold-standard format).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Skill file format | Custom format | Existing SKILL.md format with frontmatter | 14 validated skills already use this format; the eval pipeline expects it |
| Agent definition format | Custom YAML/JSON agent spec | `.claude/agents/*.md` frontmatter format | eval-grader.md and eval-browser.md prove the pattern works |
| Eval assertion format | New assertion schema | Existing evals.json + runtime-assertions.json | v3/v4 pipeline consumes this format; changing it breaks the pipeline |
| Assertion quality checking | Automated tautology detection code | Prompt-level rules in eval-author system prompt | Tautology detection is a reasoning task, not a pattern match. Opus handles it via instructions. |

**Key insight:** Phase 22 produces zero code that runs in the module or eval pipeline. Everything is a knowledge artifact (skill file, agent prompt, assertion JSON). The "don't hand-roll" guidance here is about format consistency, not library selection.

## Common Pitfalls

### Pitfall 1: Drush Skill Teaches Deprecated Patterns

**What goes wrong:** Skill teaches `src/Commands/` directory, `drush.services.yml` for DI, or `@command` annotations. Generated commands are not discovered by Drush 12+.
**Why it happens:** Most LLM training data contains Drush 8-11 patterns. The `src/Drush/Commands/` directory requirement is Drush 12+ specific (2023+).
**How to avoid:** Lead with Drush 12+ patterns. Place CRITICAL NEVER callouts on deprecated approaches. Include WRONG/RIGHT comparisons for all three breaking changes (directory, DI, attributes).
**Warning signs:** `drush list` does not show the command; `drush -vvv my:command` shows discovery errors.

### Pitfall 2: Eval-Author Generates Tautological Assertions

**What goes wrong:** Agent produces assertions like "Controller file exists" or "Module has .info.yml" that pass 100% for both with-skill and without-skill runs, producing 0% delta.
**Why it happens:** LLMs gravitate toward assertions they can verify with high confidence, which are exactly the assertions that test obvious, undifferentiated behavior.
**How to avoid:** Enforce 60/20/20 distribution. Provide Phase 18 gold-standard examples. Include explicit tautology rejection rules. Each assertion must reference a specific SKILL.md pattern.
**Warning signs:** All assertions pass at 100% for both variants. No assertion mentions a specific method, class, or API from the SKILL.md.

### Pitfall 3: Eval-Author Generates Only Static Assertions (No Runtime)

**What goes wrong:** Agent produces 15 static assertions and 0 runtime assertions. Module passes all static checks but `drush en` fails, or DI is broken.
**Why it happens:** Static assertions are natural outputs of code analysis. Runtime assertions require understanding Drupal's bootstrap, service container, and execution behavior.
**How to avoid:** Agent prompt must mandate all three tiers. Include runtime assertion examples (drush php-eval patterns). Explicitly require at least 3 runtime assertions per eval.
**Warning signs:** Zero runtime-assertions.json entries. All assertions use grep/file-read patterns, none use ddev exec or drush commands.

### Pitfall 4: Missing parent::__construct() in Drush Command Class

**What goes wrong:** Command class with `AutowireTrait` and `extends DrushCommands` omits `parent::__construct()` call. Symfony Console throws error about uninitialized command name.
**Why it happens:** Standard Drupal classes (controllers, forms) do not require parent constructor calls. Drush commands do because Symfony Console Command's constructor sets the command name.
**How to avoid:** Skill must explicitly call out `parent::__construct()` requirement with WRONG/RIGHT example. Eval assertion should check for `parent::__construct()` in constructor.
**Warning signs:** Fatal error on `drush list`: "LogicException: Command has no name."

### Pitfall 5: Confusing #[CLI\Command] (Drush 12) with #[AsCommand] (Drush 13.7)

**What goes wrong:** Skill teaches `#[AsCommand]` for Drush 12 sites or `#[CLI\Command]` for Drush 13.7+ sites. Either mismatch causes issues or deprecation warnings.
**Why it happens:** Two overlapping transition periods: `#[CLI\Command]` replaced `@command` annotations in Drush 12; `#[AsCommand]` replaces `#[CLI\Command]` in Drush 13.7.
**How to avoid:** Skill must clearly separate the two patterns with version requirements. Primary pattern = `#[CLI\Command]` (works Drush 12-13.x). Forward-looking = `#[AsCommand]` (Drush 13.7+ only).
**Warning signs:** Deprecation notices in Drush 13.7+ output. Commands not discovered on Drush 12 sites.

## Code Examples

### Example: Drush Skill WRONG/RIGHT Callout (Verified Pattern)

```markdown
> WRONG: Placing Drush command files in `src/Commands/MyModuleCommands.php`.
> Drush 12+ requires command files under `src/Drush/Commands/`. Files in the
> old `src/Commands/` location are NOT discovered. This is the #1 cause of
> "Command not found" errors in modern Drupal.
> RIGHT: Place commands at `src/Drush/Commands/MyModuleCommands.php` with
> namespace `Drupal\my_module\Drush\Commands`. The `Drush/` subdirectory
> is mandatory for auto-discovery.
```

Source: [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/)

### Example: Eval Assertion Targeting Non-Obvious Drush Pattern

```json
{
  "expectations": [
    "Drush command file is located in src/Drush/Commands/ directory, NOT in src/Commands/ (Drush 12+ requires the Drush/ subdirectory for auto-discovery -- files in the old src/Commands/ location are silently ignored and drush list will not show the command)",
    "Command class uses 'use AutowireTrait;' for dependency injection instead of a drush.services.yml file (drush.services.yml is deprecated in Drush 12+ and will be removed in Drush 14 -- AutowireTrait resolves services from constructor type hints automatically)",
    "Constructor calls parent::__construct() (Symfony Console Command's constructor initializes the command name -- omitting it causes a LogicException: 'Command has no name')",
    "Class imports Drush attributes via 'use Drush\\Attributes as CLI;' and uses #[CLI\\Command] or #[AsCommand] attributes, NOT @command annotations in docblocks (PHP 8 attributes are the standard for Drush 12+ command metadata -- docblock annotations are the Drush 8-11 legacy format)"
  ]
}
```

### Example: Eval-Author Agent System Prompt (Key Rules)

```markdown
## Assertion Design Rules

1. NEVER generate assertions that test for file existence alone, standard
   Drupal boilerplate, or patterns that Haiku produces correctly WITHOUT
   the skill. Every assertion must target a specific pattern from the
   SKILL.md WRONG/RIGHT callouts or CRITICAL sections.

2. Category distribution (MANDATORY):
   - At least 60% DIFFERENTIATING: tests non-obvious skill-taught patterns
   - At least 20% WIRING: tests that components connect (DI resolves,
     routes wire to controllers, commands are discoverable)
   - At most 20% STRUCTURAL: tests file/class existence

3. Tautology check: For each assertion, ask "Would Haiku produce this
   pattern correctly WITHOUT reading the skill?" If YES, the assertion
   is tautological -- rewrite it to test the SPECIFIC approach the skill
   teaches over the default approach Haiku would use.

4. Every static assertion MUST include a parenthetical rationale explaining
   (a) what the wrong alternative is, and (b) what happens when you do it
   wrong. Follow the Phase 18 gold-standard format.
```

### Example: Runtime Assertion for Drush Command Discovery

```json
{
  "id": "rt-1",
  "name": "Custom Drush command is discovered",
  "command": "ddev drush list --filter=my_module 2>&1 | grep -q 'my-module:' && echo PASS || echo FAIL",
  "expected": "PASS",
  "rationale": "Verifies that the command file in src/Drush/Commands/ is auto-discovered by Drush -- catches the #1 pitfall of placing commands in the wrong directory"
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `src/Commands/` directory | `src/Drush/Commands/` directory | Drush 12 (2023) | Commands not discovered if in wrong directory |
| `drush.services.yml` for DI | `AutowireTrait` in class | Drush 12.5+ (2023), required Drush 14 | Services file deprecated, autowire preferred |
| `@command` docblock annotations | `#[CLI\Command]` PHP attributes | Drush 12 (2023) | Annotations still work but are legacy |
| `#[CLI\Command]` Drush attributes | `#[AsCommand]` Symfony attribute | Drush 13.7 (2025) | CLI\Command deprecated, AsCommand is future |
| `extends DrushCommands` | `extends Command` (Symfony) | Drush 13.7 (2025) | DrushCommands base class deprecated |
| Manual eval assertion design | Eval-author Opus subagent | Phase 22 (new) | Eliminates 30-min manual bottleneck per phase |
| Two-tier assertions (static + runtime) | Three-tier (static + runtime + browser) | v4.0 Phase 18 | Browser assertions catch rendering issues |

**Deprecated/outdated:**
- `drush.services.yml` -- deprecated Drush 12+, removal planned for Drush 14
- `@command` annotation in docblocks -- deprecated Drush 12, replaced by PHP 8 attributes
- `DrushCommands` base class -- deprecated Drush 13.7, replaced by Symfony Command
- `#[CLI\Command]` attribute -- deprecated Drush 13.7, replaced by `#[AsCommand]`
- `create()` factory method for Drush DI -- superseded by AutowireTrait (still works but not recommended)

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Eval pipeline (headless claude -p + eval-grader agent) |
| Config file | `skills/drupal-drush/evals/evals.json` (new -- Wave 0 gap) |
| Quick run command | `ddev drush list --filter=module_name` (verifies Drush command discovery) |
| Full suite command | Full A/B eval pipeline (headless with/without runs + grading) |

### Phase Requirements to Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| TOOL-01 | Drush skill teaches correct patterns | manual + static | Review SKILL.md content manually; verify WRONG/RIGHT callouts | -- Wave 0 |
| TOOL-02 | Eval assertions target non-obvious patterns | static | `cat skills/drupal-drush/evals/evals.json \| jq '.evals[0].expectations'` | -- Wave 0 |
| TOOL-03 | Eval-author produces three-tier assertions | manual | Run eval-author agent with test input; verify output has static + runtime + browser | -- Wave 0 |
| TOOL-04 | Assertion distribution enforced | manual | Count assertion categories in eval-author output; verify 60/20/20 | -- Wave 0 |

### Sampling Rate

- **Per task commit:** Verify skill file structure, assertion count, agent definition format
- **Per wave merge:** Run eval-author with Drush skill as test input; verify output quality
- **Phase gate:** Drush skill evals.json passes format validation; eval-author produces valid JSON output for at least one test scenario

### Wave 0 Gaps

- [ ] `skills/drupal-drush/SKILL.md` -- the primary deliverable (does not exist yet)
- [ ] `skills/drupal-drush/evals/evals.json` -- Drush-specific eval assertions
- [ ] `.claude/agents/eval-author.md` -- Opus subagent definition

## Open Questions

1. **Should the Drush skill teach DrushTestTrait for testing Drush commands?**
   - What we know: DrushTestTrait exists and is used for functional testing of Drush extensions. The drupal-testing skill already covers PHPUnit patterns.
   - What's unclear: Whether DrushTestTrait coverage belongs in the Drush skill or the testing skill. Adding it to both would duplicate content.
   - Recommendation: Include a brief cross-reference in the Drush skill pointing to drupal-testing. Do NOT duplicate DrushTestTrait content. Keep the Drush skill focused on command creation, not command testing.

2. **Should the eval-author agent produce browser assertions for the Drush skill?**
   - What we know: Drush commands have no browser-visible output. Browser assertions test rendered web pages.
   - What's unclear: Whether the eval-author should always produce all three tiers or skip tiers that don't apply.
   - Recommendation: The Drush skill's own evals will have NO browser assertions (Drush is CLI-only). The eval-author agent should be taught that browser assertions are tier-optional based on the phase scope. Not every phase needs browser checks.

3. **#[CLI\Command] vs #[AsCommand] as primary pattern in skill?**
   - What we know: `#[CLI\Command]` works Drush 12+. `#[AsCommand]` works Drush 13.7+ only but is the future direction. The project targets `^10 || ^11` which maps to Drush 12-13.
   - What's unclear: How quickly the Drush ecosystem will migrate. Whether `#[CLI\Command]` will be removed in Drush 14 or just deprecated further.
   - Recommendation: Use `#[CLI\Command]` + `extends DrushCommands` as the PRIMARY pattern (works across Drush 12-13.x). Show `#[AsCommand]` + `extends Command` as the D11/Drush 13.7+ forward-looking pattern. This mirrors the existing skill convention of showing both D10 and D11 syntax.

## Sources

### Primary (HIGH confidence)
- [Drush 13.x Command Authoring](https://www.drush.org/13.x/commands/) -- File location, PHP attributes, class structure, deprecated patterns
- [Drush 13.x Dependency Injection](https://www.drush.org/13.x/dependency-injection/) -- AutowireTrait, constructor injection, drush.services.yml deprecation
- [Drush AutowireTrait source](https://github.com/drush-ops/drush/blob/13.x/src/Commands/AutowireTrait.php) -- Verified trait exists in vendor
- [Drush 13.x Install/Compatibility](https://www.drush.org/13.x/install/) -- Drush 13 supports Drupal 10.2+ and 11, PHP 8.3+
- [Drush GitHub commands.md](https://github.com/drush-ops/drush/blob/13.x/docs/commands.md) -- Complete command authoring reference
- Existing eval-grader.md, eval-browser.md -- Agent frontmatter format verified (project-specific)
- Phase 18 evals.json -- Gold-standard assertion format with 17 assertions, +23.3% delta (project-specific)
- PITFALLS.md from v5.0 research -- Pitfalls #1 and #2 directly address this phase

### Secondary (MEDIUM confidence)
- [Drupalize.Me: Drush Custom Command Tutorials Updated](https://drupalize.me/blog/drush-custom-command-tutorials-updated) -- Migration examples from annotations to attributes, AutowireTrait usage
- [Drush GitHub releases](https://github.com/drush-ops/drush/releases) -- Drush 13.7 deprecates annotated commands, introduces first-class Symfony Console support
- [Drush ConfigCommands source](https://github.com/drush-ops/drush/blob/13.x/src/Commands/config/ConfigCommands.php) -- Real-world command example with AutowireTrait

### Tertiary (LOW confidence)
- Drush 14 plans -- AutowireTrait will be required, drush.services.yml removed. Timeline unclear.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- Drush 13 patterns verified against official docs, AutowireTrait confirmed in vendor directory, existing skill format well-established
- Architecture: HIGH -- Skill file structure follows 14 existing skills exactly; agent definition follows 3 existing agents exactly; assertion format follows v3/v4 evals exactly
- Pitfalls: HIGH -- Drush deprecated patterns verified via official docs; tautological assertion risk grounded in empirical Phase 18 data; all prevention strategies are prompt-level rules, not code

**Research date:** 2026-03-09
**Valid until:** 2026-04-09 (stable -- Drush 13.x is the current major version, agent format is project-internal)
