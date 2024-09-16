<?php

declare(strict_types=1);

namespace Drupal\api_modification\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber to add callbacks on api responses.
 */
final class ApiResponseSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an ApiResponseSubscriber object.
   */
  public function __construct(
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Kernel response event handler.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    $request = $event->getRequest();
    $routeName = $request->get('_route');
    if ($routeName == 'jsonapi.node--article.individual.delete') {
      $response = $event->getResponse();
      if ($response->getStatusCode() == Response::HTTP_NO_CONTENT) {
        /** @var \Drupal\node\Entity\Node */
        $node = $request->attributes->get('entity');
        $this->loggerFactory->get('article_delete_api')->debug("Article '@label' (@nid) deleted via API.", [
          '@label' => $node->label(),
          '@nid' => $node->id(),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
