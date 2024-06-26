<?php

namespace Drupal\site_config_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form to expose selected configs to frontend.
 */
class AllowConfigForm extends ConfigFormBase {

  /**
   * The name of the config.
   *
   * @var string
   */
  const CONFIG_NAME = "site_config_api.allowed_config";

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "site_config_api.allowed_config.form";
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
    $configList = $this->configFactory()->listAll();
    $options = [];
    $defaultValue = [];

    foreach ($configList as $configName) {
      $options[$configName] = $configName;
    }
    foreach ($config->get('allowed_configs') as $configName) {
      $defaultValue[$configName] = $configName;
    }

    $form['allowed_configs'] = [
      '#title' => "Allow Configs",
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 20,
      '#options' => $options,
      '#default_value' => $defaultValue,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $allowedConfigs = [];
    $selectedConfigs = $form_state->getValue('allowed_configs');
    foreach ($selectedConfigs as $value) {
      $allowedConfigs[] = $value;
    }
    $this->config(static::CONFIG_NAME)
      ->set('allowed_configs', $allowedConfigs)
      ->save();
  }

}
