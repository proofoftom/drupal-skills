# Phase 24: AI Task Service + NL Task Creation - Research

**Researched:** 2026-03-09
**Domain:** Drupal AI module integration, custom service layer, AiFunctionCall plugins, REST endpoints
**Confidence:** MEDIUM-HIGH

## Summary

Phase 24 introduces the AI service layer and natural language task creation to the group_ai_pm module. The core deliverables are: (1) an `AiTaskService` that encapsulates AI logic and is injectable by both REST controllers and AiFunctionCall plugins, (2) a `CreateTaskTool` AiFunctionCall plugin following the existing `CreateProjectTool` pattern, (3) a POST REST endpoint that accepts natural language text and returns a created task, and (4) optional dependency handling so the module functions without the AI module installed.

The existing codebase already has a working AI integration pattern in the `group_ai_pm_ai` submodule with `CreateProjectTool` and `QueryProjectsTool`. However, these existing tools use an incorrect/outdated pattern -- they reference `Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase` which does not exist as a class in the installed AI Agents module. The actual AI module API uses `Drupal\ai\Base\FunctionCallBase` as the base class and `#[FunctionCall]` PHP attribute from `Drupal\ai\Attribute\FunctionCall` for plugin discovery. The AI Agents module's own tools (e.g., `CreateContentType`) use this exact pattern with `ExecutableFunctionCallInterface`. This is a critical finding: new tools MUST follow the actual API, and the planner should decide whether to also fix the existing tools.

The AI module's chat API is verified: service name is `ai.provider` (class: `AiProviderPluginManager`), chat input uses `ChatInput` with `ChatMessage` objects, system prompts are set via `ChatInput::setSystemPrompt()`, and structured JSON output can be enforced via `ChatInput::setChatStructuredJsonSchema()`. The optional dependency pattern uses Drupal's standard `@?service_name` syntax in services.yml, which passes NULL when the service is unavailable. This is well-established in Drupal core (used by `core.services.yml`, `content_moderation.services.yml`, etc.).

**Primary recommendation:** Build `AiTaskService` in the main module with `@?ai.provider` optional injection. Place `CreateTaskTool` in the existing `group_ai_pm_ai` submodule using the correct `FunctionCallBase` + `#[FunctionCall]` attribute pattern. Add a new `AiTaskController` with the POST endpoint for NL task creation.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| AI-01 | AiTaskService encapsulates all AI logic, injectable by both REST controllers and AiFunctionCall plugins | Service pattern verified: register in `group_ai_pm.services.yml` with `@?ai.provider` + `@entity_type.manager` + `@config.factory`. Controllers inject via `create()`, plugins inject via `$container->get()` in 4-param `create()`. |
| AI-02 | AiTaskService uses optional AI dependency (@? injection) so module functions without AI module | `@?` syntax verified in Drupal core (`core.services.yml` line 487, `content_moderation.services.yml` line 25). Service receives NULL when AI module absent. Guard with `if ($this->aiProvider === NULL)` returning graceful fallback. |
| AI-03 | CreateTaskTool AiFunctionCall plugin creates tasks from natural language, following existing CreateProjectTool pattern | Actual API verified: extends `FunctionCallBase`, implements `ExecutableFunctionCallInterface`, uses `#[FunctionCall]` attribute with `context_definitions` for arguments. Existing `CreateProjectTool` uses outdated/incorrect pattern. |
| AI-04 | REST endpoint (POST) accepts natural language text and returns created task JSON with all parsed fields | Follows existing `TaskApiController::quickCreate()` pattern but delegates NL parsing to `AiTaskService`. Route needs `_format: json`, `_csrf_request_header_token: 'TRUE'`, entity upcasting for project parameter. |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| drupal/ai | 1.2.11 | AI provider abstraction, chat API, FunctionCallBase | Already installed. Provider-agnostic `ai.provider` service. Pinned -- do NOT upgrade to 1.3.x RC. |
| drupal/ai_agents | 1.3.0-beta2 | AI agent framework, function call plugin discovery | Already installed. `plugin.manager.ai.function_calls` discovers FunctionCall plugins. |
| drupal/core | 10.6.x | Service container, DI, entity API, routing | `@?` optional injection, `ContainerFactoryPluginInterface`, `ControllerBase` |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Drupal Config API | core | Store AI provider/model settings | `group_ai_pm.settings` already has `ai_provider` key |
| Drupal Logger | core | Log AI call failures | `@logger.factory` for `group_ai_pm` channel |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Custom AiTaskService | Direct AI calls in controller | Service is reusable by both controllers and plugins; direct calls duplicate logic |
| Submodule for tools | Tools in main module | Submodule enforces hard dependency on ai_agents; main module stays AI-optional |
| Structured JSON schema | Free-text AI parsing | Schema guarantees field types; free-text requires post-processing validation |

