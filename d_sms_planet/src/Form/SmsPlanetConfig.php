<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SmsPlanet configuration form such password and api key.
 */
class SmsPlanetConfig extends ConfigFormBase {

  /**
   * Constructs a SmsPlanetConfig object.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param EncryptionService $encryption_service
   *   The encryption service.
   * @param TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected EncryptionService $encryption_service,
    TypedConfigManagerInterface $typedConfigManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption'),
      $container->get('config.typed'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      SmsPlanetEnums::SMS_PLANET_CONFIG_NAME->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return SmsPlanetEnums::SMS_PLANET_CONFIG_FORM_ID->value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(SmsPlanetEnums::SMS_PLANET_CONFIG_NAME->value);

    $key_encrypted = $config->get(SmsPlanetEnums::SMS_KEY->value);
    $key_for_display = $this->getPasswordForDisplay($key_encrypted);
    $form[SmsPlanetEnums::SMS_KEY->value] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $key_for_display,
      '#description' => $this->t('Planet SMS API Key'),
      '#required' => TRUE,
    ];

    $password_encrypted = $config->get(SmsPlanetEnums::SMS_PASSWORD->value);
    $password_for_display = $this->getPasswordForDisplay($password_encrypted);
    $form[SmsPlanetEnums::SMS_PASSWORD->value] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Password'),
      '#default_value' => $password_for_display,
      '#description' => $this->t('Planet SMS API Password'),
      '#required' => TRUE,
    ];

    $form[SmsPlanetEnums::TEST_MODE->value] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Mode'),
      '#default_value' => $config->get(SmsPlanetEnums::TEST_MODE->value),
      '#description' => $this->t('Enables TEST mode for SMS Planet API. No SMS will be sent. Sms content and Api response will be logged only.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(SmsPlanetEnums::SMS_PLANET_CONFIG_NAME->value);

    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $name => $value) {
      if (is_string($value) && preg_match('/^(\*){4,}/', $value)) {
        continue;
      }
      // Encrypt password and key.
      if ($name === SmsPlanetEnums::SMS_KEY->value || $name === SmsPlanetEnums::SMS_PASSWORD->value) {
        $value = $this->encryption_service->encrypt($value);
      }

      $config->set($name, $value);
    }
    $config->save();
    $this->messenger()->addMessage($this->t('Settings have been saved.'));

    parent::submitForm($form, $form_state);
  }

  /**
   * Get password for display.
   *
   * @param string|null $encrypted_string
   *   The encrypted string.
   *
   * @return string
   *   The password for display.
   */
  private function getPasswordForDisplay(?string $encrypted_string): string {
    if ($encrypted_string === NULL) {
      return '***********';
    }

    $password = $this->encryption_service->decrypt($encrypted_string);
    $length = strlen($password);

    if (empty($encrypted_string) || $length <= 3) {
      return '***********';
    }

    $sms_password_hidden = str_repeat('*', $length - 3);
    $sms_password_end = substr($password, -3);

    return $sms_password_hidden . $sms_password_end;
  }

}
