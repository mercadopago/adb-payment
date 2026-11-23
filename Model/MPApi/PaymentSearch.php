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

/**
 * Looks up in Mercado Pago the payments that belong to an order, using the order increment id
 * that the plugin sends as `external_reference` when the payment or the preference is created.
 */
class PaymentSearch {

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

    /**
     * @param Config $config
     * @param Json   $json
     * @param Logger $logger
     */
    public function __construct(Config $config, Json $json, Logger $logger)
    {
        $this->config = $config;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Search the payments of an order by its external reference.
     *
     * @param string $externalReference Order increment id.
     * @param string $storeId
     *
     * @return array Payments returned by Mercado Pago (empty when the order has none).
     *
     * @throws \Exception When Mercado Pago cannot be queried.
     */
    public function searchByExternalReference(string $externalReference, string $storeId)
    {
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, $requester);
        $uri = '/v1/payments/search?' . http_build_query([
            'sort' => 'date_created',
            'criteria' => 'desc',
            'limit' => 30,
            'external_reference' => $externalReference
        ]);
        $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);

        try {
            $result = $client->get($uri, $clientHeaders);
            $responseBody = $result->getData();
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            $this->logger->debug(['Error in request to mp payments search' => $e->getMessage()]);
            throw new \Exception('Invalid request to mp payments search');
        }

        $this->logger->debug([
            'url'      => $baseUrl . $uri,
            'method'   => 'GET',
            'response' => $this->json->serialize($responseBody)
        ]);

        $responseBody = json_decode($this->json->serialize($responseBody), true);

        return isset($responseBody['results']) && is_array($responseBody['results'])
            ? $responseBody['results']
            : [];
    }
}
