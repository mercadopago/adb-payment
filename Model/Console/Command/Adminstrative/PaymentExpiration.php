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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

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
     * @var DateTime
     */
    protected $date;

    /**
     * @param Logger            $logger
     * @param State             $state
     * @param MercadoPagoConfig $mercadopagoConfig
     * @param Json              $json
     * @param DateTime          $date
     */
    public function __construct(
        Logger $logger,
        State $state,
        MercadoPagoConfig $mercadopagoConfig,
        Json $json,
        DateTime $date
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->json = $json;
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
        $requester = new CurlRequester();
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);

        $clientHeaders = $this->mercadopagoConfig->getClientHeadersMpPluginsPhpSdk($storeId);
        $uri = '/checkout/preferences/'.$paymentId;

        $sendData = [
            'expires'               => true,
            'expiration_date_to'    => $this->date->gmtDate('Y-m-d'),
        ];

        try {
            $result = $client->put($uri, $clientHeaders, $this->json->serialize($sendData));
            $response = $result->getData();

            $this->logger->debug(
                [
                    'url'    => $baseUrl . $uri,
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
