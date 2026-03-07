Module installed successfully. Here's what was created:

```
content_recommendations_baseline/
├── content_recommendations_baseline.info.yml       # D10+D11 compatible (^10 || ^11)
├── content_recommendations_baseline.services.yml   # Registers RecommendationsService
└── src/
    ├── RecommendationsService.php                  # Fetches nodes via EntityTypeManager DI
    └── Plugin/Block/ContentRecommendationsBlock.php # Block with config form
```

Key patterns used per the skill:
- **D11 `#[Block(...)]` attribute** with `new TranslatableMarkup(...)` (not `@Translation`)
- **4-parameter plugin `create()`** — `($container, $configuration, $plugin_id, $plugin_definition)` with `parent::__construct()` called first
- **Block config via `$this->configuration`** — not Config API; `defaultConfiguration()` sets `items_to_show: 5`, `blockSubmit()` saves it
- **No `\Drupal::service()` static calls** — service injected via DI throughout