## Architecture Patterns

### Recommended Project Structure

```
modules/group_ai_pm/
  group_ai_pm.services.yml          # NEW -- registers AiTaskService with @?ai.provider
  src/
    Service/
      AiTaskService.php             # NEW -- central AI logic, optional AI dep
    Controller/
      AiTaskController.php          # NEW -- POST /api/kanban/project/{project}/ai-create
      TaskApiController.php         # EXISTS -- unchanged
  modules/
    group_ai_pm_ai/
      group_ai_pm_ai.services.yml   # MAY NEED -- if CreateTaskTool needs AiTaskService
      src/Plugin/AiFunctionCall/
        CreateProjectTool.php       # EXISTS -- consider fixing to use correct API
        QueryProjectsTool.php       # EXISTS -- consider fixing to use correct API
        CreateTaskTool.php          # NEW -- NL task creation via AI agent
```

### Pattern 1: AiTaskService with Optional AI Dependency

**What:** A Drupal service registered in `group_ai_pm.services.yml` that wraps all AI interaction logic. Uses `@?ai.provider` to receive NULL when the AI module is not installed.

**When to use:** Any time AI logic needs to be shared between REST controllers, AiFunctionCall plugins, or other services.

**Example:**

```php
// Source: Drupal core @? pattern (core.services.yml) + AI module API (ai.services.yml)
namespace Drupal\group_ai_pm\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

class AiTaskService {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected ?AiProviderPluginManager $aiProvider = NULL,
  ) {}

  /**
   * Checks whether AI features are available.
   */
  public function isAvailable(): bool {
    if ($this->aiProvider === NULL) {
      return FALSE;
    }
    $config = $this->configFactory->get('group_ai_pm.settings');
    $provider_id = $config->get('ai_provider');
    return !empty($provider_id);
  }

  /**
   * Parses natural language into task fields.
   */
  public function parseNaturalLanguage(string $text): array {
    if (!$this->isAvailable()) {
      throw new \RuntimeException('AI features are not available.');
    }

    $config = $this->configFactory->get('group_ai_pm.settings');
    $provider_id = $config->get('ai_provider');
    $model_id = $config->get('ai_model') ?? '';

    $provider = $this->aiProvider->createInstance($provider_id);

    $input = new ChatInput([
      new ChatMessage('user', $text),
    ]);

    $input->setSystemPrompt('You are a project management assistant. Parse the following task description into structured JSON with fields: title (required string), description (optional string), status (one of: todo, in_progress, review, done; default: todo), priority (one of: low, medium, high, critical; default: medium), assignee_name (optional string). Return ONLY valid JSON.');

    // Use structured JSON schema for reliable parsing.
    $input->setChatStructuredJsonSchema([
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'status' => ['type' => 'string', 'enum' => ['todo', 'in_progress', 'review', 'done']],
        'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
        'assignee_name' => ['type' => 'string'],
      ],
      'required' => ['title'],
    ]);

    $output = $provider->chat($input, $model_id, ['group_ai_pm']);
    $parsed = json_decode($output->getNormalized()->getText(), TRUE);

    if (!is_array($parsed) || empty($parsed['title'])) {
      throw new \RuntimeException('AI failed to parse task from input.');
    }

    return $parsed;
  }

  /**
   * Creates a task entity from parsed fields.
   */
  public function createTaskFromParsed(array $parsed, int $project_id): \Drupal\group_ai_pm\Entity\TaskInterface {
    $storage = $this->entityTypeManager->getStorage('task');

    $values = [
      'title' => $parsed['title'],
      'project' => $project_id,
      'status' => $parsed['status'] ?? 'todo',
      'priority' => $parsed['priority'] ?? 'medium',
    ];

    if (!empty($parsed['description'])) {
      $values['description'] = $parsed['description'];
    }

    // Resolve assignee by name if provided.
    if (!empty($parsed['assignee_name'])) {
      $users = $this->entityTypeManager->getStorage('user')
        ->loadByProperties(['name' => $parsed['assignee_name']]);
      if ($user = reset($users)) {
        $values['assignee'] = $user->id();
      }
    }

    $task = $storage->create($values);
    $task->save();
    return $task;
  }

}
```

