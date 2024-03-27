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
use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Logger            $logger
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     * @param RequestInterface  $request
     */
    public function __construct(
        Logger $logger,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
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

        if ($payments['success'] === true && isset($payments['methods'])) {
            foreach ($payments['methods'] as $payment) {
                if (isset($payment['id']) && isset($payment['name'])) {
                    $options[] = [
                        'value' => $payment['id'],
                        'label' => __($payment['name']),
                    ];
                }
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

        $requester = new CurlRequester();
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);

        $uri = '/v1/payment_methods';
        $clientHeaders = $this->mercadopagoConfig->getClientHeadersMpPluginsPhpSdk($storeId);

        try {
            $result = $client->get($uri, $clientHeaders);
            $data = $result->getData();
            $this->logger->debug((array)$data);

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
