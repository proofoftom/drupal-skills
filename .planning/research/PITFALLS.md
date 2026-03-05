# Pitfalls Research

**Domain:** Encoding Drupal module development knowledge into Claude skills
**Researched:** 2026-03-05
**Confidence:** MEDIUM (novel domain -- few public precedents for framework-specific Claude skill authoring at this scale)

## Critical Pitfalls

### Pitfall 1: Annotation-Only Code Examples in a D11 World

**What goes wrong:**
The source book (Sipos, 4th ed, 2023) was written for Drupal 10 and uses Doctrine annotations exclusively for plugin definitions (`@Block`, `@ContentEntityType`, etc.). Drupal 11 deprecated annotations in favor of PHP 8 attributes (`#[Block(...)]`). A skill that only shows annotation syntax will produce code that triggers deprecation notices on D11 and will stop working entirely when annotations are removed in a future major release.

**Why it happens:**
The book explicitly acknowledges this transition: "Annotations are very similar in concept to PHP 8 attributes, which will someday replace the Doctrine annotations. The plan is, in fact, to deprecate the latter over the course of the Drupal 10 release cycle." Skill authors who extract code examples verbatim from the book will embed annotation-only patterns.

**How to avoid:**
Every skill that covers plugin types (blocks, entities, field types, views plugins, mail plugins, etc.) must show BOTH annotation and attribute syntax. The attribute syntax should be presented as the primary/preferred form, with annotation syntax noted as D10 legacy. The `drupal-plugins-blocks`, `drupal-entities-fields`, `drupal-views-dev`, and `drupal-batch-queue-cron` skills are all affected.

**Warning signs:**
- Code examples contain `@Block`, `@ContentEntityType`, `@FieldType` without corresponding `#[Block]`, `#[ContentEntityType]`, `#[FieldType]` equivalents
- No mention of PHP 8 attributes anywhere in the skill
- D11 compatibility section is absent or marked "TODO"

**Phase to address:**
Wave 1 -- must be established as a pattern in the very first skills (`drupal-entities-fields`, `drupal-plugins-blocks`). All subsequent waves inherit the convention.

---

### Pitfall 2: Skills That Are Reference Docs Instead of Decision Guides

**What goes wrong:**
Skills become exhaustive API reference dumps -- listing every hook, every render element, every form element type. Claude already knows Drupal APIs from training data. What it lacks is judgment: when to use a config entity vs. a content entity, when to use State API vs. TempStore, when a custom plugin type is warranted vs. using an existing one. A skill that restates API surface produces no improvement over baseline Claude.

**Why it happens:**
The book is structured as a teaching reference. The natural extraction approach is to compress each chapter into a smaller version of itself. This produces mini-reference-docs, not decision-making frameworks.

**How to avoid:**
Structure each skill around decision trees and "when to use what" guidance, not API listings. Each skill should answer: "Given a developer's request, what is the RIGHT approach and what are the WRONG approaches that Claude might otherwise suggest?" Include anti-patterns and common mistakes, not just correct patterns.

**Warning signs:**
- Skill reads like a compressed book chapter
- No "when to use" / "when NOT to use" sections
- Eval shows with-skill output is barely different from no-skill output
- Skill contains lists of all available options without prioritization

**Phase to address:**
Wave 1 -- the skill template/structure must enforce decision-guide format from the start. The eval loop (Step 7) will expose this if the template does not.

---

### Pitfall 3: Trigger Description Too Broad or Too Narrow

**What goes wrong:**
A skill's `description` frontmatter field determines when Claude loads it. Too broad: "Drupal module development" triggers on every Drupal question, wasting context window on irrelevant skills. Too narrow: "Creating custom block plugins with PHP annotations" never triggers when someone asks "add a block that shows recent content."

**Why it happens:**
Description optimization is treated as an afterthought. The natural instinct is to describe what the skill contains rather than what user prompts should activate it. With 13 skills, overlapping triggers are almost guaranteed without deliberate tuning.

