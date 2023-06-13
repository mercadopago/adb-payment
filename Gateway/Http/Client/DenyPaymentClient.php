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
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\ExtPaymentIdDataRequest;

/**
 * Communication with the Gateway to Deny Payment.
 *
 * @SuppressWarnings(PHPCPD)
 */
class DenyPaymentClient implements ClientInterface
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
     * Response Pay Status Cancelled - Value.
     */
    public const RESPONSE_STATUS_CANCELLED = 'cancelled';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

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
     * @param ZendClientFactory $httpClientFactory
     * @param Config            $config
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        ZendClientFactory $httpClientFactory,
        Config $config,
        Json $json
    ) {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
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

        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];
        $url = $this->config->getApiUrl();
        $clientConfigs = $this->config->getClientConfigs();
        $clientHeaders = $this->config->getClientHeaders($storeId);
        $paymentId = $request[ExtPaymentIdDataRequest::MP_REFERENCE_ID];
        $sendData['status'] = 'cancelled';

        try {
            $client->setUri($url.'/v1/payments/'.$paymentId);
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);
            $client->setRawData($this->json->serialize($sendData), 'application/json');
            $client->setMethod(ZendClient::PUT);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data[self::RESPONSE_STATUS]) &&
                $data[self::RESPONSE_STATUS] === self::RESPONSE_STATUS_CANCELLED
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
                    'url'      => $url.'/v1/payments/'.$paymentId,
                    'request'  => $this->json->serialize($sendData),
                    'response' => $this->json->serialize($response),
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'       => $url.'/v1/payments/'.$paymentId,
                    'request'   => $this->json->serialize($sendData),
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
