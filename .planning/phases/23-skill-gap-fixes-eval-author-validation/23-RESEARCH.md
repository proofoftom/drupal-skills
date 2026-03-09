# Phase 23: Skill Gap Fixes + Eval-Author Validation - Research

**Researched:** 2026-03-09
**Domain:** Drupal skill content authoring (entities, caching, forms AJAX), eval-author agent validation
**Confidence:** HIGH

## Summary

Phase 23 has two orthogonal workstreams: (1) closing three documented skill gaps in entities-fields, caching, and forms-api SKILL.md files, and (2) validating that the eval-author agent (built in Phase 22) produces assertions of comparable quality to the hand-crafted Phase 18 gold-standard (17 assertions, +23.3% delta).

The skill gap fixes are well-scoped. The entities-fields skill needs `bundle_of` config entity + `hook_update_N()` patterns. The caching skill needs CacheableMetadata bubbling for controller responses (lazy_builder is already covered). The forms-api skill needs concrete `#ajax` patterns with callback, wrapper, and AjaxResponse.

A critical budget constraint exists: entities-fields SKILL.md is at 497/500 lines -- it has no room for new content. The bundle_of + hook_update_N content (~40-50 lines) MUST go into a reference file at `references/bundled-entities.md`, following the existing `references/files-images.md` pattern. The caching skill has 139 lines of budget, and forms-api has 60 lines of budget -- both sufficient for their additions.

The eval-author validation is the higher-risk workstream. The agent must be invoked with Phase 18 inputs (skill paths, prompt, module code) and its output compared against the known-good Phase 18 evals. Comparison is qualitative (human review), not automated -- we check that the agent produces assertions targeting the same non-obvious patterns at comparable specificity. A validation pass requires: (a) comparable assertion count (12-22), (b) distribution meeting 60/20/20 thresholds, (c) assertions targeting the same core differentiators (CacheableJsonResponse, _csrf_request_header_token, _format:json, entity upcasting, once() guard).

**Primary recommendation:** Fix skill gaps first (plan 23-01), then validate eval-author (plan 23-02). Skill fixes are independent of the agent and de-risk the phase.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| TOOL-05 | Eval-author output validated against Phase 18 gold-standard before relying on it for new phases | Eval-author agent exists at `.claude/agents/eval-author.md`. Phase 18 gold-standard at `eval/v4/phase-18-evals.json` (17 assertions, +23.3% delta). Validation approach: invoke agent with Phase 18 inputs, compare output qualitatively against gold-standard for pattern coverage, distribution, and rationale quality. |
| TOOL-06 | entities-fields skill updated with bundle_of pattern and hook_update_N() for schema changes | Current SKILL.md mentions bundles at line 31 but has no code example. Skill is at 497/500 lines -- new content MUST go to `references/bundled-entities.md`. Drupal official docs confirm bundle_of on ConfigEntityType + bundle_entity_type on ContentEntityType + ConfigEntityBundleBase pattern. hook_update_N uses EntityDefinitionUpdateManager::installFieldStorageDefinition(). |
| TOOL-07 | caching skill updated with lazy_builder pattern and CacheableMetadata bubbling | lazy_builder already has complete coverage (lines 140-211). What is missing: CacheableMetadata bubbling in controller responses -- specifically using CacheableMetadata::createFromObject() and addCacheableDependency() to aggregate metadata across multiple entity loads and apply to CacheableJsonResponse. 139 lines of budget available. |
</phase_requirements>

## Standard Stack

### Core

No new libraries or dependencies. Phase 23 modifies only knowledge artifacts (SKILL.md files) and validates an existing agent.

| Artifact | Type | Change | Budget |
|----------|------|--------|--------|
| `skills/drupal-entities-fields/SKILL.md` | Skill file | Add cross-reference to new reference file | 497 -> ~500 lines |
| `skills/drupal-entities-fields/references/bundled-entities.md` | Reference file | NEW: bundle_of + hook_update_N patterns | ~80-100 lines |
| `skills/drupal-caching/SKILL.md` | Skill file | Add CacheableMetadata bubbling section | 361 -> ~390 lines |
| `skills/drupal-forms-api/SKILL.md` | Skill file | Add #ajax section | 440 -> ~495 lines |
| `.claude/agents/eval-author.md` | Agent definition | No changes -- validation only | 203 lines (unchanged) |

### Existing Reference File Pattern

The entities-fields skill already uses reference files:
- `skills/drupal-entities-fields/references/files-images.md` (131 lines)

