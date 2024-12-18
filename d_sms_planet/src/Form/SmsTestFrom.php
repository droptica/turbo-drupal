<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;
use Drupal\d_sms_planet\Service\SmsPlanetService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SmsPlanet configuration form.
 */
class SmsTestFrom extends ConfigFormBase {

  /**
   * Constructs a SmsTestFrom object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config manager.
   * @param \Drupal\d_sms_planet\Service\SmsPlanetService $smsActions
   *   The sms actions service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config,
    protected SmsPlanetService $smsActions,
  ) {
    parent::__construct($config_factory, $typed_config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('d_sms_planet.send_sms')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      SmsPlanetEnums::SMS_TEST_CONFIG_NAME->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return SmsPlanetEnums::SMS_TEST_FORM_ID->value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(SmsPlanetEnums::SMS_TEST_CONFIG_NAME->value);

    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sms Phone Number'),
      '#default_value' => $config->get('sms_test'),
      '#description' => $this->t('Phone number to send sms to.'),
    ];

    $form['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender Name'),
      '#default_value' => $config->get('from'),
      '#description' => $this->t('Receiver of SMS will see this as sender title. Uppercase letters only.'),
    ];

    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#default_value' => $config->get('content'),
      '#maxlength' => 160,
      '#description' => $this->t('SMS content. Max 160 characters. If has polish letters, max 70 characters.'),
    ];

    $form['test'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Test SMS'),
      '#default_value' => $config->get('test'),
      '#description' => $this->t('If checked SMS will only return mock response. Unchecked - SMS will be sent.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(SmsPlanetEnums::SMS_TEST_CONFIG_NAME->value);

    $phone_number = $form_state->getValue('phone_number');
    $from = $form_state->getValue('from') ?? 'TEST';
    $content = $form_state->getValue('content');
    $test = $form_state->getValue('test');

    $config->set('sms_test', $phone_number);
    $config->set('from', strtoupper($from));
    $config->set('content', $content);
    $config->set('test', $test);
    $config->save();

    $sms = $this->smsActions->sendTestSms($phone_number, $from, $content, $test);

    if ($sms['status'] === 'error') {
      $this->messenger()->addMessage($sms['message'], 'error');

      return;
    }

    $this->messenger()->addMessage($sms['message']);
  }

}