**Services registration:**

```yaml
# group_ai_pm.services.yml
services:
  group_ai_pm.ai_task:
    class: Drupal\group_ai_pm\Service\AiTaskService
    arguments: ['@entity_type.manager', '@config.factory', '@?ai.provider']
```

### Pattern 2: AiFunctionCall Plugin (Correct API)

**What:** The actual `FunctionCallBase` + `#[FunctionCall]` attribute pattern used by the AI Agents module 1.3.x. This differs from the existing `CreateProjectTool` pattern in the codebase.

**When to use:** Creating new AI agent tools that can be invoked by the AI chatbot.

**Example:**

```php
// Source: ai_agents contrib module CreateContentType.php (verified in installed code)
namespace Drupal\group_ai_pm_ai\Plugin\AiFunctionCall;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\FunctionCall;
use Drupal\ai\Base\FunctionCallBase;
use Drupal\ai\Service\FunctionCalling\ExecutableFunctionCallInterface;
use Drupal\ai\Service\FunctionCalling\FunctionCallInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[FunctionCall(
  id: 'group_ai_pm:create_task',
  function_name: 'create_task',
  name: 'Create Task',
  description: 'Create a new task from a natural language description, parsing title, description, status, priority, and assignee.',
  group: 'group_ai_pm',
  module_dependencies: ['group_ai_pm'],
  context_definitions: [
    'text' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Text'),
      description: new TranslatableMarkup('The natural language task description to parse.'),
      required: TRUE,
    ),
    'project_id' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Project ID'),
      description: new TranslatableMarkup('The project entity ID to create the task in.'),
      required: TRUE,
    ),
  ],
)]
class CreateTaskTool extends FunctionCallBase implements ExecutableFunctionCallInterface {

  protected AiTaskService $aiTaskService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->aiTaskService = $container->get('group_ai_pm.ai_task');
    return $instance;
  }

  public function execute() {
    $text = $this->getContextValue('text');
    $project_id = $this->getContextValue('project_id');

    $parsed = $this->aiTaskService->parseNaturalLanguage($text);
    $task = $this->aiTaskService->createTaskFromParsed($parsed, $project_id);

    $this->setOutput("Task created: {$task->getTitle()} (ID: {$task->id()})");
  }

}
```

### Pattern 3: REST Controller Consuming AiTaskService

**What:** A controller that accepts natural language POST requests and delegates to AiTaskService.

**When to use:** Frontend-facing AI task creation endpoint.

**Example:**

```php
// Source: Existing TaskApiController pattern + routing-controllers skill
namespace Drupal\group_ai_pm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group_ai_pm\Entity\ProjectInterface;
use Drupal\group_ai_pm\Service\AiTaskService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AiTaskController extends ControllerBase {

  protected AiTaskService $aiTaskService;

  public function __construct(AiTaskService $ai_task_service) {
    $this->aiTaskService = $ai_task_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_ai_pm.ai_task')
    );
  }

  public function createFromText(ProjectInterface $project, Request $request) {
    if (!$this->aiTaskService->isAvailable()) {
      return new JsonResponse(
        ['error' => 'AI features are not available. Please configure an AI provider.'],
        Response::HTTP_SERVICE_UNAVAILABLE
      );
    }

    $data = json_decode($request->getContent(), TRUE);
    $text = $data['text'] ?? NULL;

    if (!$text || empty(trim($text))) {
      return new JsonResponse(
        ['error' => 'Text input is required'],
        Response::HTTP_UNPROCESSABLE_ENTITY
      );
    }

    try {
      $parsed = $this->aiTaskService->parseNaturalLanguage($text);
      $task = $this->aiTaskService->createTaskFromParsed($parsed, (int) $project->id());
      // Reuse existing serialization pattern from TaskApiController.
      return new JsonResponse([
        'task' => $this->serializeTask($task),
        'parsed' => $parsed,
      ], Response::HTTP_CREATED);
    }
    catch (\RuntimeException $e) {
      return new JsonResponse(
        ['error' => $e->getMessage()],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

}
```

