<?php

namespace Drupal\search_settings_baseline\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Search Settings Baseline.
 */
class SearchSettingsBaselineForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['search_settings_baseline.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'search_settings_baseline_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('search_settings_baseline.settings');

    $form['similarity_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Similarity threshold'),
      '#description' => $this->t('Minimum similarity score (0–1) for search results.'),
      '#default_value' => $config->get('similarity_threshold'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['result_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Result limit'),
      '#description' => $this->t('Maximum number of search results to return.'),
      '#default_value' => $config->get('result_limit'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    $form['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL'),
      '#description' => $this->t('Cache time-to-live in seconds.'),
      '#default_value' => $config->get('cache_ttl'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('search_settings_baseline.settings')
      ->set('similarity_threshold', (float) $form_state->getValue('similarity_threshold'))
      ->set('result_limit', (int) $form_state->getValue('result_limit'))
      ->set('cache_ttl', (int) $form_state->getValue('cache_ttl'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
