<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\MPApi;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;

class PaymentGet {

    protected Config $config;

    protected ZendClientFactory $httpClientFactory;

    protected Json $json;

    protected Logger $logger;

    public function __construct(Config $config, ZendClientFactory $httpClientFactory, Json $json, Logger $logger)
    {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
        $this->logger = $logger;
    }

    public function get(string $paymentId, string $storeId)
    {
        $client = $this->httpClientFactory->create();

        $url = $this->config->getApiUrl();

        $client->setUri($url.'/v1/payments/'.$paymentId);
        $client->setConfig($this->config->getClientConfigs());
        $client->setHeaders($this->config->getClientHeaders($storeId));
        $client->setMethod(ZendClient::GET);

        $responseBody = $client->request()->getBody();

        $this->logger->debug([
            'url'      => $url.'/v1/payments/'.$paymentId,
            'method'   => ZendClient::GET,
            'response' => $responseBody
        ]);

        if ($client->request()->getStatus() > 299) {
            throw new \Exception("Invalid request to mp payments: " . $responseBody);
        }

        $data = $this->json->unserialize($responseBody);

        return $data;
    }
}