This established pattern works: the main SKILL.md cross-references the file, and Claude reads it when the topic is relevant. The bundle_of + hook_update_N content should follow this exact pattern.

## Architecture Patterns

### Pattern 1: Reference File for Budget-Constrained Skills

**What:** When a SKILL.md hits its ~500 line budget, move supplementary patterns to `references/*.md` files and add a cross-reference line to the main file.

**When to use:** entities-fields is at 497 lines. Cannot add 40+ lines of bundle_of content inline.

**Example:**

In SKILL.md, add near the bundle decision tree (line 31):
```markdown
YES -> Add `bundle_entity_type`, `bundle_label`, `bundle_of` (on the config entity), and `field_ui_base_route`. Create a companion ConfigEntityType to define bundles. See `references/bundled-entities.md` in this skill directory for the complete pattern with hook_update_N().
```

In `references/bundled-entities.md`:
```markdown
# Bundled Entity Types

## The bundle_of pattern

Content entities with subtypes (like nodes having "article" and "page" types)
need TWO entity types working together...
```

### Pattern 2: entities-fields bundle_of Content

**What:** A config entity with `bundle_of` pointing to the content entity, paired with `bundle_entity_type` on the content entity and `ConfigEntityBundleBase` as the base class.

**Key code (D10 annotation):**

Content entity adds:
```php
/**
 * @ContentEntityType(
 *   id = "message",
 *   bundle_entity_type = "message_type",
 *   bundle_label = @Translation("Message type"),
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   field_ui_base_route = "entity.message_type.edit_form",
 * )
 */
class Message extends ContentEntityBase { }
```

Config entity (the bundle definition):
```php
/**
 * @ConfigEntityType(
 *   id = "message_type",
 *   label = @Translation("Message type"),
 *   bundle_of = "message",
 *   config_prefix = "type",
 *   config_export = { "id", "label" },
 * )
 */
class MessageType extends ConfigEntityBundleBase { }
```

