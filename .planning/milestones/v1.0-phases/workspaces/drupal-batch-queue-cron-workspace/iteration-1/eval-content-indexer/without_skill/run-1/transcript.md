Module installed successfully. Here's what was created:

```
html/modules/custom/content_indexer/
├── content_indexer.info.yml          # D10/D11 compatible, package: Open Social AI
├── content_indexer.install           # hook_schema() → content_indexer_status table
├── content_indexer.module            # hook_cron() → queues unindexed nodes
└── src/Plugin/QueueWorker/
    └── ContentIndexerWorker.php      # QueueWorker with cron: {time: 60}
```

Key design decisions:
- **`hook_cron()`** queries all published nodes, excludes those already marked `indexed=1` in `content_indexer_status`, and enqueues the rest — no direct processing.
- **`ContentIndexerWorker`** uses the PHP 8 `#[QueueWorker]` attribute (D10.3+/D11), `cron: ['time' => 60]` caps processing at 60 seconds per cron run.
- **`processItem()`** does a `merge()` (upsert) into `content_indexer_status` setting `indexed=1` and recording the timestamp.
- Worker is container-factory-injectable — takes `database` and `logger.channel.content_indexer` services.
