<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * Communication with Gateway to refund payment.
 */
class RefundClientTwoCc implements ClientInterface
{
  /**
   * Result Code - Block name.
   */
  public const RESULT_CODE = 'RESULT_CODE';

  /**
   * Store Id - Block name.
   */
  public const STORE_ID = 'store_id';

  /**
   * Response Refund Id - Block name.
   */
  public const RESPONSE_REFUND_ID = 'id';

  /**
   * Response Pay Status - Block Name.
   */
  public const RESPONSE_STATUS = 'status';

  /**
   * Response Pay Status Denied - Value.
   */
  public const RESPONSE_STATUS_DENIED = 'DENIED';

  /**
   * Idempotency Key block name.
   */
  public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';

  /**
   * Notification Origin - Magento
   */
  public const NOTIFICATION_ORIGIN = 'magento';

  /**
   * @var Logger
   */
  protected $logger;

  /**
   * @var Config
   */
  protected $config;

  /**
   * @var Json
   */
  protected $json;

  /**
   * @param Logger            $logger
   * @param Config            $config
   * @param Json              $json
   */
  public function __construct(
    Logger $logger,
    Config $config,
    Json $json
  ) {
    $this->config = $config;
    $this->logger = $logger;
    $this->json = $json;
  }

  /**
   * Places request to gateway.
   *
   * @param TransferInterface $transferObject
   *
   * @return array
   */
  public function placeRequest(TransferInterface $transferObject)
  {
      $requester = new CurlRequester();
      $baseUrl = $this->config->getApiUrl();
      $client  = new HttpClient($baseUrl, $requester);

      $request = $transferObject->getBody();
      $storeId = $request[self::STORE_ID];
      $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);
      $clientHeaders = array_merge_recursive($clientHeaders, [
          self::X_IDEMPOTENCY_KEY.': '.$request[self::X_IDEMPOTENCY_KEY],
      ]);

    $status = '';

    $paymentId = $request['payment_id'];

    unset($request['payment_id']);
    unset($request[self::STORE_ID]);
    unset($request[self::X_IDEMPOTENCY_KEY]);
    $metadata = ['origem' => self::NOTIFICATION_ORIGIN];

    $uriRefund = '/v1/asgard/multipayments/' . $paymentId . '/refund';

    try {
        $request = (object) array_merge( (array)$request, array( 'metadata' => $metadata ) );
        $result = $client->post($uriRefund, $clientHeaders, $this->json->serialize($request));

      $data = $result->getData();

      $refundIds = [];

      $status = null;

      foreach ($data as $arrayData) {

        if (isset($arrayData[self::RESPONSE_REFUND_ID])) {
          $refundIds[] = $arrayData[self::RESPONSE_REFUND_ID];
        }

        if (isset($arrayData[self::RESPONSE_STATUS])) {
          $status = $arrayData[self::RESPONSE_STATUS];
        }
      }

      $response = array_merge(
        [
          self::RESULT_CODE         => ($status !== self::RESPONSE_STATUS_DENIED) ? 1 : 0,
          self::RESPONSE_REFUND_ID  => implode('_', $refundIds),
          self::RESPONSE_STATUS     => $status,
        ],
        $data
      );

      $this->logger->debug(
        [
          'url'      => $baseUrl . $uriRefund,
          'request'  => $this->json->serialize($request),
          'response' => $this->json->serialize($response),
        ]
      );
    } catch (InvalidArgumentException $exc) {
      $this->logger->debug(
        [
          'url'       => $baseUrl . $uriRefund,
          'request'   => $this->json->serialize($request),
          'error'     => $exc->getMessage(),
        ]
      );
      // phpcs:ignore Magento2.Exceptions.DirectThrow
      throw new Exception('Invalid JSON was returned by the gateway');
    } catch (\Throwable $e) {
        $this->logger->debug(
            [
                'url'       => $baseUrl . $uriRefund,
                'request'   => $this->json->serialize($request),
                'error'     => $e->getMessage(),
            ]
        );
        // phpcs:ignore Magento2.Exceptions.DirectThrow
        throw new Exception($e->getMessage());
    }

    return $response;
  }
}
