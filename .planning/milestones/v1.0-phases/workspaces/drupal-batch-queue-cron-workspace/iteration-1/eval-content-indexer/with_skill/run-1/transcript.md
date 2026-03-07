Module installed successfully. Here's a summary of what was created:

**Files created under `html/modules/custom/content_indexer/`:**

| File | Purpose |
|---|---|
| `content_indexer.info.yml` | Module definition, `core_version_requirement: ^10 \|\| ^11` |
| `content_indexer.install` | `hook_schema()` — creates `content_indexer_status` table (`nid`, `indexed`, `indexed_at`) |
| `content_indexer.module` | `hook_cron()` — queries published nodes NOT already in the indexed set, queues each as a `stdClass` item |
| `src/Plugin/QueueWorker/ContentIndexerWorker.php` | QueueWorker — D11 `#[QueueWorker]` attribute, `cron: ['time' => 30]`, writes to `content_indexer_status` via `db->merge()` |

**Key design decisions following the skill:**

- `hook_cron()` only **populates** the queue — never processes items directly.
- Queue name `content_indexer_worker` **exactly matches** the QueueWorker plugin `id:` — Drupal requires this for cron-based dispatch.
- `cron: ['time' => 30]` gives the worker a 30-second budget per cron run, so deep queues don't cause timeouts.
- D11 PHP attribute syntax (`#[QueueWorker(...)]`) used throughout — compatible with Drupal 10.2+ and 11.
- `processItem()` throws on invalid data, leaving the item for retry; `db->merge()` is idempotent so re-queued items are safe.