**How to avoid:**
- Write descriptions from the user's perspective: what will the developer ASK that should trigger this skill?
- Use the description optimization loop (Step 8 in plan) seriously -- do not skip it
- Test each skill's trigger against a matrix of realistic prompts
- Test for NON-triggering: ensure `drupal-caching` does NOT trigger on "create a form" prompts
- Consider prompt categories and ensure each maps to exactly one primary skill

**Warning signs:**
- Multiple skills trigger simultaneously on simple prompts (context window bloat)
- Skill never triggers in eval despite relevant prompts
- Description uses internal/technical vocabulary instead of developer-intent language

**Phase to address:**
Wave 1 for initial drafts, but primarily Step 8 (description optimization) after all skills exist. Must be done holistically across all 13 skills, not per-skill.

---

### Pitfall 4: Cross-Reference Loops and Circular Dependencies

**What goes wrong:**
Skill A says "see drupal-entities-fields for entity definitions" and Skill B says "see drupal-routing-controllers for route registration." When Claude encounters a task spanning both (e.g., "create a custom entity with an admin page"), it loads both skills and gets conflicting or redundant guidance. Worse, if skills assume the other skill's content is loaded, they each omit critical context.

**Why it happens:**
The 13 skills are derived from overlapping book chapters. Entities need routes. Routes need forms. Forms need entities. Blocks need plugins and entities. The dependency graph is dense. The project plan explicitly calls for cross-references, but without a clear ownership model, this produces ambiguity.

**How to avoid:**
- Define clear ownership: each Drupal concept belongs to exactly ONE skill
- Cross-references should be directional: "this skill covers X; if you also need Y, the drupal-Y skill covers that" -- never assume both are loaded
- Each skill must be self-contained enough to produce correct code for its domain without requiring another skill to be active
- Create a concept-to-skill mapping table and verify no concept is claimed by two skills

**Warning signs:**
- Two skills contain overlapping code examples for the same pattern
- A skill says "as described in drupal-X" without providing enough inline context to function alone
- Eval prompts that span two domains produce confused or contradictory output

**Phase to address:**
Wave 1 planning -- the concept ownership table must exist before any skill is drafted. Verified during each wave's review step (Steps 3, 4, 5, 6).

---

### Pitfall 5: 500-Line Limit Forces Shallow Coverage of Deep Topics

**What goes wrong:**
The `drupal-entities-fields` skill covers content entities, config entities, TypedData, custom fields, file/image handling, and entity CRUD -- sourced from 4 book chapters spanning ~3000 lines. Compressing this into 500 lines forces either: (a) shallow coverage of everything, producing a skill that is correct but unhelpful, or (b) deep coverage of some topics and omission of others, producing a skill with blind spots.

**Why it happens:**
The 500-line constraint is a skill-creator anatomy rule, likely tied to context window efficiency. The entity/field system is the most complex domain in Drupal development. The skill plan groups it as one skill because it represents one conceptual domain, but the source material is vastly larger than other skills.

**How to avoid:**
- Use reference files aggressively: the skill body covers decision logic and core patterns; reference files (`references/config-entities.md`, `references/field-types.md`, `references/file-handling.md`) contain detailed API patterns
- The body should be a routing layer: "For content entities, follow this pattern [core example]. For config entities, see references/config-entities.md"
- Prioritize ruthlessly: content entities with fields are the 80% case; config entities and TypedData are 20%
- Apply the same strategy to other large skills: `drupal-theming` (Ch 4 + Ch 12), `drupal-access-security` (Ch 10 + Ch 18)

**Warning signs:**
- Skill body exceeds 500 lines and gets truncated or stripped
- Eval shows correct output for simple entity tasks but wrong/incomplete output for complex ones (config entities, custom field widgets)
- Reference files exist but are never loaded because the body does not guide Claude to them

**Phase to address:**
Wave 1 -- `drupal-entities-fields` is the hardest skill and is scheduled first precisely to establish the reference-file pattern. If this skill's structure is wrong, all complex skills in later waves will inherit the problem.

