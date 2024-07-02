<?php

declare(strict_types=1);

namespace Drupal\rate_limit\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\rate_limit\Form\SettingsForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to limit api request in a time limit.
 */
final class RateLimitSubscriber implements EventSubscriberInterface {
  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a RateLimitSubscriber object.
   */
  public function __construct(Connection $connection, ConfigFactory $config) {
    $this->connection = $connection;
    $this->config = $config;
  }

  /**
   * Kernel request event handler.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
   *   In case the rate limit exceeds.
   */
  public function onKernelRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    $requestFormat = $event->getRequest()->getRequestFormat();
    $clientIp = $request->getClientIp();
    $config = $this->config->get(SettingsForm::CONFIG);
    $rateLimit = $config->get("rate_limit");
    $timeLimit = $config->get("time_limit");
    if ($requestFormat !== "json" || !$rateLimit || !$timeLimit) {
      return;
    }
    $didIpCrossRateLimit = $this->checkIfIpCrossedRateLimit($clientIp, (int) $rateLimit, (int) $timeLimit);
    if ($didIpCrossRateLimit !== FALSE) {
      throw new TooManyRequestsHttpException(0, "Rate limit exceeded. Please wait for another $didIpCrossRateLimit seconds before making more requests.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 30],
    ];
  }

  /**
   * The function to check if the current IP has reached the rate limit.
   *
   * @param string $ip
   *   The client ip of the request.
   * @param int $rateLimit
   *   The number of times an IP is allowed to make
   *   the api call before it rejects.
   * @param int $timeLimit
   *   The time limit within which the rate limit applies.
   *
   * @return false|int
   *   Returns false if the IP has not reached the limit yet,
   *   else returns the time until it resets.
   */
  protected function checkIfIpCrossedRateLimit(string $ip, int $rateLimit = 5, int $timeLimit = 30) {

    $ipDetails = $this->getIpDetails($ip);
    if (empty($ipDetails)) {
      $this->createNewIp($ip);
    }
    else {
      ['timestamp' => $timestamp, 'count' => $count] = (array) array_values($ipDetails)[0];
      $currentTimestamp = time();
      if ($currentTimestamp < $timestamp + $timeLimit) {
        $count++;
        if ($count > $rateLimit) {
          return $timestamp + $timeLimit - $currentTimestamp;
        }
        else {
          $this->updateIpDetails($ip, ['count' => $count]);
        }
      }
      else {
        $this->updateIpDetails($ip, ['timestamp' => time(), 'count' => 1]);
      }
    }
    return FALSE;
  }

  /**
   * The database call to fetch details based on IP.
   *
   * @param string $ip
   *   The client ip.
   */
  protected function getIpDetails(string $ip) {
    try {
      $query = $this->connection->select('rate_limit', 'rt');
      $query->fields('rt', ['timestamp', 'count']);
      $query->condition('ip', $ip);
      $result = $query->execute()->fetchAll();
      return $result;
    }
    catch (\Exception $e) {
      throw new \Exception("Unable to fetch details of Ip for rate limit");
    }
  }

  /**
   * The database call to update details based on IP.
   *
   * @param string $ip
   *   The client ip.
   * @param array $fields
   *   The array containing fields with values to be updated.
   */
  protected function updateIpDetails(string $ip, array $fields) {
    try {
      $this->connection->update('rate_limit')
        ->fields($fields)
        ->condition('ip', $ip)
        ->execute();
    }
    catch (\Exception $e) {
      throw new \Exception("Unable to update details of Ip for rate limit");
    }

  }

  /**
   * The database call to create new IP.
   *
   * @param string $ip
   *   The client ip.
   */
  protected function createNewIp(string $ip) {
    try {
      $this->connection->insert('rate_limit')
        ->fields([
          'ip' => $ip,
          'timestamp' => time(),
          'count' => 1,
        ])
        ->execute();
    }
    catch (\Exception $e) {
      throw new \Exception("Unable to create new Ip to rate limit");
    }
  }

}