**Route definition:**

```yaml
group_ai_pm.api.ai_create:
  path: '/api/kanban/project/{project}/ai-create'
  defaults:
    _controller: '\Drupal\group_ai_pm\Controller\AiTaskController::createFromText'
  methods: [POST]
  requirements:
    _permission: 'administer group_ai_pm'
    _csrf_request_header_token: 'TRUE'
    _format: json
  options:
    _admin_route: TRUE
    parameters:
      project:
        type: entity:project
```

### Anti-Patterns to Avoid

- **Putting AI logic directly in controllers:** Duplicates logic between REST endpoint and AiFunctionCall plugin. AiTaskService must be the single source of truth.
- **Using `\Drupal::service('ai.provider')` instead of DI:** Static service calls in controllers and services are the #1 DI violation. Use `@?ai.provider` in services.yml or `$container->get()` in `create()`.
- **Extending non-existent `AiFunctionCallBase`:** The existing `CreateProjectTool` references `Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase` which is not a real class. The correct base is `Drupal\ai\Base\FunctionCallBase`.
- **Using `@AiFunctionCall` annotations:** The actual AI module uses `#[FunctionCall]` PHP attributes. The annotation pattern in existing tools is outdated.
- **Omitting `_format: json` on API routes:** Without this, Drupal returns HTML error pages for 403/404/422 instead of JSON.
- **Hard-coding AI provider in code:** Use `$this->configFactory->get('group_ai_pm.settings')->get('ai_provider')` to read the configured provider.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| AI provider abstraction | Custom HTTP client for OpenAI/Anthropic | `ai.provider` service + `ChatInput`/`ChatOutput` | Provider-agnostic, handles auth, rate limits, retries |
| Structured JSON from AI | Parse free-text with regex | `ChatInput::setChatStructuredJsonSchema()` | AI module enforces schema at provider level |
| Optional service injection | `\Drupal::moduleHandler()->moduleExists()` checks | `@?ai.provider` in services.yml | Standard Symfony DI pattern, passes NULL automatically |
| Plugin discovery for AI tools | Custom plugin manager | `plugin.manager.ai.function_calls` | AI module provides discovery, just extend `FunctionCallBase` |
| Task serialization | Custom serialization in each tool/controller | Extract `serializeTask()` to AiTaskService or trait | Already exists in `TaskApiController`, reuse it |

**Key insight:** The AI module provides a complete abstraction layer. The only custom code needed is the service that bridges AI chat output with entity creation, and the plugin/controller that wire it into the system.

## Common Pitfalls

### Pitfall 1: Existing Tools Use Non-Existent Base Class
**What goes wrong:** `CreateProjectTool` and `QueryProjectsTool` extend `Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase` and use `@AiFunctionCall` annotation. This class does not exist in the installed AI Agents module. These tools work only because the `ai_agents` module likely has a compatibility shim or autoloader alias.
**Why it happens:** The tools were created based on documentation that described an older API. The actual API uses `FunctionCallBase` from `Drupal\ai\Base\FunctionCallBase`.
**How to avoid:** New `CreateTaskTool` MUST use the correct pattern: extend `FunctionCallBase`, implement `ExecutableFunctionCallInterface`, use `#[FunctionCall]` attribute.
**Warning signs:** `drush eval "print_r(array_keys(\Drupal::service('plugin.manager.ai.function_calls')->getDefinitions()));"` does not list the new plugin.