---

### Pitfall 6: Drupal Version Confusion in Generated Code

**What goes wrong:**
Claude's training data contains Drupal 7, 8, 9, 10, and 11 code. Without strong version anchoring, skills may reinforce D7/D8 patterns (e.g., `hook_menu()` instead of routing YAML, `db_query()` instead of database service injection, `variable_get()` instead of State API). A skill that does not forcefully override legacy patterns will produce a mix of correct and incorrect code.

**Why it happens:**
Drupal has undergone radical architectural changes across major versions. D7-to-D8 was a complete rewrite (procedural to OOP/Symfony). D8-to-D10 was evolutionary. D10-to-D11 introduced PHP attributes. Claude's training over-represents D7/D8 content because there are more blog posts, StackOverflow answers, and tutorials for older versions. The book is D10, but Claude may blend in older patterns.

**How to avoid:**
- Each skill must explicitly state: "This skill covers Drupal 10.x and 11.x. Do NOT use patterns from Drupal 7 or 8."
- Include explicit anti-patterns: "Do NOT use `db_query()` -- use `\Drupal::database()->query()` or inject the database service"
- Include a "legacy patterns to avoid" section in every skill listing the D7/D8 equivalents that Claude should never generate
- Test evals specifically for version regression: prompt with tasks that have different solutions in D7 vs D10

**Warning signs:**
- Generated code uses procedural hooks that were replaced by plugins/services in D8+
- `hook_menu()`, `db_select()`, `variable_get()`, `drupal_set_message()` appear in output
- Services are accessed via `\Drupal::service()` static calls instead of dependency injection

**Phase to address:**
Wave 1 -- version anchoring must be in every skill from the start. The eval loop should include at least one "regression bait" prompt per skill.

---

### Pitfall 7: Ignoring the .info.yml / .routing.yml / .services.yml Ecosystem

**What goes wrong:**
A skill teaches how to write a controller class but forgets that the developer also needs a `.routing.yml` entry, a `.services.yml` entry for dependency injection, and possibly a `.permissions.yml` and `.links.menu.yml`. Drupal modules are defined by a constellation of YAML files plus PHP classes. A skill that only covers the PHP side produces code that does not actually work.

**Why it happens:**
Book chapters often present YAML and PHP together, but when extracting into skills, it is easy to focus on the "interesting" PHP logic and treat YAML as boilerplate. Claude can generate YAML but often gets the structure wrong (wrong indentation, wrong keys, wrong service class paths).

**How to avoid:**
- Every skill that produces PHP code must also specify the corresponding YAML files
- Use complete, copy-pasteable examples: "To create a route, you need BOTH the .routing.yml entry AND the controller class"
- The `drupal-module-scaffold` skill should establish the full file map for a module
- Other skills should reference back to scaffold for the file structure and add their domain-specific YAML

**Warning signs:**
- Skill contains PHP classes but no YAML examples
- Generated code works in isolation but fails because routing/services/permissions YAML is missing
- Eval prompts ask "create a block" and get a Block plugin class but no mention of module .info.yml

**Phase to address:**
Wave 1 -- `drupal-module-scaffold` must establish the file-ecosystem pattern. All skills in subsequent waves inherit and extend it.

---

## Moderate Pitfalls

### Pitfall 8: Eval Prompts Not Grounded in Real Projects

**What goes wrong:**
Test prompts are abstract ("create a custom block plugin") instead of grounded in the `os-knowledge-garden` codebase ("add a block to social_ai_indexing that shows the indexing queue status"). Abstract prompts test whether Claude can generate generic Drupal code, which it can already do. They do not test whether the skill improves Claude's ability to write code that fits into a real project's patterns, services, and conventions.

**Why it happens:**
Writing grounded prompts requires understanding the test project's architecture, existing services, and realistic tasks. Abstract prompts are faster to write.

