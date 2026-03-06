<?php

/**
 * @file
 * Views integration for the resource_directory module.
 */

/**
 * Implements hook_views_data().
 */
function resource_directory_views_data() {
  $data = [];

  // Table group -- groups all fields under "Resource Directory" in the Views UI.
  $data['resource_links']['table']['group'] = t('Resource Directory');

  // Base table definition -- makes resource_links usable as a Views base.
  $data['resource_links']['table']['base'] = [
    'field' => 'id',
    'title' => t('Resource Links'),
    'help' => t('Contains resource directory entries with title, URL, category, and creation date.'),
  ];

  // id field.
  $data['resource_links']['id'] = [
    'title' => t('ID'),
    'help' => t('The unique resource ID.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  // title field.
  $data['resource_links']['title'] = [
    'title' => t('Title'),
    'help' => t('The resource title.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // url field.
  $data['resource_links']['url'] = [
    'title' => t('URL'),
    'help' => t('The resource URL.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // category field -- uses custom dropdown filter plugin.
  $data['resource_links']['category'] = [
    'title' => t('Category'),
    'help' => t('The resource category.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'resource_directory_category',
      'title' => t('Category (dropdown)'),
      'help' => t('Filter resources by category using a dropdown list of existing values.'),
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // created field -- Unix timestamp.
  $data['resource_links']['created'] = [
    'title' => t('Created'),
    'help' => t('The date and time the resource was created.'),
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
  ];

  return $data;
}
