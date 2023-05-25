<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Exception;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;

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
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @param Logger            $logger
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     * @param ZendClientFactory $httpClientFactory
     */
    public function __construct(
        Logger $logger,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        $this->logger = $logger;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
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
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'/item_categories');
        $client->setConfig($clientConfigs);
        $client->setHeaders($clientHeaders);
        $client->setMethod(ZendClient::GET);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);
            $this->logger->debug([$client]);
            $this->logger->debug($response);

            return $response;
        } catch (Exception $e) {
            $this->logger->debug(['error' => $e->getMessage()]);

            return [];
        }
    }
}