**How to avoid:**
- Every skill must have 2-3 eval prompts that reference specific modules, services, or patterns from `os-knowledge-garden`
- Prompts should require Claude to integrate with existing code (e.g., "add a route to the localnodes_platform module that...")
- Include at least one prompt that tests a common mistake Claude makes without the skill

**Warning signs:**
- All eval prompts could apply to any Drupal project
- Eval prompts do not mention any file, service, or module from `os-knowledge-garden`

**Phase to address:**
Wave 1 drafting, verified during review steps (Steps 3, 4, 5, 6).

---

### Pitfall 9: Skill Content Contradicts Claude's Training Data

**What goes wrong:**
A skill states a pattern that directly contradicts what Claude "knows" from training. For example, if the skill says "always use `\Drupal::service()` for service access" but Claude's training strongly favors constructor injection, Claude may ignore the skill or produce a confused hybrid. Skills must work WITH Claude's existing knowledge, correcting only where it is wrong.

**Why it happens:**
Skill authors may not know what Claude already knows about Drupal. They write skills as if teaching from scratch, potentially including advice that conflicts with correct patterns Claude already has.

**How to avoid:**
- Before drafting each skill, run a baseline (no-skill) test to see what Claude generates
- Skills should CORRECT mistakes in Claude's baseline, not restate what Claude already does correctly
- Frame corrections as explicit overrides: "Claude may suggest X -- instead, do Y because Z"
- The eval loop is specifically designed to reveal this; do not skip baseline comparisons

**Warning signs:**
- Skill restates common Drupal patterns that Claude already generates correctly
- With-skill output quality is the same as no-skill output
- Skill advice contradicts Drupal.org best practices

**Phase to address:**
Step 7 (eval loop) -- baseline comparison is the primary detection mechanism. But skill drafters in Waves 1-4 should run informal baseline checks before writing.

---

### Pitfall 10: Monolithic Skills That Should Be Split

**What goes wrong:**
A skill tries to cover too many sub-domains and becomes a grab-bag of loosely related patterns. Example: `drupal-batch-queue-cron` combines batch processing, queue workers, cron hooks, logging, mail sending, and token handling. These are distinct developer tasks with different trigger prompts. A developer asking "how to send email" should not have to load batch processing guidance.

**Why it happens:**
The skill groupings were designed around book chapter proximity, not developer workflow. Ch 14 (batch/queue/cron) and Ch 3 (logging/mail) are separate chapters folded into one skill.

**How to avoid:**
- Validate each skill grouping against real developer prompts: would a developer ever ask about ALL these topics together?
- If sub-topics always appear independently, consider splitting
- Use reference files to isolate secondary topics: the main skill body covers the primary topic, and reference files cover adjacent concerns
- The 13-skill count is not sacred -- 14 or 15 skills may be better if splitting improves trigger precision

**Warning signs:**
- A skill's description is a compound sentence with "and" linking unrelated concepts
- The skill triggers on prompts where only 20% of its content is relevant
- Eval shows that loading the skill for a narrow prompt adds noise

**Phase to address:**
Wave 4 (when these compound skills are drafted), but the question should be asked during Wave 1 planning for all skill groupings.

---

### Pitfall 11: Not Testing Skill Interactions

**What goes wrong:**
Each skill is evaluated independently, but in real usage, multiple skills may be loaded simultaneously. Skill A says "define services in .services.yml" and Skill B says "register event subscribers in .services.yml with tags." These are compatible, but if both skills provide complete .services.yml examples, Claude may generate two separate files or duplicate entries.

**Why it happens:**
The eval plan (Step 7) tests each skill independently with specific prompts. There is no step for testing multi-skill scenarios.

**How to avoid:**
- Add a multi-skill eval phase: prompts that span 2-3 skills ("create a module with a custom entity, an admin form, and proper caching")
- Verify that skills do not produce conflicting file structures
- Ensure skills use additive patterns ("add this to .services.yml") not replacement patterns ("your .services.yml should look like this")

