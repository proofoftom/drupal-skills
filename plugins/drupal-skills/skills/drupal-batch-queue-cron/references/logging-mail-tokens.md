# Logging, Mail, and Tokens

Three related Drupal APIs for output, communication, and dynamic text replacement. These are commonly used alongside batch/queue/cron processing for error logging, notification emails, and configurable message templates.

## Logging -- PSR-3 Logger Channels

Drupal uses PSR-3 logger channels for structured logging. Each module gets its own channel.

### Getting a logger

```php
// Static access (in procedural code or hooks):
\Drupal::logger('my_module')->error('Import failed for @item', [
  '@item' => $name,
]);

// Dependency injection (in services/controllers):
// Inject 'logger.factory' service (LoggerChannelFactoryInterface).
$this->logger = $logger_factory->get('my_module');
$this->logger->warning('Skipped @count items', ['@count' => $skipped]);
```

### Log levels

Use the appropriate PSR-3 level:

| Level | When to use |
|-------|------------|
| `emergency` | System is unusable |
| `alert` | Immediate action required |
| `critical` | Critical conditions (component unavailable) |
| `error` | Runtime errors that need attention |
| `warning` | Exceptional but non-error conditions |
| `notice` | Normal but significant events |
| `info` | Informational messages |
| `debug` | Detailed debug information |

### Placeholder syntax

Drupal's logger supports three placeholder types in messages:

| Placeholder | Behavior | Use for |
|-------------|----------|---------|
| `@variable` | Sanitized with `Html::escape()` | User-provided text, entity labels |
| `%variable` | Sanitized and wrapped in `<em>` tags | Emphasized values in messages |
| `:variable` | Sanitized and treated as URL | Links, URIs |

```php
$this->logger->error('Failed to import @title from :url (processed %count items)', [
  '@title' => $product->label(),
  ':url' => $source_url,
  '%count' => $processed,
]);
```

### Custom logger channel in services.yml

Define a dedicated channel for your module:

```yaml
services:
  logger.channel.my_module:
    parent: container.trait.logger_channel_factory
    arguments: ['my_module']
```

Then inject `logger.channel.my_module` instead of using the factory.

> WRONG: Using `watchdog()` for logging. This function was removed in Drupal 8. Use logger channels: `\Drupal::logger('channel')->error('message')`.

## Mail -- hook_mail and Mail Manager

Drupal's mail system uses hook_mail to define message templates and the Mail Manager service to send.

### Define message templates with hook_mail

```php
/**
 * Implements hook_mail().
 */
function my_module_mail($key, &$message, $params) {
  switch ($key) {
    case 'import_complete':
      $message['subject'] = t('Import completed: @count items', [
        '@count' => $params['count'],
      ]);
      $message['body'][] = t('The import process has completed successfully.');
      $message['body'][] = t('@count items were imported.', [
        '@count' => $params['count'],
      ]);
      break;

    case 'import_error':
      $message['subject'] = t('Import failed');
      $message['body'][] = t('The import encountered an error: @error', [
        '@error' => $params['error_message'],
      ]);
      break;
  }
}
```

Key points:
- `$key` identifies which email template to use.
- `$message['subject']` is a single string.
- `$message['body']` is an array of strings (joined with newlines when sent).
- `$params` passes data from the send call to the template.

### Send mail via Mail Manager

```php
// Inject 'plugin.manager.mail' service or use static access.
$mail_manager = \Drupal::service('plugin.manager.mail');

$result = $mail_manager->mail(
  'my_module',        // Module defining hook_mail.
  'import_complete',  // $key matching hook_mail switch case.
  $to_email,          // Recipient email address.
  'en',               // Language code.
  [                   // $params passed to hook_mail.
    'count' => $imported_count,
  ]
);

if (!$result['result']) {
  \Drupal::logger('my_module')->error('Failed to send import notification.');
}
```

### Alter other modules' mail

```php
/**
 * Implements hook_mail_alter().
 */
function my_module_mail_alter(&$message) {
  if ($message['id'] === 'other_module_notification') {
    $message['headers']['Cc'] = 'admin@example.com';
  }
}
```

The `$message['id']` is formatted as `{module}_{key}`.

> WRONG: Using PHP `mail()` directly. Drupal's Mail Manager provides pluggable mail backends, proper formatting, hook_mail_alter support, and integration with contrib mail modules (SMTP, Mailgun, etc.). Always use the mail manager service.

## Token API -- dynamic string replacement

Tokens are standardized placeholders like `[node:title]` that modules can define and replace. Used in configurable text fields, email templates, and path aliases.

### Define custom tokens with hook_token_info

```php
/**
 * Implements hook_token_info().
 */
function my_module_token_info() {
  $types = [
    'my_module' => [
      'name' => t('My Module'),
      'description' => t('Tokens related to My Module.'),
    ],
  ];

  $tokens = [
    'my_module' => [
      'import-count' => [
        'name' => t('Import count'),
        'description' => t('The number of items imported in the last run.'),
      ],
      'last-run' => [
        'name' => t('Last run'),
        'description' => t('The date of the last import run.'),
      ],
    ],
  ];

  return ['types' => $types, 'tokens' => $tokens];
}
```

### Implement token replacement with hook_tokens

```php
/**
 * Implements hook_tokens().
 */
function my_module_tokens($type, $tokens, array $data, array $options, $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'my_module') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'import-count':
          $replacements[$original] = \Drupal::state()->get('my_module.import_count', 0);
          break;

        case 'last-run':
          $timestamp = \Drupal::state()->get('my_module.last_run', 0);
          $replacements[$original] = \Drupal::service('date.formatter')
            ->format($timestamp, 'short');
          break;
      }
    }
  }

  return $replacements;
}
```

### Use Token service for replacement

```php
// Replace tokens in a string.
$text = \Drupal::token()->replace(
  'Last import: [my_module:import-count] items on [my_module:last-run]',
  ['my_module' => []]  // Data context (empty if tokens use global state).
);
```

### Standard token types

Drupal core provides these built-in token types:

| Type | Example tokens | Available when |
|------|---------------|----------------|
| `node` | `[node:title]`, `[node:nid]`, `[node:url]` | Node entity in context |
| `user` | `[user:name]`, `[user:mail]` | User entity in context |
| `site` | `[site:name]`, `[site:url]` | Always available |
| `date` | `[date:short]`, `[date:custom:Y-m-d]` | Always available |
| `current-user` | `[current-user:name]` | Always available |

> WRONG: Building custom placeholder replacement systems (regex-based `{variable}` patterns). Drupal's Token API provides standardized, discoverable, UI-friendly replacements that integrate with admin interfaces and contrib modules like Pathauto.

> RIGHT: Define tokens via hook_token_info() and hook_tokens(). Use `\Drupal::token()->replace()` for substitution.
