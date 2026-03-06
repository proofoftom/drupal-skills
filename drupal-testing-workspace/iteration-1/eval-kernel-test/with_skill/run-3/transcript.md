# Transcript: Kernel Test for RelatedContentService

## Skill Used

Read `/home/proofoftom/Code/drupal-skills/skills/drupal-testing/SKILL.md` first. The skill establishes test type selection logic (Unit < Kernel < Functional < FunctionalJavascript) and documents the required patterns for each.

## Test Type Decision

The task asks to verify a service can be loaded from the container — no browser interaction, no JavaScript. The service needs:
- The Drupal service container (with all module services registered)
- The database (for entity schema installation)

This rules out Unit tests (need real services, not mocks) and Functional tests (overkill — no HTTP/browser needed). **KernelTestBase** is correct: it boots a lightweight kernel with the specified modules, has database access, and lets us call `$this->container->get()`.

## Codebase Exploration

### Module structure read:

- `social_ai_indexing.info.yml` — dependencies: `search_api:search_api`, `group:group`, `ai_search:ai_search`, `ai:ai_assistant_api`, `ai:ai_chatbot`
- `social_ai_indexing.services.yml` — `social_ai_indexing.related_content` registered with arguments `@entity_type.manager`, `@social_ai_indexing.permission_filter`
- `RelatedContentService.php` — constructor takes `EntityTypeManagerInterface` and `PermissionFilterService`
- `PermissionFilterService.php` — constructor takes `$membership_loader` (group.membership_loader), `AccountInterface`, `RouteMatchInterface`, `EntityTypeManagerInterface`

### Dependency chain resolved:

| Module | Provides | Needed by |
|--------|----------|-----------|
| `system`, `user`, `field`, `text`, `filter` | Core services | Basic kernel |
| `node` | Node entity type | Task requirement + RelatedContentService uses NodeInterface |
| `options` | Options widget | `group` dep |
| `entity` | Entity API | `group` dep |
| `flexible_permissions` | Permission framework | `group` dep |
| `group` | `group.membership_loader` | `social_ai_indexing.permission_filter` |
| `key` | Key management | `ai` dep |
| `ai` | AI abstraction layer | `ai_search`, `ai_assistant_api` |
| `search_api` | Search API framework | `ai_search`, `social_ai_indexing` |
| `ai_search` | VDB search backend | `social_ai_indexing` |
| `ai_assistant_api` | AI assistant services | `social_ai_indexing` + `ai_chatbot` |
| `ai_chatbot` | Chatbot frontend | `social_ai_indexing` |
| `social_ai_indexing` | Module under test | — |

Cross-referenced with existing kernel tests in contrib modules:
- `GroupKernelTestBase` (group module) — shows `installEntitySchema('group')`, `installEntitySchema('group_content')`, `installEntitySchema('group_config_wrapper')`, `installConfig(['group'])`
- `InstallAiSearchTest` — shows minimal `['system', 'user', 'ai', 'search_api']` then module_installer pattern
- `InstallAiAssistantApiTest` — same minimal pattern

## Test File Created

**Location:** `html/modules/custom/social_ai_indexing/tests/src/Kernel/RelatedContentServiceTest.php`

**Namespace:** `Drupal\Tests\social_ai_indexing\Kernel\RelatedContentServiceTest`

**Base class:** `Drupal\KernelTests\KernelTestBase`

### Key decisions:

1. **`$modules` includes all transitive dependencies** — KernelTestBase does NOT auto-install module dependencies, so every required module must be listed explicitly. Missing a module causes "Service not found" errors.

2. **Entity schemas installed in setUp():**
   - `user` + `node` (task requirement, and node entity access table)
   - `group`, `group_content`, `group_config_wrapper` + `installConfig(['group'])` — required for group module services to work (following GroupKernelTestBase pattern)

3. **`@group social_ai_indexing`** — required for test discovery per the skill.

4. **`assertInstanceOf(RelatedContentService::class, $service)`** — verifies both that the service is in the container AND that it resolves to the correct class.

## Output

Test file copied to:
`/home/proofoftom/Code/drupal-skills/drupal-testing-workspace/iteration-1/eval-kernel-test/with_skill/run-3/outputs/src/Kernel/RelatedContentServiceTest.php`
