<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Enums;

/**
 * Contains config names, form ids, content ids, tokens and queue name.
 */
enum SmsPlanetEnums: string {
  // SMS Planet API Config.
  case SMS_PLANET_CONFIG_NAME = 'd_sms_planet.config';
  case SMS_PLANET_CONFIG_FORM_ID = 'd_sms_planet_config_form';
  case SMS_FROM = 'd_sms_planet_from_number';
  case SMS_KEY = 'd_sms_planet_key';
  case SMS_PASSWORD = 'd_sms_planet_password';
  case TEST_MODE = 'd_sms_planet_test_mode';


  // SMS Content Config.
  case SMS_CONTENT_CONFIG_NAME = 'd_sms_planet.content';
  case SMS_CONTENT_FORM_ID = 'd_sms_planet_content_form';

  // Test sms form.
  case SMS_TEST_FORM_ID = 'd_sms_planet_test_form';
  case SMS_TEST_CONFIG_NAME = 'd_sms_planet.test';

  // SMS Content IDs.
  case CONTENT_EXAMPLE_MESSAGE = 'd_sms_planet_content_example_message';

  // Tokens for SMS Content.
  case TOKEN_USER_DISPLAY_NAME = '[user:display_name]';
  case TOKEN_USER_FIRST_NAME = '[user:first_name]';
  case TOKEN_USER_LAST_NAME = '[user:last_name]';
  case TOKEN_USER_EMAIL = '[user:mail]';
  case TOKEN_USER_PHONE_NUMBER = '[user:phone_number]';

  // Queue.
  case SMS_QUEUE_NAME = 'd_sms_planet_queue';

  // Logger channel.
  case SMS_LOGGER_CHANNEL = 'd_sms_planet';

  // ECA Send SMS action plugin.
  case ECA_SENDER = 'sender';
  case ECA_RECEIVER = 'receiver';
  case ECA_MESSAGE = 'message';

  /**
   * Get all available policy content ids.
   *
   * @return array
   *   Array of policy content ids
   */
  public static function getPolicyContentIds(): array {
    return [
      self::CONTENT_EXAMPLE_MESSAGE->value,
    ];
  }

  /**
   * Get all available tokens as html list.
   *
   * @return string
   *   Html list of tokens
   */
  public static function getAvailableTokensHtmlList(): string {
    $tokens = [
      self::TOKEN_USER_DISPLAY_NAME->value,
      self::TOKEN_USER_FIRST_NAME->value,
      self::TOKEN_USER_LAST_NAME->value,
      self::TOKEN_USER_EMAIL->value,
      self::TOKEN_USER_PHONE_NUMBER->value,
    ];
    $list = '<ul>';
    foreach ($tokens as $token) {
      $list .= '<li>' . $token . '</li>';
    }
    $list .= '</ul>';

    return $list;
  }

}
