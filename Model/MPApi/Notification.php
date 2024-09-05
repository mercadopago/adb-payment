<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\MPApi;

use Exception;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\PP\Sdk\Common\Constants;

class Notification {

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

    public function get(string $notificationId, string $storeId)
    {
        try {
            $sdk = $this->config->getSdkInstance($storeId);
            $notificationInstance = $sdk->getNotificationInstance();
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            $this->logger->debug(['error' => $e->getMessage()]);
            throw new Exception($e->getMessage());
        }

        $data = null;
        try {
            $responseBody = $notificationInstance->read(array('id' => $notificationId));
            $data = $this->json->serialize($responseBody);
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            $this->logger->debug(['error' => $e->getMessage()]);
            throw new \Exception("Invalid request to asgard notification: " . $data);
        }

        $baseUrl = Constants::BASEURL_MP;
        $this->logger->debug(
            [
                'url'      => $baseUrl . $notificationInstance->getUris()['get'],
                'method'   => 'GET',
                'response' => $data,
            ]
        );

        return $this->json->unserialize($data);
    }
}
