<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;
use Drupal\d_sms_planet\Service\SmsPlanetService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs SMS Planet Queue to not overload the API.
 *
 * @QueueWorker(
 *   id = "d_sms_planet_queue",
 *   title = @Translation("SMS Planet Queue"),
 *   cron = {"time" = 300}
 *   )
 */
class SmsPlanetQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new SmsPlanetQueue object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\d_sms_planet\Service\SmsPlanetService $smsPlanetService
   *   The sms planet service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   The logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected SmsPlanetService $smsPlanetService,
    protected LoggerChannel $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('d_sms_planet.send_sms'),
      $container->get('logger.factory')->get(SmsPlanetEnums::SMS_LOGGER_CHANNEL->value),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['user']) || !isset($data['content_id'])) {
      $this->logger->error('SMS Planet Queue: Missing data.');
      return;
    }

    if (!$data['user'] instanceof UserInterface) {
      $this->logger->error('SMS Planet Queue: User is not an instance of UserInterface.');
      return;
    }

    if (!is_string($data['content_id'])) {
      $this->logger->error('SMS Planet Queue: Content ID is not a string.');
      return;
    }

    if (array_search($data['content_id'], SmsPlanetEnums::getPolicyContentIds()) === FALSE) {
      $this->logger->error('SMS Planet Queue: Content ID is not a valid content ID.');
      return;
    }

    $this->smsPlanetService->sendSmsToUser($data['user'], $data['content_id']);

    $this->logger->info('SMS sent to user: ' . $data['user']->id());
  }

}
