<?php

declare(strict_types=1);

namespace Drupal\d_sms_planet\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\d_sms_planet\Enums\SmsPlanetEnums;
use Drupal\encryption\EncryptionService;
use Drupal\user\UserInterface;
use SMSPLANET\PHP\Client;

/**
 * Allows to send SMS via SMS Planet Gateway.
 */
class SmsPlanetService {

    /**
     * The client.
     *
     * @var \SMSPLANET\PHP\Client
     */
    protected Client $client;

    /**
     * The api config.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected ImmutableConfig $apiConfig;

    /**
     * The content config.
     *
     * @var \Drupal\Core\Config\ImmutableConfig
     */
    protected ImmutableConfig $contentConfig;

    /**
     * The logger.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     *  Constructs a new SmsActions object.
     *
     *   @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *     The config factory.
     * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
     *    The logger.
     * @param \Drupal\encryption\EncryptionService $encryption_service
     *   The encryption service.
     * @param \Drupal\Component\Uuid\UuidInterface $uuid
     *   The uuid generator.
     */
    public function __construct(
        protected ConfigFactoryInterface    $config_factory,
        LoggerChannelFactory    $loggerFactory,
        protected EncryptionService     $encryption_service,
        protected UuidInterface     $uuid,
    ) {
        $this->apiConfig = $this->config_factory->get(SmsPlanetEnums::SMS_PLANET_CONFIG_NAME->value);
        $this->contentConfig = $this->config_factory->get(SmsPlanetEnums::SMS_CONTENT_CONFIG_NAME->value);
        $this->logger = $loggerFactory->get(SmsPlanetEnums::SMS_LOGGER_CHANNEL->value);

        // Initialize the client with decrypted key and password.
        $key = $this->encryption_service->decrypt($this->apiConfig->get(SmsPlanetEnums::SMS_KEY->value));
        $password = $this->encryption_service->decrypt($this->apiConfig->get(SmsPlanetEnums::SMS_PASSWORD->value));
        $this->client = new Client([
            'key' => $key,
            'password' => $password,
        ]);
    }

    /**
     * Send SMS to user via SMS Planet Gateway.
     *
     * @param \Drupal\user\UserInterface $user
     *   The user.
     * @param string $content_id
     *   The content id.
     */
    public function sendSmsToUser(UserInterface $user, string $content_id): void {

        $from = $this->apiConfig->get(SmsPlanetEnums::SMS_FROM->value);
        $user_phone_number = $user->get('field_phone_number')->value ?? '';

        // Replace tokens in content.
        $sms_content = $this->contentConfig->get($content_id);
        $sms_content = str_replace(SmsPlanetEnums::TOKEN_USER_DISPLAY_NAME->value, $user->getDisplayName(), $sms_content);
        $sms_content = str_replace(SmsPlanetEnums::TOKEN_USER_FIRST_NAME->value, $user->get('field_first_name')->value ?? '', $sms_content);
        $sms_content = str_replace(SmsPlanetEnums::TOKEN_USER_LAST_NAME->value, $user->get('field_last_name')->value ?? '', $sms_content);
        $sms_content = str_replace(SmsPlanetEnums::TOKEN_USER_EMAIL->value, $user->getEmail(), $sms_content);
        $sms_content = str_replace(SmsPlanetEnums::TOKEN_USER_PHONE_NUMBER->value, $user_phone_number, $sms_content);

        // Send SMS via SMS client.
        $this->sendSms($from, $user_phone_number, $sms_content);
    }

    /**
     * Send SMS to a phone number via SMS Planet Gateway.
     *
     * @param string $to
     *   The phone number.
     * @param string $msg
     *   The message.
     * @param string $from
     *   The sender name.
     */
    public function sendSms(string $to, string $msg, string|null $from = NULL): int|bool {
        // Test mode.
        $test = $this->apiConfig->get(SmsPlanetEnums::TEST_MODE->value);

        if (empty($from)){
            $from = $this->contentConfig->get(SmsPlanetEnums::SMS_FROM->value);
        }
        // Uuid.
        $uuid = $this->uuid->generate();

        $this->logger->info("Attempting to send SMS to: $to from: ```$from``` with message: ```$msg```. Test mode: $test. ATTEMPT_UUID: $uuid");

        try
        {
            $message_id = $this->client->sendSMS([
                'from' => strtoupper($from),
                'to' => $to,
                'msg' => $msg,
                'test' => (int) $test,
            ]);
            $this->logger->info("SMS sent! message_id: $message_id.\nATTEMPT_UUID: $uuid");

            return $message_id;
        }
        catch (\Exception $e)
        {
            $error_msg = 'Error sending sms to:' . $to . ' - ' . $e->getCode() . ' - ' . $e->getMessage() . ' ATTEMPT_UUID: ' . $uuid;
            $this->logger->error($error_msg);
            return FALSE;
        }
    }

    /**
     * Send Test SMS.
     *
     * @return array
     *   Array of status and message
     */
    public function sendTestSms(string $phone_number, string $from, string $content, int $test = 1): array {

        try {
            $message_id = $this->client->sendSMS([
                'from' => $from,
                'to' => $phone_number,
                'msg' => $content,
                'test' => $test,
            ]);
        }
        catch (\Exception $e) {
            return ['message' => $e->getCode() . ' - ' . $e->getMessage(), 'status' => 'error'];
        }

        return ['message' => "SMS sent! message_id: $message_id", 'status' => 'success'];
    }

}
