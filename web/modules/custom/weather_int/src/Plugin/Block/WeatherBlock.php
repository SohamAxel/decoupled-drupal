<?php

namespace Drupal\weather_int\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\weather_int\WeatherApiIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Weather block.
 *
 * @Block(
 *  id = "weather_block",
 *  admin_label = @Translation("Weather Block"),
 *  category = @Translation("Weather"),
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The weather api integration service.
   *
   * @var \Drupal\weather_int\WeatherApiIntegration
   */
  protected $weatherApiInt;

  /**
   * Constructs a block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\weather_int\WeatherApiIntegration $weatherApiInt
   *   The weather api integration service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WeatherApiIntegration $weatherApiInt) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->weatherApiInt = $weatherApiInt;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get("weather_int.weatherapi_integration")
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $location = $this->configuration['weather_location'];
    $data = $this->weatherApiInt->getCurrentWeatherData($location);
    if ($data['isSuccessful']) {
      $currentWeatherInformation = $data['content'];
      $block = [
        '#theme' => 'weather_display',
        '#location' => "{$currentWeatherInformation['location']['name']}, {$currentWeatherInformation['location']['region']}, {$currentWeatherInformation['location']['country']}",
        '#icon' => $currentWeatherInformation['current']['condition']['icon'],
        '#temperature' => $currentWeatherInformation['current']['temp_c'],
        '#wind' => $currentWeatherInformation['current']['wind_kph'],
        '#feels_like' => $currentWeatherInformation['current']['feelslike_c'],
        '#precipitation' => $currentWeatherInformation['current']['precip_mm'],
        '#cache' => [
          'max-age' => 3600,
        ],
      ];
    }
    else {
      $block = [
        '#markup' => "Failed to fetch weather information",
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['weather_location'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Location'),
      '#description' => $this->t("The Weather location"),
      '#default_value' => $this->configuration['weather_location'] ?? "",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['weather_location'] = $form_state->getValue('weather_location');
  }

}
