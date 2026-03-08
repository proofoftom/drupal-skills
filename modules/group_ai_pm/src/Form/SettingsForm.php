<?php

namespace Drupal\group_ai_pm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Group AI Project Management settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_ai_pm_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group_ai_pm.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('group_ai_pm.settings');

    $form['default_project_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Project Status'),
      '#description' => $this->t('The default status for new projects.'),
      '#options' => [
        'planning' => $this->t('Planning'),
        'active' => $this->t('Active'),
        'review' => $this->t('Review'),
        'completed' => $this->t('Completed'),
      ],
      '#default_value' => $config->get('default_project_status') ?? 'planning',
    ];

    $form['default_task_status'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Task Status'),
      '#description' => $this->t('The default status for new tasks.'),
      '#options' => [
        'todo' => $this->t('To Do'),
        'in_progress' => $this->t('In Progress'),
        'review' => $this->t('Review'),
        'done' => $this->t('Done'),
      ],
      '#default_value' => $config->get('default_task_status') ?? 'todo',
    ];

    $form['ai_provider'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AI Provider'),
      '#description' => $this->t('The AI provider to use for this module.'),
      '#default_value' => $config->get('ai_provider') ?? '',
      '#maxlength' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('group_ai_pm.settings')
      ->set('default_project_status', $form_state->getValue('default_project_status'))
      ->set('default_task_status', $form_state->getValue('default_task_status'))
      ->set('ai_provider', $form_state->getValue('ai_provider'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
