# File and Image Fields

This reference covers file and image base field definitions for content entities. These field types are entity references to File entities with special handling for uploads, display, and usage tracking.

## Image field definition

Use the `image` field type for image uploads. It extends the `file` field type with image-specific settings (dimensions, alt text, title).

```php
$fields['image'] = BaseFieldDefinition::create('image')
  ->setLabel(t('Image'))
  ->setDescription(t('The product image.'))
  ->setSettings([
    'file_extensions' => 'png gif jpg jpeg webp',
    'alt_field' => TRUE,
    'alt_field_required' => TRUE,
    'title_field' => FALSE,
    'max_resolution' => '2000x2000',
    'min_resolution' => '100x100',
    'file_directory' => 'product_images',
  ])
  ->setDisplayOptions('view', [
    'type' => 'image',
    'weight' => 0,
    'settings' => [
      'image_style' => 'large',
      'image_link' => '',
    ],
  ])
  ->setDisplayOptions('form', [
    'type' => 'image_image',
    'weight' => 5,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

Default `file_extensions` for image fields: `png gif jpg jpeg`. Add `webp` if needed. The `image_image` widget provides the standard upload UI with preview.

### Image display formatters

| Formatter | Purpose | Key settings |
|-----------|---------|-------------|
| `image` | Renders image with optional style | `image_style`, `image_link` |
| `image_url` | Outputs URL instead of img tag | `image_style` |

The `image_style` setting references image style configuration entities (e.g., `large`, `medium`, `thumbnail`).

## File field definition

Use the `file` field type for generic file uploads (PDFs, documents, archives).

```php
$fields['document'] = BaseFieldDefinition::create('file')
  ->setLabel(t('Document'))
  ->setDescription(t('An attached document.'))
  ->setSettings([
    'file_extensions' => 'pdf doc docx txt',
    'file_directory' => 'product_documents',
    'max_filesize' => '10 MB',
    'uri_scheme' => 'public',
  ])
  ->setDisplayOptions('view', [
    'type' => 'file_default',
    'weight' => 5,
  ])
  ->setDisplayOptions('form', [
    'type' => 'file_generic',
    'weight' => 5,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

### File display formatters

| Formatter | Purpose |
|-----------|---------|
| `file_default` | Link to file with icon and size |
| `file_url_plain` | Plain URL string |
| `file_table` | Table of files with metadata |

## Managed vs unmanaged files

**Managed files** are File entities tracked in the `file_managed` table. Usage is tracked in the `file_usage` table. When an entity with a file/image field is saved, Drupal automatically records file usage. Orphaned files (zero usage) are cleaned up by cron.

**Unmanaged files** are raw filesystem operations without entity tracking. Use `\Drupal::service('file_system')` for unmanaged file operations. Prefer managed files when attaching files to entities.

## Programmatic file handling

To programmatically create a managed file and attach it to an entity:

```php
use Drupal\Core\File\FileSystemInterface;

// Write data as a managed file.
$file = \Drupal::service('file.repository')->writeData(
  $data,
  'public://product_images/photo.jpg',
  FileSystemInterface::EXISTS_REPLACE
);

// Attach to entity via the file field.
$product->set('image', $file->id());
$product->save();
```

The `FileRepositoryInterface::writeData()` method creates a File entity and writes the data to disk. The third parameter controls collision behavior:

| Constant | Behavior |
|----------|----------|
| `FileSystemInterface::EXISTS_REPLACE` | Overwrite existing file |
| `FileSystemInterface::EXISTS_RENAME` | Append number to filename |
| `FileSystemInterface::EXISTS_ERROR` | Return FALSE if file exists |

## Accessing file field values

```php
// Get the File entity from an image/file field.
$file = $entity->get('image')->entity;

// Get the file URI (e.g., 'public://product_images/photo.jpg').
$uri = $file->getFileUri();

// Get a URL from the URI.
$url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
```

## Media entities (recommended for D10+)

For richer media management, use entity reference fields pointing to Media entities instead of raw file/image fields. Media entities wrap File entities with additional metadata and integrate with Drupal's media library. However, for simple use cases or programmatic imports, direct file/image fields are simpler and sufficient.
