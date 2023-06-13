<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Console\Command\Adminstrative;

use Exception;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;

/**
 * Model Command Line for payment expiration in Mercado Pago.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PaymentExpiration extends AbstractModel
{
    /**
     * @var State
     */
    protected $state;

    /**
     * @var MercadoPagoConfig
     */
    protected $mercadopagoConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @param Logger            $logger
     * @param State             $state
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     * @param ZendClientFactory $httpClientFactory
     * @param DateTime          $date
     */
    public function __construct(
        Logger $logger,
        State $state,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json,
        ZendClientFactory $httpClientFactory,
        DateTime $date
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
        $this->date = $date;
    }

    /**
     * Command Fetch.
     *
     * @param string   $paymentId
     * @param int|null $storeId
     *
     * @return void
     */
    public function expire($paymentId, $storeId = 0)
    {
        $this->writeln('Init Expire Payment');
        $expire = $this->setExpiration($paymentId, $storeId);

        if ($expire === true) {
            $this->writeln('The payment has expired');
        }

        if ($expire === false) {
            $this->writeln('The payment has not expired');
        }

        $this->writeln(__('Finished'));
    }

    /**
     * Get Validate Credentials.
     *
     * @param string   $paymentId
     * @param int|null $storeId
     *
     * @return bool
     */
    public function setExpiration($paymentId, $storeId): bool
    {
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);
        $uri = $uri.'/checkout/preferences/'.$paymentId;

        $sendData = [
            'expires'               => true,
            'expiration_date_to'    => $this->date->gmtDate('Y-m-d'),
        ];

        $client = $this->httpClientFactory->create();
        $client->setUri($uri);
        $client->setConfig($clientConfigs);
        $client->setHeaders($clientHeaders);
        $client->setRawData($this->json->serialize($sendData), 'application/json');
        $client->setMethod(ZendClient::PUT);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug(
                [
                    'url'    => $uri,
                    'result' => $this->json->serialize($response),
                ]
            );

            return true;
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);
        }

        return false;
    }
}
