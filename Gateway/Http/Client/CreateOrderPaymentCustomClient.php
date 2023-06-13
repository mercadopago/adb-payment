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
use MercadoPago\AdbPayment\Gateway\Request\CaptureAmountRequest;
use MercadoPago\AdbPayment\Gateway\Request\CcPaymentDataRequest;

/**
 * Communication with the Gateway to create a payment by custom (Card, Pix, Ticket, Pec).
 */
class CreateOrderPaymentCustomClient implements ClientInterface
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
     * External Order Id - Block name.
     */
    public const EXT_ORD_ID = 'EXT_ORD_ID';

    /**
     * External Status - Block name.
     */
    public const STATUS = 'status';

    /**
     * External Status Rejected - Block name.
     */
    public const STATUS_REJECTED = 'rejected';

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
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $request = $transferObject->getBody();
        $storeId = $request[self::STORE_ID];

        unset($request[self::STORE_ID]);
        unset($request[CcPaymentDataRequest::MP_PAYMENT_ID]);
        unset($request[CaptureAmountRequest::AMOUNT_PAID]);
        unset($request[CaptureAmountRequest::AMOUNT_TO_CAPTURE]);

        $serializeResquest = $this->json->serialize($request);
        $url = $this->config->getApiUrl();
        $clientConfigs = $this->config->getClientConfigs();
        $clientHeaders = $this->config->getClientHeaders($storeId);

        $responseBody = [];

        try {
            $client->setUri($url.'/v2/asgard/payments');
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);
            $client->setRawData($serializeResquest, 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);

            if ($data[self::STATUS] === self::STATUS_REJECTED) {
                $data['id'] = null;
            }

            $response = array_merge(
                [
                    self::RESULT_CODE  => isset($data['id']) ? 1 : 0,
                    self::EXT_ORD_ID   => isset($data['id']) ? $data['id'] : null,
                ],
                $data
            );
        } catch (InvalidArgumentException $exc) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        } finally {
            $this->logger->debug(
                [
                    'url'      => $url.'/v2/asgard/payments',
                    'header'   => $this->json->serialize($clientHeaders),
                    'request'  => $serializeResquest,
                    'response' => $responseBody,
                ]
            );
        }

        unset($clientHeaders['Authorization']);


        return $response;
    }
}
