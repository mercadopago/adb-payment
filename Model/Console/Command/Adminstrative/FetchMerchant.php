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
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Message\ManagerInterface;
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
class FetchMerchant extends AbstractModel
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

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
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param TypeListInterface     $cacheTypeList
     * @param Pool                  $cacheFrontendPool
     * @param Logger                $logger
     * @param State                 $state
     * @param MercadoPagoConfig     $mercadopagoConfig
     * @param Config                $config
     * @param StoreManagerInterface $storeManager
     * @param Json                  $json
     * @param ZendClientFactory     $httpClientFactory
     * @param ManagerInterface      $messageManager
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        Logger $logger,
        State $state,
        MercadoPagoConfig $mercadopagoConfig,
        Config $config,
        StoreManagerInterface $storeManager,
        Json $json,
        ZendClientFactory $httpClientFactory,
        ManagerInterface $messageManager
    ) {
        parent::__construct(
            $logger
        );
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->state = $state;
        $this->mercadopagoConfig = $mercadopagoConfig;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->httpClientFactory = $httpClientFactory;
        $this->messageManager = $messageManager;
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
        $this->writeln('Init Fetch Merchant');

        if (!$storeIds) {
            $allStores = $this->storeManager->getStores();
            $countStores = count($allStores);

            foreach ($allStores as $stores) {
                $storeIdIsDefault = false;
                $storeId = (int) $stores->getId();
                $this->storeManager->setCurrentStore($stores);
                $webSiteId = (int) $stores->getWebsiteId();

                if ($webSiteId === 1 || $countStores === 1) {
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
        $mpSiteId = $this->mercadopagoConfig->getMpSiteId($storeId);
        $mpSiteId = strtolower((string) $mpSiteId);
        $mpWebSiteUrl = $this->mercadopagoConfig->getMpWebSiteBySiteId();
        $validate = $this->getValidateCredentials($storeId);
        $fullUrl = $mpWebSiteUrl.$mpSiteId.'/account/credentials';

        if (!$validate['success']) {
            if (isset($validate['error'])) {
                $this->messageManager->addNotice(
                    __('Please check store id %1 credentials, they are invalid so they were deleted.', $storeId)
                );
                $this->clearData(
                    $storeIdIsDefault,
                    $storeId,
                    $webSiteId
                );

                return $this;
            }

            if ($validate['response']['is_test']) {
                $this->messageManager->addComplexWarningMessage(
                    'addRedirectAccountMessage',
                    [
                        'store' => $storeId,
                        'url'   => $fullUrl,
                    ]
                );
                $errorMsg = __('Store ID: %1 Not allowed for production use', $storeId);
                $this->writeln('<error>'.$errorMsg.'</error>');
            }
        }

        $validatePublickey = $this->getValidatePublicKey($storeId);

        if (!$validatePublickey['success']) {
            if (isset($validatePublickey['error'])) {
                $this->messageManager->addNotice(
                    __('Please check store id %1 credentials, they are invalid so they were deleted.', $storeId)
                );

                $this->clearData(
                    $storeIdIsDefault,
                    $storeId,
                    $webSiteId
                );

                return $this;
            }
        }

        $usersMe = $this->getUsersMe($storeId);
        if ($usersMe['success']) {
            $response = $usersMe['response'];
            $registreData = [
                'id'      => $response['id'],
                'site_id' => $response['site_id'],
                'email'   => $response['email'],
                'name'    => $response['first_name'].' '.$response['last_name'],
            ];

            $registryConfig = $this->saveData(
                $registreData,
                $storeIdIsDefault,
                $storeId,
                $webSiteId
            );

            if ($registryConfig['success']) {
                $this->cacheTypeList->cleanType('config');

                return $this;
            }

            $errorMsg = __('There was an error saving: %1', $registryConfig['error']);
            $this->writeln('<error>'.$errorMsg.'</error>');

            $this->messageManager->addError($errorMsg);
        }

        $errorMsg = __('Error fetching information: %1', $usersMe['response']['message']);
        $this->writeln('<error>'.$errorMsg.'</error>');
        $this->messageManager->addNotice(__('Please check store id %1 credentials', $storeId));
    }

    /**
     * Get Validate Credentials.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getValidateCredentials(int $storeId = null): array
    {
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'/plugins-credentials-wrapper/credentials');
        $client->setConfig($clientConfigs);
        $client->setHeaders($clientHeaders);
        $client->setMethod(ZendClient::GET);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug(['plugins-credentials-wrapper/credential' => $result]);

            return [
                'success'    => isset($response['homologated']) ? $response['homologated'] : false,
                'response'   => $response,
            ];
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);

            return ['success' => false, 'error' =>  $exc->getMessage()];
        }
    }

    /**
     * Get Validate Public Key.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getValidatePublicKey(int $storeId = null): array
    {
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $publicKey = $this->mercadopagoConfig->getMerchantGatewayClientId($storeId);

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'/plugins-credentials-wrapper/credentials?public_key='.$publicKey);
        $client->setConfig($clientConfigs);
        $client->setMethod(ZendClient::GET);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug([
                'plugins-credentials-wrapper/credential?public_key=' => $result,
            ]);

            return [
                'success'    => isset($response['homologated']) ? $response['homologated'] : false,
                'response'   => $response,
            ];
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);

            return ['success' => false, 'error' =>  $exc->getMessage()];
        }
    }

    /**
     * Get Users Me.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getUsersMe(int $storeId = null): array
    {
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);

        $client = $this->httpClientFactory->create();
        $client->setUri($uri.'/users/me');
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
     * Save Data.
     *
     * @param array $data
     * @param bool  $storeIdIsDefault
     * @param int   $storeId
     * @param int   $webSiteId
     *
     * @return array
     */
    public function saveData(
        array $data,
        bool $storeIdIsDefault,
        int $storeId = 0,
        int $webSiteId = 0
    ): array {
        $environment = $this->mercadopagoConfig->getEnvironmentMode($storeId);
        $scope = ScopeInterface::SCOPE_WEBSITES;

        foreach ($data as $field => $value) {
            $pathPattern = 'payment/mercadopago_paymentmagento/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);

            if ($field === 'site_id') {
                $pathPattern = 'payment/mercadopago_paymentmagento/%s';
                $pathConfigId = sprintf($pathPattern, $field);
            }

            try {
                if ($storeIdIsDefault) {
                    $scope = 'default';
                    $webSiteId = 0;
                }
                $this->config->saveConfig(
                    $pathConfigId,
                    $value,
                    $scope,
                    $webSiteId
                );
            } catch (Exception $exc) {
                return ['success' => false, 'error' => $exc->getMessage()];
            }
        }

        return ['success' => true];
    }

    /**
     * Claar Data.
     *
     * @param bool $storeIdIsDefault
     * @param int  $storeId
     * @param int  $webSiteId
     *
     * @return array
     */
    public function clearData(
        bool $storeIdIsDefault,
        int $storeId = 0,
        int $webSiteId = 0
    ): array {
        $environment = $this->mercadopagoConfig->getEnvironmentMode($storeId);
        $scope = ScopeInterface::SCOPE_WEBSITES;
        
        $data = ['client_id' => null, 'client_secret' => null];

        foreach ($data as $field => $value) {
            $pathPattern = 'payment/mercadopago_paymentmagento/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);
            try {
                if ($storeIdIsDefault) {
                    $scope = 'default';
                    $webSiteId = 0;
                }
                $this->config->saveConfig(
                    $pathConfigId,
                    $value,
                    $scope,
                    $webSiteId
                );
            } catch (Exception $exc) {
                return ['success' => false, 'error' => $exc->getMessage()];
            }
        }

        return ['success' => true];
    }
}
