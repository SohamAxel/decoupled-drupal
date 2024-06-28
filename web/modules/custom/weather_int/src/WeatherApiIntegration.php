<?php

namespace Drupal\weather_int;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\weather_int\Form\WeatherSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * The service to access data from weatherapi endpoints.
 */
class WeatherApiIntegration {

  /**
   * The base url of weather api.
   *
   * @var string
   */
  const BASE_WEATHERAPI_URL = "http://api.weatherapi.com/v1";

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Constructor class.
   *
   * @param \GuzzleHttp\Client $client
   *   The http client service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger service.
   */
  public function __construct(Client $client, ConfigFactory $config, LoggerChannelFactory $logger) {
    $this->client = $client;
    $this->config = $config->get(WeatherSettings::CONFIG_NAME);
    $this->logger = $logger;
  }

  /**
   * Get the current weather information for the location provided.
   *
   * @param string $location
   *   The weather location.
   *
   * @return array
   *   Array containing the status of api call and
   *   the response data if fetch was successful
   */
  public function getCurrentWeatherData(string $location) {
    // To store result data and status of API fetch.
    $returnData = [
      'isSuccessful' => TRUE,
      'content' => [],
    ];

    // The access key of weatherapi account.
    $access_key = $this->config->get("api_key");

    // The required endpoint parameters.
    $parameters = [
      'key' => $access_key,
      'q' => $location,
    ];

    // Building the request url.
    $currentWeatherEndpoint = static::BASE_WEATHERAPI_URL . "/current.json";
    $urlParams = http_build_query($parameters);
    $urlEndpoint = "$currentWeatherEndpoint?$urlParams";

    try {
      $response = $this->client->request('GET', $urlEndpoint, [
        'headers' => [
          'accept' => 'application/json',
        ],
      ]);

      if ($response->getStatusCode() == 200) {
        $content = $response->getBody()->getContents();
        $content = Json::decode($content);
        $returnData['content'] = $content;
      }
    }
    catch (ClientException $e) {
      $this->logger->get("weather_int_api")->error($e->getMessage());
      $returnData['isSuccessful'] = FALSE;
    }

    return $returnData;
  }

}
