<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;

/**
 * Form for content for SMS messages.
 */
class SmsContentForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      SmsPlanetEnums::SMS_CONTENT_CONFIG_NAME->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return SmsPlanetEnums::SMS_CONTENT_FORM_ID->value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(SmsPlanetEnums::SMS_CONTENT_CONFIG_NAME->value);

    $form[SmsPlanetEnums::SMS_FROM->value] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sms send from'),
      '#default_value' => $config->get(SmsPlanetEnums::SMS_FROM->value),
      '#description' => $this->t('Receiver of SMS will see this as sender title. Uppercase letters only.'),
      '#required' => TRUE,
    ];

    $form['info'] = [
      '#markup' => $this->t('Available tokens:'),
    ];
    $form['tokens'] = [
      '#markup' => SmsPlanetEnums::getAvailableTokensHtmlList(),
    ];
    $max_length = 160;
    $form[SmsPlanetEnums::CONTENT_EXAMPLE_MESSAGE->value] = [
      '#type' => 'textarea',
      '#title' => $this->t('Example message'),
      '#default_value' => $config->get(SmsPlanetEnums::CONTENT_EXAMPLE_MESSAGE->value),
      '#description' => $this->t('SMS content for example message'),
      '#maxlength' => $max_length,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config(SmsPlanetEnums::SMS_CONTENT_CONFIG_NAME->value);

    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $name => $value) {
      $config->set($name, $value);
    }
    $config->save();
    $this->messenger()->addMessage($this->t('Settings have been saved.'));

    parent::submitForm($form, $form_state);
  }

}
