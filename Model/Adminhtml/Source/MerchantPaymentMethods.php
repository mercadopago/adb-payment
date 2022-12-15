<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

use Exception;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;

/**
 * Payment method options available to the merchant on Mercado Pago.
 */
class MerchantPaymentMethods implements ArrayInterface
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

        $options[] = [
            'value' => null,
            'label' => __('Not Excluded'),
        ];

        $payments = $this->getAllPaymentMethods();

        if ($payments['success'] === true) {
            foreach ($payments['methods'] as $payment) {
                $options[] = [
                    'value' => $payment['id'],
                    'label' => __($payment['name']),
                ];
            }
        }

        return $options;
    }

    /**
     * Get All Payment Methods.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getAllPaymentMethods(int $storeId = 0): ?array
    {
        $response = ['success' => false];
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'/v1/payment_methods');
        $client->setConfig($clientConfigs);
        $client->setHeaders($clientHeaders);
        $client->setMethod(ZendClient::GET);

        try {
            $result = $client->request()->getBody();
            $data = $this->json->unserialize($result);
            $this->logger->debug($data);

            if (!isset($data['error'])) {
                $response = array_merge(
                    ['success' => true],
                    ['methods' => $data]
                );
            }
        } catch (Exception $e) {
            $this->logger->debug(['error' => $e->getMessage()]);
        }

        return $response;
    }
}
