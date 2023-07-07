<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Exception;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * Categories Options in Mercado Pago.
 */
class Category implements ArrayInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Logger            $logger
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     */
    public function __construct(
        Logger $logger,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json
    ) {
        $this->logger = $logger;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
    }

    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $categories = $this->getAllCategories();
        $options[] = [
            'value' => '',
            'label' => __('Please select a category'),
        ];
        foreach ($categories as $categorie) {
            $options[] = [
                'value' => $categorie['id'],
                'label' => __($categorie['description']),
            ];
        }

        return $options;
    }

    /**
     * Get New Token.
     *
     * @param int $storeId
     *
     * @return array
     */
    protected function getAllCategories(int $storeId = 0): array
    {
        $requester = new CurlRequester();
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);

        $uri = '/item_categories';
        $clientHeaders = $this->mercadopagoConfig->getClientHeadersNoAuthMpPluginsPhpSdk($storeId);

        try {
            $result = $client->get($uri, $clientHeaders);
            $response = (array)$result->getData();
            $this->logger->debug([$client]);
            $this->logger->debug((array)$response);

            return $response;
        } catch (Exception $e) {
            $this->logger->debug(['error' => $e->getMessage()]);

            return [];
        }
    }
}
