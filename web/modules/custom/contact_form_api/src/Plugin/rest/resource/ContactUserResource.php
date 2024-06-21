<?php

namespace Drupal\contact_form_api\Plugin\rest\resource;

use Drupal\contact\MailHandler;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Provides a resource to contact users through email.
 *
 * @RestResource(
 *  id = "contact_user_resource",
 *  label = @Translation("Contact User Resource"),
 *  uri_paths = {
 *    "create" = "/api/contact-user"
 *  }
 * )
 */
class ContactUserResource extends ResourceBase {

  /**
   * The error message incase an values are missing.
   *
   * @var string
   */
  const MISSING_DATA_ERROR_MSG = "Missing data. Subject, Recipient, Message values are required. Optional value Copy can be send to send yourself a copy";

  /**
   * The error message incase an recipient not found.
   *
   * @var string
   */
  const RECIPIENT_NOT_FOUND_ERROR_MSG = "Unable to find the recipient, please provide a valid recipient";

  /**
   * The success message if process was successful.
   *
   * @var string
   */
  const SUCCESS_MSG = "Contact form submitted successfully";

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The mail handler service for contact entities.
   *
   * @var \Drupal\contact\MailHandler
   */
  protected $contactMailHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   A current user object.
   * @param \Drupal\contact\MailHandler $contactMailHandler
   *   The mail handler service for contact entities.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $currentUser,
    MailHandler $contactMailHandler,
    EntityTypeManager $entityTypeManager,
  ) {
    $this->contactMailHandler = $contactMailHandler;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;

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
      $container->get('current_user'),
      $container->get('contact.mail_handler'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a response based on the status of contact submission.
   *
   * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
   *   Throws error incase wrong data in payload.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   returns response if successful.
   */
  public function post($data) {
    // Destructuring the data.
    ['subject' => $subject, 'message' => $message, 'recipient' => $recipient, 'copy' => $copy] = $data;

    // Check if any required fields are empty.
    if (!isset($subject) || !isset($message) || !isset($recipient)) {
      throw new BadRequestException(static::MISSING_DATA_ERROR_MSG);
    }

    // Check if the recipient is a valid user in our site.
    $doesRecipientExist = !empty($this->entityTypeManager->getStorage('user')->getQuery()->accessCheck(FALSE)->condition("uid", $recipient, '=')->execute());
    if (!$doesRecipientExist) {
      throw new BadRequestException(static::RECIPIENT_NOT_FOUND_ERROR_MSG);
    }

    $contactEntity = $this->entityTypeManager->getStorage('contact_message')->create([
      'contact_form' => ["target_id" => "personal"],
      'subject' => $subject,
      'message' => $message,
      'copy' => $copy == NULL || $copy == 0 ? FALSE : TRUE,
      'recipient' => $recipient,
    ]);
    $this->contactMailHandler->sendMailMessages($contactEntity, $this->currentUser);

    return new ModifiedResourceResponse(static::SUCCESS_MSG);
  }
}