**Warning signs:**
- Individual skill evals pass but real-world usage produces broken modules
- Two skills provide complete examples of the same file (.module, .services.yml)
- Developer reports that Claude generates contradictory advice

**Phase to address:**
Step 9 (ad-hoc audit) -- but should be formalized as a multi-skill eval step between Steps 7 and 8.

---

## Minor Pitfalls

### Pitfall 12: Neglecting Drupal Coding Standards in Examples

**What goes wrong:**
Code examples in skills use inconsistent formatting, non-standard naming conventions, or missing doc blocks. Claude reproduces these in generated code. Drupal has strict coding standards (Drupal CS) that differ from PSR-12 in several ways (e.g., array syntax, comment formatting, function naming).

**Prevention:**
All code examples in skills must pass `phpcs --standard=Drupal`. Include a note in each skill: "Follow Drupal coding standards (drupal.org/docs/develop/standards)."

---

### Pitfall 13: Hardcoded Module Names in Examples

**What goes wrong:**
Skills use `hello_world` or `mymodule` as example module names. Claude then generates code with these placeholder names instead of adapting to the developer's actual module name. This is especially problematic for namespaces, service IDs, and YAML keys.

**Prevention:**
Use descriptive but clearly-placeholder names like `{module_name}` or use the test project's actual module names (e.g., `social_ai_indexing`) in examples. Explicitly instruct Claude to adapt names to the developer's context.

---

### Pitfall 14: Missing Error Handling and Edge Cases

**What goes wrong:**
Book examples are pedagogical and omit error handling, access checks, and edge cases. Skills that reproduce these clean examples produce code that works in demos but fails in production (missing null checks, no try/catch around external calls, no access control on routes).

**Prevention:**
Add a "production checklist" to each skill: access checks, input validation, error handling, logging. The `drupal-access-security` skill should be cross-referenced from every other skill.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Annotation-only examples (skip attributes) | Faster skill drafting, direct book extraction | Skills produce deprecated code on D11; requires rewrite | Never -- D11 is already released |
| Skipping eval loop for "simple" skills | Ships faster | No evidence the skill improves over baseline; may waste context window | Never -- even simple skills need baseline comparison |
| Copy-pasting book code verbatim | Ensures accuracy to source | Code may use deprecated APIs, lacks context for when/why to use it | Only for reference files, never for skill body |
| Single eval prompt per skill | Minimum viable validation | Misses edge cases and alternative trigger patterns | Only for MVP wave; must expand before Step 8 |
| Skipping description optimization (Step 8) | Saves time | Skills trigger incorrectly, overlap, or never activate | Never -- this is the highest-ROI step for 13 co-existing skills |

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Skill + Claude training data | Skill restates what Claude knows, wasting tokens | Use baseline eval to identify gaps; skill corrects only what Claude gets wrong |
| Skill + reference files | Reference files exist but skill body never directs Claude to load them | Skill body must contain explicit "see references/X.md for Y" directives |
| Skill + skill (multi-skill) | Skills assume exclusive context, produce conflicting advice | Each skill must be additive; never produce complete file replacements |
| Skill + os-knowledge-garden | Eval prompts are generic, not grounded in test project | Prompts must reference specific modules, services, and files from the test project |
| Skill + skill-creator eval | Eval assertions are too loose ("output contains PHP") | Assertions must check for specific patterns, correct service injection, proper YAML structure |

## "Looks Done But Isn't" Checklist

