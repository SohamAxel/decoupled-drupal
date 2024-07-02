<?php

declare(strict_types=1);

namespace Drupal\rate_limit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Rate Limit settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * The config name.
   *
   * @var string
   */
  const CONFIG = "rate_limit.settings";

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rate_limit.settings.form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::CONFIG];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::CONFIG);
    $form['rate_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rate Limit'),
      '#description' => $this->t("The number of times the call will be allowed before rejecting"),
      '#default_value' => $config->get('rate_limit'),
    ];
    $form['time_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time Limit (in seconds)'),
      '#description' => $this->t("The time limit within which the rate limit will work"),
      '#default_value' => $config->get('time_limit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(static::CONFIG)
      ->set('rate_limit', $form_state->getValue('rate_limit'))
      ->set('time_limit', $form_state->getValue('time_limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