### Pitfall 2: AI Provider Not Validated Before Chat Call
**What goes wrong:** `AiTaskService::parseNaturalLanguage()` calls `$this->aiProvider->createInstance($provider_id)` with an empty or invalid provider ID. The AI module throws an exception or returns garbage.
**Why it happens:** The `ai_provider` config key may be empty string (default), or the configured provider may not be installed/configured.
**How to avoid:** `isAvailable()` must check: (1) `aiProvider` is not NULL, (2) provider ID is not empty, (3) optionally verify `$provider->isUsable('chat')` before making calls.
**Warning signs:** Empty task titles, RuntimeException from AI module, provider not found errors.

### Pitfall 3: Plugin `create()` Signature Mismatch
**What goes wrong:** New plugin uses controller-style `create(ContainerInterface $container)` with 1 parameter instead of plugin-style 4-parameter `create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)`.
**Why it happens:** Controller DI and plugin DI use the same method name but different signatures. LLMs frequently confuse them.
**How to avoid:** Follow the `FunctionCallBase::create()` pattern exactly. Use `parent::create()` + setter injection (as shown in `CreateContentType.php` from ai_agents contrib).
**Warning signs:** Fatal error: "Too few arguments" or "Too many arguments" when AI agent tries to use the tool.

### Pitfall 4: Missing `context_definitions` in `#[FunctionCall]` Attribute
**What goes wrong:** Plugin uses `getArguments()` method (annotation-era pattern) instead of `context_definitions` in the `#[FunctionCall]` attribute. The AI chatbot cannot determine what arguments the tool accepts.
**Why it happens:** Existing `CreateProjectTool` uses `getArguments()` which is the old pattern. The actual API uses `context_definitions` array in the attribute with `ContextDefinition` objects.
**How to avoid:** Define all tool inputs as `ContextDefinition` objects in the `#[FunctionCall]` attribute. Access values via `$this->getContextValue('field_name')` instead of `$this->functionCall->getArgumentsObject()->field`.
**Warning signs:** AI chatbot says the tool has no arguments, or passes arguments that the tool cannot read.

### Pitfall 5: Config Schema Not Updated for New Settings
**What goes wrong:** `AiTaskService` reads `ai_model` from config, but `group_ai_pm.schema.yml` and `group_ai_pm.settings.yml` do not include it. Config validation fails on CI. Settings form does not expose the new field.
**Why it happens:** Adding a new config key requires updating three files: `.schema.yml`, `.settings.yml` (defaults), and `SettingsForm.php`.
**How to avoid:** Whenever AiTaskService reads a new config key, add it to all three files. The `ai_model` key is needed alongside the existing `ai_provider` key.
**Warning signs:** `drush config:validate` reports schema errors. New settings do not appear in the UI.

## Code Examples

### Verified: AI Module Chat API Call

```php
// Source: ai.services.yml (ai.provider service), ChatInput.php, ChatMessage.php
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

// Get provider from plugin manager
$provider = $this->aiProvider->createInstance('openai');

// Build chat input
$input = new ChatInput([
  new ChatMessage('user', 'Create a high priority task to fix the login bug'),
]);
$input->setSystemPrompt('Parse task descriptions into JSON...');
$input->setChatStructuredJsonSchema([...]);

// Execute chat
$output = $provider->chat($input, 'gpt-4', ['group_ai_pm']);
$text = $output->getNormalized()->getText();
$parsed = json_decode($text, TRUE);
```

### Verified: Optional Service Injection

```yaml
# Source: Drupal core core.services.yml line 487, content_moderation.services.yml line 25
services:
  group_ai_pm.ai_task:
    class: Drupal\group_ai_pm\Service\AiTaskService
    arguments: ['@entity_type.manager', '@config.factory', '@?ai.provider']
```

```php
// In AiTaskService constructor
public function __construct(
  protected EntityTypeManagerInterface $entityTypeManager,
  protected ConfigFactoryInterface $configFactory,
  protected ?AiProviderPluginManager $aiProvider = NULL,
) {}
```

### Verified: FunctionCallBase create() with Setter Injection

