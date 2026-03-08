<?php

namespace Drupal\group_ai_pm\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the Project entity.
 */
class ProjectViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add a computed task_count field.
    $data['group_ai_pm_project']['task_count'] = [
      'title' => $this->t('Task count'),
      'help' => $this->t('The number of tasks associated with this project.'),
      'field' => [
        'id' => 'project_task_count',
      ],
    ];

    return $data;
  }

}
