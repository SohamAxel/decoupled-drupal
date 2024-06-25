<?php

namespace Drupal\site_config_api\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Provides a resource to export site configuration.
 *
 * @RestResource(
 *  id = "site_config_resource",
 *  label = @Translation("Site Config Resource"),
 *  uri_paths = {
 *    "canonical" = "/api/config-export/{config_name}"
 *  }
 * )
 */
class SiteConfigurationResource extends ResourceBase {
  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The constructor of the class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   A config factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    ConfigFactory $configFactory,
  ) {
    $this->configFactory = $configFactory;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns config data for valid configurations.
   *
   * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
   *   Throws error incase config not found.
   *
   * @return \Drupal\rest\ResourceResponse
   *   returns configuration if successful.
   */
  public function get(string $config_name) {

    $config = $this->configFactory->get($config_name);
    $doesConfigExist = !$config->isNew();

    if ($doesConfigExist) {
      $configData[$config_name] = $config->getRawData();
      return new ResourceResponse($configData);
    }
    else {
      throw new BadRequestException("Configuration ($config_name) does not exists.");
    }

  }

}
