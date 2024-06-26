<?php

namespace Drupal\site_config_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\site_config_api\Form\AllowConfigForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to list exposed site configuration.
 *
 * @RestResource(
 *  id = "site_config_list_resource",
 *  label = @Translation("Site Config List Resource"),
 *  uri_paths = {
 *    "canonical" = "/api/config-list"
 *  }
 * )
 */
class AllowedSiteConfigList extends ResourceBase {
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
   * Returns list of exposed config names.
   *
   * @return \Drupal\rest\ResourceResponse
   *   returns list of exposed config names if successful.
   */
  public function get() {
    $configData = $this->configFactory->get(AllowConfigForm::CONFIG_NAME)->get('allowed_configs') ?? [];
    $response = new ResourceResponse($configData);
    // Adding the config cache tag which invalidates the data
    // when allowed configs changes.
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'tags' => ['config:site_config_api.allowed_config'],
      ],
    ]));
    return $response;
  }

}
