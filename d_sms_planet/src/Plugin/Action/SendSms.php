<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Plugin\Action;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;
use Drupal\d_sms_planet\Service\SmsPlanetService;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send sms ECA action.
 *
 * @Action(
 *   id = "eca_send_sms",
 *   label = @Translation("Send SMS"),
 *   description = @Translation("Send sms via SMS Planet Gateway.")
 * )
 */
class SendSms extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The sms planet service.
   *
   * @var \Drupal\d_sms_planet\Service\SmsPlanetService
   */
  protected SmsPlanetService $smsPlanetService;

  /**
   * The api config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $apiConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->smsPlanetService = $container->get('d_sms_planet.send_sms');
    $instance->apiConfig = $container->get('config.factory')->get(SmsPlanetEnums::SMS_PLANET_CONFIG_NAME->value);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $sender = $this->tokenService->replaceClear($this->configuration[SmsPlanetEnums::ECA_SENDER->value]);

    if (empty($sender)) {
      $sender = $this->apiConfig->get(SmsPlanetEnums::SMS_FROM->value);
    }

    $receiver = $this->tokenService->replaceClear($this->configuration[SmsPlanetEnums::ECA_RECEIVER->value]);
    $message = $this->tokenService->replaceClear($this->configuration[SmsPlanetEnums::ECA_MESSAGE->value]);

    // Send sms to the receiver.
    $this->smsPlanetService->sendSms($receiver, $message, $sender);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      SmsPlanetEnums::ECA_SENDER->value => '',
      SmsPlanetEnums::ECA_RECEIVER->value => '',
      SmsPlanetEnums::ECA_MESSAGE->value => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form[SmsPlanetEnums::ECA_SENDER->value] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender'),
      '#default_value' => $this->configuration[SmsPlanetEnums::ECA_SENDER->value],
      '#description' => $this->t('The sender of the sms message. If empty, the default sender from the SMS Planet configuration will be used.'),
      '#weight' => -30,
    ];
    $form[SmsPlanetEnums::ECA_RECEIVER->value] = [
      '#type' => 'textfield',
      '#title' => $this->t('Receiver of the sms message (phone number)'),
      '#description' => $this->t('Phone number of the person who needs to receive sms.'),
      '#default_value' => $this->configuration[SmsPlanetEnums::ECA_RECEIVER->value],
      '#weight' => -20,
    ];
    $form[SmsPlanetEnums::ECA_MESSAGE->value] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('The message, which should be send, can use tokens such [entity:nid] etc.'),
      '#default_value' => $this->configuration[SmsPlanetEnums::ECA_MESSAGE->value],
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration[SmsPlanetEnums::ECA_SENDER->value] = $form_state->getValue(SmsPlanetEnums::ECA_SENDER->value);
    $this->configuration[SmsPlanetEnums::ECA_RECEIVER->value] = $form_state->getValue(SmsPlanetEnums::ECA_RECEIVER->value);
    $this->configuration[SmsPlanetEnums::ECA_MESSAGE->value] = $form_state->getValue(SmsPlanetEnums::ECA_MESSAGE->value);
    parent::submitConfigurationForm($form, $form_state);
  }

}
