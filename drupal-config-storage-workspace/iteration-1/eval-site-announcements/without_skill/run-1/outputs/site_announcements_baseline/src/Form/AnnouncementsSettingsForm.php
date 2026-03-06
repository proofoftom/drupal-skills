<?php

namespace Drupal\site_announcements_baseline\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Site Announcements Baseline.
 */
class AnnouncementsSettingsForm extends ConfigFormBase {

  const SETTINGS = 'site_announcements_baseline.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'site_announcements_baseline_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['announcement_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Announcement text'),
      '#default_value' => $config->get('announcement_text'),
      '#required' => TRUE,
    ];

    $form['display_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display enabled'),
      '#default_value' => $config->get('display_enabled'),
    ];

    $form['max_display_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum display count'),
      '#default_value' => $config->get('max_display_count'),
      '#min' => 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::SETTINGS)
      ->set('announcement_text', $form_state->getValue('announcement_text'))
      ->set('display_enabled', (bool) $form_state->getValue('display_enabled'))
      ->set('max_display_count', (int) $form_state->getValue('max_display_count'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
