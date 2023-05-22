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
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;

/**
 * Model for Command lines to capture Merchant details on Mercado Pago.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FetchMerchant extends AbstractModel
{
    /**
     * Message error invalid credential.
     */
    public const INVALID_CREDENTIAL = 'Your credentials are incorrect. Please double-check in your account your credentials are correct.';

    /**
     * Message error invalid credential to sendbox mode.
     */
    public const INVALID_SANDBOX_MODE = 'Your credentials are incorrect. Production credentials have been filled in and should be used in production mode. Please check the credentials again.';

    /**
     * Message error invalid credential to production mode.
     */
    public const INVALID_PRODUCTION_MODE = 'Your credentials are incorrect. Test credentials have been filled in and should be used in sandbox mode. Please check the credentials again.';

    /**
     * Enviroment production.
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * Enviroment sandbox.
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

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
            $defaultId = (int) $this->storeManager->getDefaultStoreView()->getId();

            foreach ($allStores as $stores) {
                $storeId = (int) $stores->getId();
                $this->storeManager->setCurrentStore($stores);
                $webSiteId = (int) $stores->getWebsiteId();
                $storeIdIsDefault = ($defaultId === $storeId) ? true : false;

                $this->writeln(
                    __(
                        'Default Store Id %1 - Set Data for store id %2 Web Site Id %3',
                        $storeIdIsDefault,
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
     * @return bool|void
     */
    public function fetchInfo(
        bool $storeIdIsDefault,
        int $storeId = 0,
        int $webSiteId = 0
    ) {
        $validateToken = $this->hasValidationStatusToken($storeId, $storeIdIsDefault, $webSiteId);
        if ($validateToken) {
            $this->clearData($storeIdIsDefault, $storeId, $webSiteId);
            $this->cacheTypeList->cleanType('config');
            return false;
        }

        $this->hasUserData($storeId, $storeIdIsDefault, $webSiteId);
    }

    /**
     * Has Validation Status Token.
     *
     * @param int  $storeId
     * @param bool $storeIdIsDefault
     * @param int  $webSiteId
     *
     * @return bool
     */
    public function hasValidationStatusToken(
        $storeId,
        $storeIdIsDefault,
        $webSiteId
    ) {
        $hasError = false;

        $token = $this->getAccessToken($storeId);
        $publicKey = $this->getPublicKey($storeId);
        $environment = $this->mercadopagoConfig->getEnvironmentMode($storeId);

        $messageError = $this->verifyCredentials($token, $publicKey);

        if(!isset($messageError)){
            if ($environment === self::ENVIRONMENT_PRODUCTION) {
                $messageError = $this->verifyProductionMode($token, $publicKey);
            }

            if ($environment === self::ENVIRONMENT_SANDBOX) {
                $messageError = $this->verifySandBoxMode($token, $publicKey);
            }
        }

        if(isset($messageError)){
            $this->messageManager->addWarning(__($messageError));
            $hasError = true;
        }

        return $hasError;
    }

    public function verifyCredentials($token, $publicKey)
    {
        if (isset($token['error']) || isset($publicKey['error'])) {
            return self::INVALID_CREDENTIAL;
        }

        if (!isset($token['success']) || !isset($publicKey['success'])) {
            return self::INVALID_CREDENTIAL;
        }

        if (!$token['response']['homologated']) {
            return self::INVALID_CREDENTIAL;
        }

        if ($token['response']['client_id'] !== $publicKey['response']['client_id']) {
            return self::INVALID_CREDENTIAL;
        }
    }

    public function verifyProductionMode($token, $publicKey)
    {
        if($token['response']['is_test']) {
            return self::INVALID_PRODUCTION_MODE;
        }

        if($publicKey['response']['is_test']) {
            return self::INVALID_PRODUCTION_MODE;
        }
    }

    public function verifySandBoxMode($token, $publicKey)
    {
        if(!$token['response']['is_test']) {
            return self::INVALID_SANDBOX_MODE;
        }

        if(!$publicKey['response']['is_test']) {
            return self::INVALID_SANDBOX_MODE;
        }
    }

    /**
     * Get Validate Public Key.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getPublicKey($storeId): array
    {
        $publicKey = $this->mercadopagoConfig->getMerchantGatewayClientId($storeId);
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();

        $client = $this->httpClientFactory->create();
        $client->setMethod(ZendClient::GET);
        $client->setConfig($clientConfigs);
        $client->setUri($uri.'/plugins-credentials-wrapper/credentials?public_key='.$publicKey);

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug([
                'plugins-credentials-wrapper/credential?public_key=' => $result,
            ]);

            return [
                'success'    => true,
                'response'   => $response,
            ];
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);

            return ['success' => false, 'error' =>  $exc->getMessage()];
        }
    }

    public function getAccessToken($storeId): array
    {
        $uri = $this->mercadopagoConfig->getApiUrl();
        $clientConfigs = $this->mercadopagoConfig->getClientConfigs();
        $clientHeaders = $this->mercadopagoConfig->getClientHeaders($storeId);

        $client = $this->httpClientFactory->create();
        $client->setMethod(ZendClient::GET);
        $client->setConfig($clientConfigs);
        $client->setHeaders($clientHeaders);
        $client->setUri($uri.'/plugins-credentials-wrapper/credentials');

        try {
            $result = $client->request()->getBody();
            $response = $this->json->unserialize($result);

            $this->logger->debug(['plugins-credentials-wrapper/credential' => $result]);

            return [
                'success'    => true,
                'response'   => $response,
            ];
        } catch (Exception $exc) {
            $this->logger->debug(['error' => $exc->getMessage()]);

            return ['success' => false, 'error' =>  $exc->getMessage()];
        }
    }

    /**
     * Has User Data.
     *
     * @param int  $storeId
     * @param bool $storeIdIsDefault
     * @param int  $webSiteId
     *
     * @return void
     */
    public function hasUserData(
        $storeId,
        $storeIdIsDefault,
        $webSiteId
    ) {
        $usersMe = $this->getUsersMe($storeId);

        if ($usersMe['success']) {
            $response = $usersMe['response'];
            $registreData = [
                'id'      => $response['id'],
                'site_id' => $response['site_id'],
                'email'   => $response['email'],
                'name'    => $response['first_name'].' '.$response['last_name'],
            ];

            $this->saveData($registreData, $storeIdIsDefault, $storeId, $webSiteId);

            $this->cacheTypeList->cleanType('config');
        }
    }

    /**
     * Get Users Me.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getUsersMe($storeId): array
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
            $pathPattern = 'payment/mercadopago_adbpayment/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);

            if ($field === 'site_id') {
                $pathPattern = 'payment/mercadopago_adbpayment/%s';
                $pathConfigId = sprintf($pathPattern, $field);
            }

            if ($storeIdIsDefault) {
                $this->config->saveConfig(
                    $pathConfigId,
                    $value,
                    'default',
                    0
                );
            }

            $this->config->saveConfig(
                $pathConfigId,
                $value,
                $scope,
                $webSiteId
            );
        }

        return ['success' => true];
    }

    /**
     * Clear Data.
     *
     * @param bool $storeIdIsDefault
     * @param int  $storeId
     * @param int  $webSiteId
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
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
            $pathPattern = 'payment/mercadopago_adbpayment/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);

            try {
                if ($storeIdIsDefault) {
                    $this->config->deleteConfig(
                        $pathConfigId,
                        'default',
                        0
                    );
                }

                $this->config->deleteConfig(
                    $pathConfigId,
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
