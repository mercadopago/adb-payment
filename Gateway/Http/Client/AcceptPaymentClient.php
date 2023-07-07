<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Http\Client;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\ExtPaymentIdDataRequest;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;
use MercadoPago\AdbPayment\Gateway\Request\CaptureAmountRequest;

/**
 * Communication with the Gateway to Accept Payment.
 */
class AcceptPaymentClient implements ClientInterface
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
     * Response Pay Status - Block Name.
     */
    public const RESPONSE_STATUS = 'status';

    /**
     * Response Pay Status Approved - Value.
     */
    public const RESPONSE_STATUS_APPROVED = 'approved';

    /**
     * Response Pay Status Denied - Value.
     */
    public const RESPONSE_STATUS_ERROR = 'ERROR';

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
        $sendData = [];
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, $requester);
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);
        $paymentId = $request[ExtPaymentIdDataRequest::MP_REFERENCE_ID];
        $uri = '/v1/payments/' . $paymentId;
        unset($request[ExtPaymentIdDataRequest::MP_REFERENCE_ID]);
        unset($request[self::STORE_ID]);

        $sendData['capture'] = true;

        if (!empty($request[CaptureAmountRequest::AMOUNT_TO_CAPTURE]) && $request[CaptureAmountRequest::AMOUNT_TO_CAPTURE] > 0) {
            $sendData['transaction_amount'] = $request[CaptureAmountRequest::AMOUNT_TO_CAPTURE];
        }

        $serializeResquest = $this->json->serialize($sendData);

        try {
            $responseBody = $client->put($uri, $clientHeaders, $serializeResquest);
            $data = $responseBody->getData();
            $response = array_merge(
                [
                    self::RESULT_CODE => 0,
                ],
                $data
            );
            if (
                isset($data[self::RESPONSE_STATUS]) &&
                $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_APPROVED
            ) {
                $response = array_merge(
                    [
                        self::RESULT_CODE => 1,
                    ],
                    $data
                );
            }
            $this->logger->debug(
                [
                    'url'      => $baseUrl . '/v1/payments/' . $paymentId,
                    'request'  => $this->json->serialize($sendData),
                    'response' => $this->json->serialize($response),
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . '/v1/payments/' . $paymentId,
                    'request'   => $this->json->serialize($sendData),
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } catch (\Throwable $exc) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . $uri,
                    'request'   => $this->json->serialize($sendData),
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($exc->getMessage());
        }

        return $response;
    }
}
