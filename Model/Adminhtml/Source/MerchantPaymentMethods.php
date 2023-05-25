<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Exception;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;

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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Logger            $logger
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     * @param ZendClientFactory $httpClientFactory
     * @param RequestInterface  $request
     */
    public function __construct(
        Logger $logger,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json,
        ZendClientFactory $httpClientFactory,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
        $this->request = $request;
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
            'label' => __('Do Not Delete'),
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
        $storeId = $this->request->getParam('store', 0);

        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs($storeId);
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
