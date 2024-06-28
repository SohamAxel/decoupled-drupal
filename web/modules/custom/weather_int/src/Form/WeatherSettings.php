<?php

namespace Drupal\weather_int\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form for module settings.
 */
class WeatherSettings extends ConfigFormBase {

  /**
   * The name of the config.
   *
   * @var string
   */
  const CONFIG_NAME = "weather_int.config";

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "weather_int.config.form";
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api key'),
      '#description' => $this->t('Add the api key provided by weatherapi.com'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base Url'),
      '#description' => $this->t('Add the base url provided by weatherapi.com'),
      '#default_value' => $config->get('base_url'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('base_url', $form_state->getValue('base_url'))
      ->save();
  }

}
