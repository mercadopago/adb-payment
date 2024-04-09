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
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\AdbPayment\Model\Console\Command\AbstractModel;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

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
        $this->messageManager = $messageManager;
    }

    /**
     * Command Fetch.
     *
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return void
     */
    public function fetch($storeId = null, $scope = ScopeInterface::SCOPE_WEBSITES)
    {
        $this->writeln('Init Fetch Merchant');

        if ($scope === ScopeInterface::SCOPE_WEBSITES) {
            $allWebsites = $this->storeManager->getWebsites();
            foreach ($allWebsites as $websites) {
                $websiteId = (int) $websites->getId();
                $this->fetchInfoWebsite($websiteId, $scope);
            }
        }

        $allStores = $this->storeManager->getStores();
        foreach ($allStores as $stores) {
            $storeId = (int) $stores->getId();
            $this->fetchInfoStore($storeId, ScopeInterface::SCOPE_STORES);
        }

        $this->writeln(__('Finished'));
    }

    /**
    * Fetch info by store.
    *
    * @param int  $storeId
    * @param string  $scope
    *
    * @return bool|void
    */
    private function fetchInfoWebsite($website, $scope)
    {
        $website = $this->storeManager->getWebsite($website);
        $webSiteId = (int) $website->getId();
        $defaultId = (int) $this->storeManager->getDefaultStoreView() ->getId();
        $storeIdIsDefault = ($defaultId === $webSiteId) ? true : false;

        $this->writeln(
            __(
                'Default Store Id %1 - Set Data for Web Site Id %2',
                $storeIdIsDefault,
                $webSiteId
            )
        );

        $this->fetchInfo($storeIdIsDefault, $scope, $webSiteId);
    }

    /**
    * Fetch info by store.
    *
    * @param int  $storeId
    * @param string  $scope
    *
    * @return bool|void
    */
    private function fetchInfoStore($storeId, $scope)
    {
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store);
        $webSiteId = (int) $store->getWebsiteId();
        $defaultId = (int) $this->storeManager->getDefaultStoreView()   ->getId();
        $storeIdIsDefault = ($defaultId === $storeId) ? true : false;

        $this->writeln(
            __(
                'Default Store Id %1 - Set Data for store id %2 Web Site Id %3',
                $storeIdIsDefault,
                $storeId,
                $webSiteId
            )
        );

        $this->fetchInfo($storeIdIsDefault, $scope, $storeId);
    }

    /**
     * Create Data Merchant.
     *
     * @param bool $storeIdIsDefault
     * @param string  $scope
     * @param int  $scopeId StoreId or WebsiteId
     *
     * @return bool|void
     */
    public function fetchInfo(
        bool $storeIdIsDefault,
        string $scope = ScopeInterface::SCOPE_WEBSITES,
        int $scopeId = 0
    ) {
        $validateToken = $this->hasValidationStatusToken($scopeId, $scope);
        if ($validateToken) {
            $this->clearData($storeIdIsDefault, $scope, $scopeId);
            $this->cacheTypeList->cleanType('config');
            return false;
        }

        $this->hasUserData($storeIdIsDefault, $scope, $scopeId);
    }

    /**
     * Has Validation Status Token.
     *
     * @param int  $storeId
     * @param string  $scope
     *
     * @return bool
     */
    public function hasValidationStatusToken(
        $storeId,
        $scope
    ) {
        $hasError = false;

        $token = $this->getAccessToken($storeId, $scope);
        $publicKey = $this->getPublicKey($storeId, $scope);
        $environment = $this->mercadopagoConfig->getEnvironmentMode($storeId, $scope);

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

        if (!isset($token['response']['homologated'])) {
            return self::INVALID_CREDENTIAL;
        }

        if (isset($token['response']['client_id'], $publicKey['response']['client_id'])) {
            if ($token['response']['client_id'] !== $publicKey['response']['client_id']) {
                return self::INVALID_CREDENTIAL;
            }
        } else {
            return self::INVALID_CREDENTIAL;
        }
    }

    public function verifyProductionMode($token, $publicKey)
    {
        if(isset($token['response']['is_test'], $publicKey['response']['is_test'])) {
            if($token['response']['is_test']) {
                return self::INVALID_PRODUCTION_MODE;
            }

            if($publicKey['response']['is_test']) {
                return self::INVALID_PRODUCTION_MODE;
            }
        } else {
            return self::INVALID_CREDENTIAL;
        }
    }

    public function verifySandBoxMode($token, $publicKey)
    {
        if(isset($token['response']['is_test'], $publicKey['response']['is_test'])) {
            if(!$token['response']['is_test']) {
                return self::INVALID_SANDBOX_MODE;
            }

            if(!$publicKey['response']['is_test']) {
                return self::INVALID_SANDBOX_MODE;
            }
        } else {
            return self::INVALID_CREDENTIAL;
        }
    }

    /**
     * Get Validate Public Key.
     *
     * @param int $storeId
     * @param string $scope
     *
     * @return array
     */
    public function getPublicKey($storeId, $scope): array
    {
        $publicKey = $this->mercadopagoConfig->getMerchantGatewayClientId($storeId, $scope);
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $requester = new CurlRequester();
        $client  = new HttpClient($baseUrl, $requester);
        $uri = '/plugins-credentials-wrapper/credentials?public_key='.$publicKey;

        try {
            $result = $client->get($uri);
            $response = $result->getData();

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

    public function getAccessToken($storeId, $scope): array
    {
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $requester = new CurlRequester();
        $client  = new HttpClient($baseUrl, $requester);
        $clientHeaders = $this->mercadopagoConfig->getClientHeadersMpPluginsPhpSdk($storeId, $scope);
        $uri = '/plugins-credentials-wrapper/credentials';

        try {
            $result = $client->get($uri, $clientHeaders);
            $response = $result->getData();


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
     * @param bool $storeIdIsDefault
     * @param string  $scope
     * @param int  $scopeId StoreId or WebsiteId
     *
     * @return void
     */
    public function hasUserData(
        $storeIdIsDefault,
        $scope,
        $scopeId
    ) {
        $usersMe = $this->getUsersMe($scopeId, $scope);

        if ($usersMe['success']) {
            $response = $usersMe['response'];
            $registreData = [
                'id'      => $response['id'],
                'site_id' => $response['site_id'],
                'email'   => $response['email'],
                'name'    => $response['first_name'].' '.$response['last_name'],
                'version' => $this->mercadopagoConfig->getModuleVersion(),
            ];

            $this->saveData($registreData, $storeIdIsDefault, $scope, $scopeId);

            $this->cacheTypeList->cleanType('config');
        }
    }

    /**
     * Get Users Me.
     *
     * @param int $storeId
     * @param string $scope
     *
     * @return array
     */
    public function getUsersMe($storeId, $scope = ScopeInterface::SCOPE_STORES): array
    {
        $baseUrl = $this->mercadopagoConfig->getApiUrl();
        $requester = new CurlRequester();
        $client  = new HttpClient($baseUrl, $requester);
        $clientHeaders = $this->mercadopagoConfig->getClientHeadersMpPluginsPhpSdk($storeId, $scope);
        $uri = '/users/me';

        try {
            $result = $client->get($uri, $clientHeaders);
            $response = $result->getData();

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
     * @param string $scope
     * @param int   $scopeId StoreId or WebsiteId
     *
     * @return array
     */
    public function saveData(
        array $data,
        bool $storeIdIsDefault,
        string $scope = ScopeInterface::SCOPE_WEBSITES,
        int $scopeId = 0

    ): array {
        $environment = $this->mercadopagoConfig->getEnvironmentMode($scopeId, $scope);

        foreach ($data as $field => $value) {
            $pathPattern = 'payment/mercadopago_adbpayment/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);

            if ($field === 'site_id' || $field === 'version') {
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
                $scopeId
            );
        }

        return ['success' => true];
    }

    /**
     * Clear Data.
     *
     * @param bool $storeIdIsDefault
     * @param string $scope
     * @param int  $scopeId StoreId or WebsiteId
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function clearData(
        bool $storeIdIsDefault,
        $scope = ScopeInterface::SCOPE_WEBSITES,
        int $scopeId = 0
    ): array {
        $environment = $this->mercadopagoConfig->getEnvironmentMode($scopeId, $scope);

        $data = ['client_id' => null, 'client_secret' => null];

        foreach ($data as $field => $value) {
            $pathPattern = 'payment/mercadopago_adbpayment/%s_%s';
            $pathConfigId = sprintf($pathPattern, $field, $environment);

            if ($field === 'site_id' || $field === 'version') {
                $pathPattern = 'payment/mercadopago_adbpayment/%s';
                $pathConfigId = sprintf($pathPattern, $field);
            }

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
                    $scopeId
                );
            } catch (Exception $exc) {
                return ['success' => false, 'error' => $exc->getMessage()];
            }
        }

        return ['success' => true];
    }
}