- [ ] **Skill body:** Contains decision logic, not just API reference -- verify by checking for "when to use" sections
- [ ] **D11 compatibility:** Every plugin example shows both annotation AND attribute syntax -- verify by searching for `#[`
- [ ] **YAML files:** Every PHP class example has corresponding YAML config -- verify by checking for `.routing.yml`, `.services.yml` mentions
- [ ] **Anti-patterns:** Skill explicitly lists what NOT to do -- verify by searching for "do not" / "avoid" / "instead"
- [ ] **Version anchoring:** Skill states "Drupal 10.x/11.x" and lists legacy patterns to avoid -- verify top-of-skill version statement
- [ ] **Cross-references:** References to other skills are informational, not load-bearing -- verify skill works without the referenced skill loaded
- [ ] **Description field:** Written from developer-intent perspective, not skill-content perspective -- verify it describes prompts, not topics
- [ ] **Eval prompts:** At least one prompt references os-knowledge-garden specifically -- verify module/service names appear
- [ ] **Reference files:** If skill has reference files, body contains explicit "see references/X" directives -- verify linkage
- [ ] **Coding standards:** Code examples follow Drupal CS (not PSR-12) -- verify array syntax, doc blocks, naming

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Annotation-only examples | LOW | Add `#[Attribute]` variants next to each `@Annotation` -- mechanical transformation |
| Reference-doc-style skills | HIGH | Requires full rewrite around decision trees; cannot be patched incrementally |
| Bad trigger descriptions | LOW | Run description optimization loop (Step 8); automated process |
| Cross-reference loops | MEDIUM | Build concept ownership table, audit all cross-references, rewrite overlapping sections |
| 500-line overflow | MEDIUM | Extract to reference files; requires restructuring body as routing layer |
| Version confusion | LOW | Add version anchoring header and legacy-pattern blacklist to each skill |
| Missing YAML examples | MEDIUM | Audit each skill for PHP-without-YAML gaps; add YAML examples |
| Non-grounded eval prompts | LOW | Write new prompts referencing os-knowledge-garden; no skill changes needed |
| Training data conflicts | HIGH | Requires baseline testing and careful reframing of skill guidance |
| Monolithic skills | HIGH | Splitting a published skill breaks user installations; must get grouping right initially |
| No multi-skill testing | MEDIUM | Add multi-skill eval prompts; may reveal skill conflicts requiring edits |

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Annotation-only code (P1) | Wave 1 skill drafting | Search all skills for `#[` attribute syntax |
| Reference-doc skills (P2) | Wave 1 template design | Eval with-skill vs no-skill diff shows meaningful improvement |
| Bad trigger descriptions (P3) | Step 8 (description optimization) | Cross-skill trigger matrix with no overlaps |
| Cross-reference loops (P4) | Pre-Wave 1 planning | Concept ownership table with no duplicate claims |
| 500-line overflow (P5) | Wave 1 (`drupal-entities-fields`) | All skill bodies under 500 lines; reference files linked |
| Version confusion (P6) | Wave 1 template design | No D7/D8 patterns in eval output |
| Missing YAML (P7) | Wave 1 (`drupal-module-scaffold`) | Every PHP example has corresponding YAML |
| Non-grounded evals (P8) | All wave review steps | Eval prompts mention os-knowledge-garden files |
| Training conflicts (P9) | Step 7 (eval loop) | Baseline comparison shows improvement, not regression |
| Monolithic skills (P10) | Pre-Wave 1 planning | Each skill maps to a distinct developer intent |
| No multi-skill testing (P11) | Post-Step 7, before Step 8 | Multi-domain prompts produce coherent output |
| Coding standards (P12) | All wave drafting | Code passes phpcs --standard=Drupal |
| Hardcoded names (P13) | All wave drafting | Examples use placeholder or real project names |
| Missing error handling (P14) | All wave drafting | Production checklist present in each skill |

## Sources

- Sipos D. "Drupal 10 Module Development" 4th ed, 2023 -- source material analysis (line 674 on annotations/attributes transition)
- `polished-tickling-owl.md` -- existing project execution plan with wave structure and eval workflow
- `os-knowledge-garden/CLAUDE.md` -- test project context and Drupal conventions
- `~/.claude/skills/context7/SKILL.md` -- existing Claude skill as reference for anatomy and structure
- PROJECT.md -- project constraints (500-line limit, 13 skills, skill-creator eval framework)
- Direct analysis of Claude's known Drupal training data tendencies (version mixing, annotation defaults)

---
*Pitfalls research for: Claude skills for Drupal module development*
*Researched: 2026-03-05*
