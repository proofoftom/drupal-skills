<?php

namespace Drupal\search_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure search parameters.
 */
class SearchSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['search_settings.settings'];
  }

  public function getFormId() {
    return 'search_settings_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('search_settings.settings');

    $form['similarity_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Similarity threshold'),
      '#description' => $this->t('Minimum similarity score (0–1) for search results.'),
      '#default_value' => $config->get('similarity_threshold') ?? 0.7,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#required' => TRUE,
    ];

    $form['result_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Result limit'),
      '#description' => $this->t('Maximum number of search results to return.'),
      '#default_value' => $config->get('result_limit') ?? 5,
      '#min' => 1,
      '#required' => TRUE,
    ];

    $form['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL'),
      '#description' => $this->t('Time in seconds to cache search results.'),
      '#default_value' => $config->get('cache_ttl') ?? 300,
      '#min' => 0,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('search_settings.settings')
      ->set('similarity_threshold', (float) $form_state->getValue('similarity_threshold'))
      ->set('result_limit', (int) $form_state->getValue('result_limit'))
      ->set('cache_ttl', (int) $form_state->getValue('cache_ttl'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