```php
// Source: ai_agents/src/Plugin/AiFunctionCall/CreateContentType.php (installed code)
public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FunctionCallInterface|static {
  $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
  // Use setter injection for additional services
  $instance->entityTypeManager = $container->get('entity_type.manager');
  $instance->aiTaskService = $container->get('group_ai_pm.ai_task');
  return $instance;
}
```

### Verified: FunctionCall Attribute with ContextDefinition

```php
// Source: ai_agents/src/Plugin/AiFunctionCall/CreateContentType.php (installed code)
#[FunctionCall(
  id: 'group_ai_pm:create_task',
  function_name: 'create_task',
  name: 'Create Task',
  description: 'Create a task from natural language input.',
  group: 'group_ai_pm',
  module_dependencies: ['group_ai_pm'],
  context_definitions: [
    'text' => new ContextDefinition(
      data_type: 'string',
      label: new TranslatableMarkup('Text'),
      description: new TranslatableMarkup('Natural language task description'),
      required: TRUE,
    ),
    'project_id' => new ContextDefinition(
      data_type: 'integer',
      label: new TranslatableMarkup('Project ID'),
      description: new TranslatableMarkup('The project to create the task in'),
      required: TRUE,
    ),
  ],
)]
class CreateTaskTool extends FunctionCallBase implements ExecutableFunctionCallInterface {
  // ...
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `@AiFunctionCall` annotation + `AiFunctionCallBase` | `#[FunctionCall]` attribute + `FunctionCallBase` | AI module 1.2.x | Existing tools use old pattern; new tools MUST use current |
| `getArguments()` method | `context_definitions` in attribute | AI module 1.2.x | Tool arguments defined declaratively, not programmatically |
| `$this->functionCall->getArgumentsObject()->field` | `$this->getContextValue('field')` | AI module 1.2.x | Context-aware plugin system replaces direct argument access |
| `setChatSystemRole()` on provider | `setSystemPrompt()` on ChatInput | AI module 1.2.x | System prompt is input concern, not provider concern |
| Controller-only AI logic | Dedicated service + controller delegation | Best practice | AiTaskService is reusable by plugins, controllers, and future batch workers |

**Deprecated/outdated:**
- `AiFunctionCallBase`: Does not exist as a real class. Use `FunctionCallBase` from `Drupal\ai\Base`.
- `@AiFunctionCall` annotation: Replaced by `#[FunctionCall]` PHP attribute.
- `setChatSystemRole()`: Deprecated in favor of `ChatInput::setSystemPrompt()`.
- `getArguments()` method on plugins: Replaced by `context_definitions` in the `#[FunctionCall]` attribute.

## Open Questions

1. **Existing tool compatibility**
   - What we know: `CreateProjectTool` and `QueryProjectsTool` reference a non-existent base class and use old API patterns.
   - What's unclear: Whether they currently work at runtime (possibly via an autoloader alias or compatibility shim we did not find).
   - Recommendation: Plan a task to verify existing tools work, and consider migrating them to the correct API in this phase to avoid mixed patterns.

2. **AI model configuration**
   - What we know: `group_ai_pm.settings` has `ai_provider` but no `ai_model` key. The chat API requires both `provider_id` and `model_id`.
   - What's unclear: Whether `getDefaultProviderForOperationType('chat')` can supply the model automatically.
   - Recommendation: Add `ai_model` to config schema and settings form. Use `getDefaultProviderForOperationType('chat')` as fallback when model not explicitly configured.