Source: [Drupal Entity API - Create a custom content entity with bundles](https://www.drupal.org/docs/drupal-apis/entity-api/create-a-custom-content-entity-with-bundles)

**WRONG/RIGHT callout:**
> WRONG: Creating a content entity with `bundle_entity_type = "message_type"` but forgetting `bundle_of = "message"` on the config entity. The content entity declares it has bundles, but the config entity does not know it provides bundles. Result: Field UI has no bundle-specific field management, and `\Drupal::entityTypeManager()->getDefinition('message')->getBundleEntityType()` returns NULL.
> RIGHT: The content entity has `bundle_entity_type` and `entity_keys.bundle`, and the config entity has `bundle_of` pointing back. Both sides MUST be configured. Also extend `ConfigEntityBundleBase` (not plain `ConfigEntityBase`) for the bundle entity -- it provides bundle-aware methods.

### Pattern 3: hook_update_N() for Adding Base Fields

**What:** When adding a new base field to an existing entity type, you must also provide a `hook_update_N()` function so existing installations get the database schema change.

**Key code:**
```php
/**
 * Add the 'priority' base field to the task entity.
 */
function my_module_update_10001() {
  $field_storage_definition = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Priority'))
    ->setDescription(t('Task priority level.'))
    ->setSettings([
      'allowed_values' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
      ],
    ])
    ->setDefaultValue('medium')
    ->setDisplayOptions('form', [
      'type' => 'options_select',
    ]);

  \Drupal::entityDefinitionUpdateManager()
    ->installFieldStorageDefinition(
      'priority',
      'task',
      'my_module',
      $field_storage_definition
    );
}
```

Source: [Drupal Update API - Updating Entities and Fields](https://www.drupal.org/docs/drupal-apis/update-api/updating-entities-and-fields-in-drupal-8)

**WRONG/RIGHT callout:**
> WRONG: Adding a new field to `baseFieldDefinitions()` without a corresponding `hook_update_N()`. The field works on fresh installs but existing sites get a database schema mismatch: the entity storage expects the column but the table lacks it. Result: "Unknown column" SQL errors on any entity query that touches the new field.
> RIGHT: ALWAYS pair `baseFieldDefinitions()` changes with a `hook_update_N()` that calls `\Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition()`. The field definition must be repeated in the update hook (not referenced from the entity class) because entity class definitions may change between updates.

### Pattern 4: CacheableMetadata Bubbling in Controller Responses

**What:** When a controller loads multiple entities and returns a response (render array or CacheableJsonResponse), cache metadata from ALL loaded entities must be aggregated and applied to the response. Otherwise the response is cached without proper invalidation tags.

**Key code for render array controllers:**
```php
public function list() {
  $build = ['#theme' => 'my_list', '#items' => []];
  $cache_metadata = new CacheableMetadata();
  $cache_metadata->addCacheTags(['my_entity_list']);
  $cache_metadata->addCacheContexts(['user.permissions']);

  $entities = $this->entityTypeManager->getStorage('my_entity')
    ->loadMultiple();
  foreach ($entities as $entity) {
    $cache_metadata->addCacheableDependency($entity);
    $build['#items'][] = [...];
  }

  $cache_metadata->applyTo($build);
  return $build;
}
```

**Key code for JSON controllers:**
```php
public function apiList() {
  $data = [];
  $cache_metadata = new CacheableMetadata();
  $cache_metadata->addCacheTags(['my_entity_list']);
  $cache_metadata->addCacheContexts(['user.permissions']);

  $entities = $this->entityTypeManager->getStorage('my_entity')
    ->loadMultiple();
  foreach ($entities as $entity) {
    $cache_metadata->addCacheableDependency($entity);
    $data[] = $this->serialize($entity);
  }

  $response = new CacheableJsonResponse($data);
  $response->addCacheableDependency($cache_metadata);
  return $response;
}
```

**WRONG/RIGHT callout:**
> WRONG: Loading multiple entities in a controller but only adding cache tags for the first entity, or hardcoding tags like `['node_list']` without also adding per-entity tags. Changes to specific entities do not invalidate the response because their individual tags are missing.
> RIGHT: Create a CacheableMetadata object, call `addCacheableDependency($entity)` for EACH loaded entity in the loop, and apply the aggregated metadata to the render array or response. This captures both individual entity tags AND entity type list tags automatically.

### Pattern 5: forms-api #ajax Content

**What:** The `#ajax` property on form elements triggers asynchronous callbacks that replace part of the page without a full reload.

**Key code:**
```php
public function buildForm(array $form, FormStateInterface $form_state) {
  $form['status'] = [
    '#type' => 'select',
    '#title' => $this->t('Status'),
    '#options' => ['draft' => 'Draft', 'published' => 'Published'],
    '#ajax' => [
      'callback' => '::statusCallback',
      'wrapper' => 'status-result',
    ],
  ];

  $form['status_result'] = [
    '#type' => 'container',
    '#attributes' => ['id' => 'status-result'],
    '#markup' => '',
  ];

  return $form;
}

public function statusCallback(array &$form, FormStateInterface $form_state) {
  // Return the element that matches the wrapper ID
  return $form['status_result'];
}
```

**WRONG/RIGHT callout (wrapper matching):**
> WRONG: Setting `'wrapper' => 'status-result'` in `#ajax` but the target element has `'#attributes' => ['id' => 'result-wrapper']`. The wrapper ID does not match any element in the DOM. Result: the AJAX callback fires but the replacement silently fails -- no error in the browser console, no visual update. This is the #1 AJAX debugging trap.
> RIGHT: The `#ajax.wrapper` value MUST exactly match the `#attributes.id` of the container element being replaced. Both values are plain strings (no `#` prefix in either place).

**AjaxResponse for multi-command callbacks:**
```php
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;

public function statusCallback(array &$form, FormStateInterface $form_state) {
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#status-result', $form['status_result']));
  $response->addCommand(new MessageCommand($this->t('Status updated.')));
  return $response;
}
```

**When to use each callback return type:**
- Return a **render array** (form element) when replacing a single wrapper -- simplest approach, Drupal handles the replacement automatically
- Return an **AjaxResponse** when you need multiple DOM updates, messages, redirects, or other side effects

**AJAX in tables (unique wrapper per row):**
```php
foreach ($entities as $id => $entity) {
  $form['tasks'][$id]['status'] = [
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $entity->get('status')->value,
    '#ajax' => [
      'callback' => '::updateTaskStatus',
      'wrapper' => 'task-row-' . $id,
    ],
  ];
  $form['tasks'][$id]['#attributes']['id'] = 'task-row-' . $id;
}
```

### Anti-Patterns to Avoid

- **entities-fields: Inline content in budget-exhausted skills:** Adding 50 lines to a 497-line skill. Move to reference files instead.
- **caching: Documenting lazy_builder twice:** The lazy_builder section already exists. Add CacheableMetadata bubbling as a new subsection, not a rewrite.
- **forms-api: Comprehensive AJAX reference:** Only 60 lines available. Cover callback, wrapper matching, AjaxResponse, and AJAX in tables. Skip #ajax.effect, #ajax.progress, advanced AJAX patterns.
- **eval-author validation: Exact match expectation:** The eval-author will produce DIFFERENT assertions than the gold-standard (different wording, potentially different ordering). Validation checks pattern coverage and quality, not text equality.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| bundle_of documentation | Write from scratch | Adapt from Drupal official docs pattern | Official docs have the authoritative pattern with ConfigEntityBundleBase |
| hook_update_N examples | Invent new patterns | Follow EntityDefinitionUpdateManager::installFieldStorageDefinition() | This is the documented Drupal API; alternatives risk data corruption |
| AJAX form patterns | Comprehensive AJAX guide | Targeted callback + wrapper + AjaxResponse | 60-line budget means prioritizing the patterns that actually differentiate eval scores |
| Eval-author validation | Automated assertion diffing | Human qualitative comparison against gold-standard | Assertion text varies; what matters is pattern coverage and distribution |

## Common Pitfalls

### Pitfall 1: entities-fields SKILL.md Line Budget Exceeded
**What goes wrong:** Adding bundle_of content inline pushes the skill past 500 lines. Long skills degrade Claude's ability to follow guidance (attention dilution).
**Why it happens:** The ARCHITECTURE research estimated "300-400 lines" but the actual skill is 497 lines.
**How to avoid:** Use the existing reference file pattern. Add only a cross-reference line (~1 line change) to SKILL.md. Put the bundle_of + hook_update_N content in `references/bundled-entities.md`.
**Warning signs:** `wc -l` shows >500 after edit.

### Pitfall 2: Duplicating Existing Caching Content
**What goes wrong:** Adding lazy_builder content that already exists (lines 140-211), wasting budget and creating maintenance burden.
**Why it happens:** The ROADMAP says "includes lazy_builder pattern" -- researcher reads this as "add lazy_builder" when it already exists.
**How to avoid:** Verify current SKILL.md content before writing. The gap is CacheableMetadata bubbling in controller responses, NOT lazy_builder itself. Add ~25 lines for the bubbling pattern, leave lazy_builder untouched.
**Warning signs:** `grep -c lazy_builder SKILL.md` returns > 10 matches (already comprehensive).

### Pitfall 3: forms-api AJAX Section Exceeds Budget
**What goes wrong:** Comprehensive AJAX coverage (callback types, event triggers, effect options, progress indicators, nested AJAX, etc.) pushes past 500 lines.
**Why it happens:** AJAX is a deep topic. Tempting to cover everything.
**How to avoid:** Budget is 60 lines. Cover ONLY: (1) basic #ajax with callback and wrapper, (2) wrapper ID matching WRONG/RIGHT, (3) AjaxResponse for multi-command, (4) AJAX in tables with unique per-row wrappers. These are the patterns that appeared in Phase 20 evals.
**Warning signs:** New content exceeds 55 lines.

### Pitfall 4: Eval-Author Validation Criteria Too Strict or Too Lenient
**What goes wrong:** If criteria are "produce identical assertions," the agent always fails (different wording). If criteria are "produce any assertions," the agent always passes (no quality bar).
**Why it happens:** No prior art for eval-author validation in this project.
**How to avoid:** Define pass criteria as: (a) 12-22 total assertions (gold-standard has 17), (b) distribution meets 60/20/20 thresholds, (c) at least 4 of 5 core Phase 18 differentiators are covered (CacheableJsonResponse, _csrf_request_header_token, _format:json, entity upcasting config, once() guard), (d) parenthetical rationales present on all assertions, (e) no tautological assertions (file existence, class extends standard base). This tests quality without requiring text identity.
**Warning signs:** Agent passes all criteria but assertions are shallow (test existence, not behavior).

### Pitfall 5: Eval-Author Gets Different Phase 18 Starting Code
**What goes wrong:** The eval-author is given the current accumulated module code (post-Phase 20) as "existing module code" instead of the Phase 17 code that was the actual starting point for Phase 18.
**Why it happens:** The module has evolved since Phase 18.
**How to avoid:** The eval-author needs the correct starting code context. For validation, point it at the Phase 17 module state. If that state is not preserved separately, use the git history or the `drupal-10-group-ai-pm` template (which represents the Phase 17 baseline before Phase 18 additions). Alternatively, instruct the agent that "existing module code" for Phase 18 is the pre-Phase-18 state and describe what existed.
**Warning signs:** Agent assertions reference code that was built IN Phase 18 as "already existing."

## Code Examples

### entities-fields Reference File Structure
```
skills/drupal-entities-fields/
  SKILL.md                              # 497 lines (add 1-line cross-reference)
  references/
    files-images.md                     # Existing: 131 lines
    bundled-entities.md                 # NEW: ~80-100 lines
```

### Caching Skill Addition Point

The new CacheableMetadata bubbling section should be inserted after the existing CacheableMetadata section (after line ~258) and before the Block plugin caching section. Approximately 25-30 lines.

```markdown
### CacheableMetadata bubbling in controllers

When a controller loads multiple entities, aggregate their cache metadata
into a single CacheableMetadata object and apply it to the response.

[code examples from Pattern 4 above]
```

### forms-api Skill Addition Point

The new #ajax section should be inserted before the Cross-references section (before line ~430). Approximately 50-55 lines.

```markdown
## AJAX form elements

The `#ajax` property triggers server-side callbacks without a full page reload.

[code examples from Pattern 5 above]
```

### Eval-Author Validation Invocation

```bash
# Invoke eval-author agent with Phase 18 inputs
# Skill paths: the 5 skills tested in Phase 18
# Phase prompt: from eval/v4/phase-18-evals.json .evals[0].prompt
# Existing module code: the pre-Phase-18 module state
# Gold-standard: eval/v4/phase-18-evals.json
# Output directory: a temporary directory for validation output

# The agent is invoked via Claude Code's agent system:
# /agent eval-author
# with the required inputs passed in the initial message
```

### Eval-Author Validation Pass Criteria

| Criterion | Threshold | Gold-Standard Reference |
|-----------|-----------|------------------------|
| Total assertion count | 12-22 | 17 |
| Differentiating % | >= 60% | 100% (all 17) |
| Structural % | <= 20% | 0% |
| Core differentiators covered | >= 4 of 5 | 5/5 |
| Parenthetical rationales | 100% | 100% |
| Tautological assertions | 0 | 0 |
| Runtime assertions present | >= 3 | 17 in gold-standard |

**Core differentiators** (must cover at least 4):
1. `_csrf_request_header_token` vs `_csrf_token` for JavaScript API routes
2. `_format: json` requirement on API routes
3. `CacheableJsonResponse` vs plain `JsonResponse`
4. Entity upcasting config (`options.parameters.{param}.type: entity:{type}`)
5. `once()` guard in Drupal.behaviors for Vue mounting

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| bundle_of undocumented in skill | Reference file with complete pattern | Phase 23 (new) | Haiku produces correct bundled entity pairs |
| CacheableMetadata not shown in controller context | Bubbling pattern with addCacheableDependency loop | Phase 23 (new) | Correct cache invalidation for multi-entity responses |
| No #ajax content in forms-api skill | Callback, wrapper, AjaxResponse patterns | Phase 23 (new) | AJAX forms work correctly, wrapper IDs match |
| Manual eval assertion design | Validated eval-author agent | Phase 23 (validated) | Automated assertion design for all future phases |

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Manual review + eval pipeline |
| Config file | eval/v4/phase-18-evals.json (gold-standard reference) |
| Quick run command | `wc -l skills/drupal-*/SKILL.md` (verify line budgets) |
| Full suite command | Invoke eval-author with Phase 18 inputs, compare output against gold-standard |

### Phase Requirements -> Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| TOOL-05 | Eval-author produces quality assertions matching Phase 18 gold-standard | manual (human comparison) | N/A - qualitative review | N/A |
| TOOL-06 | entities-fields skill includes bundle_of + hook_update_N | static | `grep -c 'bundle_of\|bundled-entities' skills/drupal-entities-fields/SKILL.md` | -- Wave 0 |
| TOOL-07 | caching skill includes CacheableMetadata bubbling | static | `grep -c 'addCacheableDependency.*loop\|applyTo' skills/drupal-caching/SKILL.md` | Partial (CacheableMetadata exists, bubbling section does not) |

### Sampling Rate
- **Per task commit:** `wc -l skills/drupal-*/SKILL.md` (ensure no skill exceeds 500 lines)
- **Per wave merge:** Eval-author validation pass/fail against gold-standard
- **Phase gate:** All three skill gaps closed + eval-author validation passed

### Wave 0 Gaps
- [ ] `skills/drupal-entities-fields/references/bundled-entities.md` -- bundle_of + hook_update_N content
- [ ] CacheableMetadata bubbling section in caching SKILL.md
- [ ] #ajax section in forms-api SKILL.md
- [ ] Eval-author validation output (temporary, for comparison)

## Open Questions

1. **What is the correct Phase 18 "starting code" for eval-author validation?**
   - What we know: Phase 18 started from the Phase 17 module state. The `drupal-10-group-ai-pm` template represents that state.
   - What's unclear: Whether the template has been updated since Phase 17 (Phase 18+ code may have been rsynced back).
   - Recommendation: Use git to identify the Phase 17 module state, or point the eval-author at the template and describe what Phase 18 should ADD (not what already exists). The phase prompt in `phase-18-evals.json` already says "Read the existing Drupal module" -- the agent should be able to distinguish existing from new code.

2. **Should the eval-author validation also check runtime assertions?**
   - What we know: The gold-standard has 17 runtime assertions. The eval-author agent produces both static and runtime assertions.
   - What's unclear: Whether runtime assertion quality can be validated without actually running them.
   - Recommendation: Validate runtime assertions exist (count >= 3), check format compliance, and verify at least some target non-obvious patterns (e.g., _csrf_request_header_token route check, _format:json route check). Do NOT run them against actual ddev instances for validation -- that is a full eval cycle, not validation.

3. **How should the forms-api #ajax section handle the already-mentioned AJAX in the description?**
   - What we know: The frontmatter description says "AJAX form elements" but the body has no content.
   - What's unclear: Whether the description should be updated to be more precise.
   - Recommendation: Keep the description as-is (it correctly promises AJAX coverage). Add the body content to deliver on that promise. No description change needed.

## Sources

### Primary (HIGH confidence)
- [Drupal Entity API - Create a custom content entity with bundles](https://www.drupal.org/docs/drupal-apis/entity-api/create-a-custom-content-entity-with-bundles) -- bundle_of + bundle_entity_type pattern
- [Drupal Update API - Updating Entities and Fields](https://www.drupal.org/docs/drupal-apis/update-api/updating-entities-and-fields-in-drupal-8) -- installFieldStorageDefinition() in hook_update_N
- [Drupal Render API - Cacheability of render arrays](https://www.drupal.org/docs/drupal-apis/render-api/cacheability-of-render-arrays) -- CacheableMetadata bubbling behavior
- `eval/v4/phase-18-evals.json` -- Gold-standard: 17 assertions, +23.3% delta (project-specific)
- `eval/v4/phase-18-runtime-assertions.json` -- Gold-standard: 17 runtime assertions (project-specific)
- `.claude/agents/eval-author.md` -- Agent definition to validate (project-specific)
- Current SKILL.md files (project-specific): entities-fields (497 lines), caching (361 lines), forms-api (440 lines)
- `.planning/research/PITFALLS.md` -- Pitfall #6 (missing hook_update_N), Pitfall #15 (missing #ajax content)

### Secondary (MEDIUM confidence)
- [Drupal Entity API - Bundles](https://www.drupal.org/docs/drupal-apis/entity-api/bundles) -- Bundle concept overview
- [PreviousNext - Entity Definition Update Manager for bundle support](https://www.previousnext.com.au/blog/using-drupals-entity-definition-update-manager-add-bundle-support-existing-content-entity) -- Practical bundle migration patterns
- [Drupalize.Me - Add Cache Metadata to Render Arrays](https://drupalize.me/tutorial/add-cache-metadata-render-arrays) -- CacheableMetadata tutorial
- `.planning/research/STACK.md` -- Skill gap fix specifications
- `.planning/research/ARCHITECTURE.md` -- Component 5: Skill Gap Fixes

### Tertiary (LOW confidence)
- None -- all findings verified against primary sources.

## Metadata

**Confidence breakdown:**
- Skill gap content (entities-fields): HIGH -- Drupal official docs confirm bundle_of + ConfigEntityBundleBase + installFieldStorageDefinition patterns
- Skill gap content (caching): HIGH -- CacheableMetadata and addCacheableDependency are already in the skill; the bubbling pattern is a well-documented Drupal concept
- Skill gap content (forms-api): HIGH -- #ajax callback/wrapper pattern is core Drupal Form API, confirmed by Phase 20 eval assertions
- Eval-author validation: MEDIUM -- no prior art for this specific validation approach; criteria are reasonable but untested
- Line budgets: HIGH -- measured directly via wc -l on current files

**Research date:** 2026-03-09
**Valid until:** 2026-04-09
