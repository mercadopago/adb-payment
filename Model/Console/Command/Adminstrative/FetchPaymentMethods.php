<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Console\Command\Adminstrative;

use Exception;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\PaymentMagento\Model\Console\Command\AbstractModel;

/**
 * Model for Command lines to capture Merchant details on Mercado Pago.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FetchPaymentMethods extends AbstractModel
{
    /**
     * Mp Payment Mapping and Assignment in Magento.
     */
    public const MP_MAPPING_PAYMENTS = [
        'abitab'            => 'mercadopago_paymentmagento_abitab',
        'banamex'           => 'mercadopago_paymentmagento_banamex',
        'bancomer'          => 'mercadopago_paymentmagento_bancomer',
        'bolbradesco'       => 'mercadopago_paymentmagento_boleto',
        'efecty'            => 'mercadopago_paymentmagento_efecty',
        'oxxo'              => 'mercadopago_paymentmagento_oxxo',
        'pagoefectivo_atm'  => 'mercadopago_paymentmagento_pagoefectivo',
        'pagofacil'         => 'mercadopago_paymentmagento_pagofacil',
        'paycash'           => 'mercadopago_paymentmagento_paycash',
        'pec'               => 'mercadopago_paymentmagento_pec',
        'pix'               => 'mercadopago_paymentmagento_pix',
        'pse'               => 'mercadopago_paymentmagento_pse',
        'rapipago'          => 'mercadopago_paymentmagento_rapipago',
        'redpagos'          => 'mercadopago_paymentmagento_redpagos',
        'serfin'            => 'mercadopago_paymentmagento_serfin',
        'webpay'            => 'mercadopago_paymentmagento_webpay',
        'visa'              => 'mercadopago_paymentmagento_cc',
        'master'            => 'mercadopago_paymentmagento_cc',
        'elo'               => 'mercadopago_paymentmagento_cc',
        'amex'              => 'mercadopago_paymentmagento_cc',
        'debmaster'         => 'mercadopago_paymentmagento_cc',
        'hipercard'         => 'mercadopago_paymentmagento_cc',
        'debvisa'           => 'mercadopago_paymentmagento_cc',
        'debelo'            => 'mercadopago_paymentmagento_cc',
        'cabal'             => 'mercadopago_paymentmagento_cc',
        'debcabal'          => 'mercadopago_paymentmagento_cc',
        'cmr'               => 'mercadopago_paymentmagento_cc',
        'cencosud'          => 'mercadopago_paymentmagento_cc',
        'diners'            => 'mercadopago_paymentmagento_cc',
        'tarshop'           => 'mercadopago_paymentmagento_cc',
        'argencard'         => 'mercadopago_paymentmagento_cc',
        'naranja'           => 'mercadopago_paymentmagento_cc',
        'maestro'           => 'mercadopago_paymentmagento_cc',
        'tengo'             => 'mercadopago_paymentmagento_cc',
        'sodexo'            => 'mercadopago_paymentmagento_cc',
        'carnet'            => 'mercadopago_paymentmagento_cc',
        'toka'              => 'mercadopago_paymentmagento_cc',
        'mercadopagocard'   => 'mercadopago_paymentmagento_cc',
        'edenred'           => 'mercadopago_paymentmagento_cc',
        'redcompra'         => 'mercadopago_paymentmagento_cc',
        'magna'             => 'mercadopago_paymentmagento_cc',
        'presto'            => 'mercadopago_paymentmagento_cc',
        'codensa'           => 'mercadopago_paymentmagento_cc',
        'lider'             => 'mercadopago_paymentmagento_cc',
        'creditel'          => 'mercadopago_paymentmagento_cc',
        'oca'               => 'mercadopago_paymentmagento_cc',
        'mediotest'         => 'mercadopago_paymentmagento_cc',
    ];

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
     * @param Logger                $logger
     * @param State                 $state
     * @param MercadoPagoConfig     $mercadopagoConfig
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param ZendClientFactory     $httpClientFactory
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Logger $logger,
        State $state,
        MercadoPagoConfig $mercadopagoConfig,
        Config $config,
        StoreManagerInterface $storeManager,
        Json $json,
        ZendClientFactory $httpClientFactory
    ) {
        parent::__construct(
            $logger
        );
        $this->state = $state;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * Command Fetch.
     *
     * @param int|null $storeId
     *
     * @return void
     */
    public function fetch($storeId = null)
    {
        $storeIds = $storeId ?: null;
        $this->writeln('Init Fetch Allowed Payment Methods');

        if (!$storeIds) {
            $allStores = $this->storeManager->getStores();
            $countStores = count($allStores);

            foreach ($allStores as $stores) {
                $storeIdIsDefault = false;
                $storeId = (int) $stores->getId();
                $this->storeManager->setCurrentStore($stores);
                $webSiteId = (int) $stores->getWebsiteId();

                if ($countStores === 1) {
                    $storeIdIsDefault = true;
                }

                $this->writeln(
                    __(
                        'Default Store %1 - Set Data for store id %2 Web Site Id %3',
                        (bool) $storeIdIsDefault,
                        $storeId,
                        $webSiteId
                    )
                );
                $this->fetchInfo($storeIdIsDefault, $storeId, $webSiteId);
            }
        }
        $this->writeln(__('Finished'));
    }

    /**
     * Create Data Merchant.
     *
     * @param bool $storeIdIsDefault
     * @param int  $storeId
     * @param int  $webSiteId
     *
     * @return void
     */
    public function fetchInfo(
        bool $storeIdIsDefault,
        int $storeId = 0,
        int $webSiteId = 0
    ) {
        $paymentMethods = $this->getPaymentMethods($storeId);
        $mapping = self::MP_MAPPING_PAYMENTS;
        $mpPaymentAllowed = [];

        if ($paymentMethods['success']) {
            $response = $paymentMethods['response'];

            foreach ($response as $payment) {
                $mpPayment = $payment['id'];

                if (array_key_exists($mpPayment, $mapping)) {
                    $mpPaymentAllowed[] = $mpPayment;
                }
            }

            $this->disablePaymentMethod(
                $mpPaymentAllowed,
                $storeIdIsDefault,
                $webSiteId
            );

            return $this;
        }

        $errorMsg = __('Fetch error: %1', $paymentMethods['response']['message']);
        $this->writeln('<error>'.$errorMsg.'</error>');
    }

    /**
     * Get Payment Methods.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getPaymentMethods(int $storeId = null): array
    {
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
            $response = $this->json->unserialize($result);

            $this->logger->debug(['fetch_result' => $result]);

            return [
                'success'    => isset($response['message']) ? false : true,
                'response'   => $response,
            ];
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);

            return ['success' => false, 'error' =>  $exc->getMessage()];
        }
    }

    /**
     * Disabled Payment Method.
     *
     * @param array $mpPaymentAllowed
     * @param bool  $storeIdIsDefault
     * @param int   $webSiteId
     *
     * @return void
     */
    public function disablePaymentMethod(
        array $mpPaymentAllowed,
        bool $storeIdIsDefault,
        int $webSiteId = 0
    ): void {
        $mapping = self::MP_MAPPING_PAYMENTS;

        foreach ($mapping as $mpMappingMethod => $mageMappingMethod) {
            if ($mageMappingMethod !== 'mercadopago_paymentmagento_cc') {
                if (!in_array($mpMappingMethod, $mpPaymentAllowed)) {
                    $this->writeln('<error> Disable '.$mageMappingMethod.' </error>');
                    $this->saveConfigPaymentMethod(
                        $mageMappingMethod,
                        $storeIdIsDefault,
                        $webSiteId
                    );
                }
            }
        }
    }

    /**
     * Save Config Payment Method.
     *
     * @param string $payment
     * @param bool   $storeIdIsDefault
     * @param int    $webSiteId
     *
     * @return array
     */
    public function saveConfigPaymentMethod(
        string $payment,
        bool $storeIdIsDefault,
        int $webSiteId = 0
    ): array {
        $scope = ScopeInterface::SCOPE_WEBSITES;

        $pathPattern = 'payment/%s/active';
        $pathConfigId = sprintf($pathPattern, $payment);

        try {
            if ($storeIdIsDefault) {
                $scope = 'default';
                $webSiteId = 0;
            }
            $this->config->saveConfig(
                $pathConfigId,
                0,
                $scope,
                $webSiteId
            );
        } catch (Exception $exc) {
            return ['success' => false, 'error' => $exc->getMessage()];
        }

        return ['success' => true];
    }
}