3. **Task serialization sharing**
   - What we know: `TaskApiController` has a `serializeTask()` method that the new `AiTaskController` also needs.
   - What's unclear: Best approach -- trait, service method, or code duplication.
   - Recommendation: Move `serializeTask()` to `AiTaskService` or a shared trait. Both controllers should use the same serialization.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit (Drupal's BrowserTestBase / KernelTestBase) |
| Config file | `phpunit.xml` in Drupal root |
| Quick run command | `ddev exec phpunit modules/custom/group_ai_pm/tests/src/Kernel/RestApiTest.php -x` |
| Full suite command | `ddev exec phpunit modules/custom/group_ai_pm/tests/ --no-coverage` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| AI-01 | AiTaskService is injectable | unit/kernel | `ddev drush php-eval "\Drupal::service('group_ai_pm.ai_task');"` | No -- Wave 0 |
| AI-02 | Module works without AI module | kernel | `ddev drush en group_ai_pm -y` (without ai module) | No -- Wave 0 |
| AI-03 | CreateTaskTool discoverable | kernel | `ddev drush php-eval "print_r(array_keys(\Drupal::service('plugin.manager.ai.function_calls')->getDefinitions()));"` | No -- Wave 0 |
| AI-04 | POST endpoint returns created task | functional | `curl -X POST /api/kanban/project/1/ai-create -d '{"text":"..."}' ` | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** `ddev drush cr && ddev drush php-eval "\Drupal::service('group_ai_pm.ai_task');"`
- **Per wave merge:** `ddev exec phpunit modules/custom/group_ai_pm/tests/ --no-coverage`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] Runtime assertion: `group_ai_pm.ai_task` service resolves from container
- [ ] Runtime assertion: module enables without AI module (`ddev drush en group_ai_pm -y` on clean install without ai)
- [ ] Runtime assertion: CreateTaskTool plugin is discovered by `plugin.manager.ai.function_calls`
- [ ] Runtime assertion: POST to ai-create endpoint returns 201 with task JSON (requires AI provider configured)
- [ ] Static assertion: `AiTaskService` constructor accepts nullable `AiProviderPluginManager`
- [ ] Static assertion: `CreateTaskTool` extends `FunctionCallBase` (NOT `AiFunctionCallBase`)
- [ ] Static assertion: `CreateTaskTool` uses `#[FunctionCall]` attribute (NOT `@AiFunctionCall` annotation)
- [ ] Static assertion: Route `group_ai_pm.api.ai_create` has `_format: json` requirement

## Sources

### Primary (HIGH confidence)
- Installed `ai.services.yml` -- Confirmed `ai.provider` service name (class: `AiProviderPluginManager`)
- Installed `FunctionCallBase.php` (`Drupal\ai\Base\FunctionCallBase`) -- Verified constructor signature, `create()` pattern, `getContextValue()` method
- Installed `FunctionCall.php` attribute (`Drupal\ai\Attribute\FunctionCall`) -- Verified attribute parameters: `id`, `function_name`, `name`, `description`, `group`, `module_dependencies`, `context_definitions`
- Installed `CreateContentType.php` (`ai_agents/src/Plugin/AiFunctionCall/`) -- Verified real-world `FunctionCallBase` + `ExecutableFunctionCallInterface` pattern with setter injection in `create()`
- Installed `ChatInput.php`, `ChatMessage.php`, `ChatOutput.php` -- Verified chat API: `setSystemPrompt()`, `setChatStructuredJsonSchema()`, `getNormalized()->getText()`
- Drupal core `core.services.yml` -- Confirmed `@?` optional injection syntax (line 487: `@?csrf_token`)
- Existing `TaskApiController.php` -- Verified REST endpoint pattern, `serializeTask()`, `CacheableJsonResponse`

### Secondary (MEDIUM confidence)
- `.planning/research/PITFALLS.md` -- Pitfalls #7 (provider not validated), #10 (plugin annotation namespace)
- `.planning/research/STACK.md` -- AI module version decisions, chat API examples
- `.planning/research/ARCHITECTURE.md` -- AI tool expansion plan, route structure

### Tertiary (LOW confidence)
- `AiFunctionCallBase` existence -- The existing tools import from `Drupal\ai_agents\Plugin\AiFunctionCall\AiFunctionCallBase` but no such class was found in the installed contrib code. It may be provided by a different mechanism (autoloader alias, compatibility layer) not visible in source scanning.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All versions verified in installed code, zero new dependencies
- Architecture: HIGH - Service pattern, optional DI, and FunctionCall API all verified against installed source
- Pitfalls: MEDIUM-HIGH - Plugin API discrepancy is confirmed; existing tool compatibility is uncertain
- Chat API: HIGH - ChatInput, ChatMessage, setSystemPrompt, setChatStructuredJsonSchema all verified in source

**Research date:** 2026-03-09
**Valid until:** 2026-04-09 (stable -- AI module versions pinned, no upgrades planned)
