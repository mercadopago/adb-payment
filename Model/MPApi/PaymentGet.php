<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\MPApi;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

class PaymentGet {

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Config $config, Json $json, Logger $logger)
    {
        $this->config = $config;
        $this->json = $json;
        $this->logger = $logger;
    }

    public function get(string $paymentId, string $storeId)
    {
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);

        $uri = '/v1/payments/'.$paymentId;
        $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);

        $responseBody = null;
        try {
            $result = $client->get($uri, $clientHeaders);
            $responseBody = $result->getData();
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception("Invalid request to mp payments: " . $responseBody);
        }

        $this->logger->debug([
            'url'      => $baseUrl.$uri,
            'method'   => 'GET',
            'response' => $this->json->serialize($responseBody)
        ]);

        return $responseBody;
    }
}
