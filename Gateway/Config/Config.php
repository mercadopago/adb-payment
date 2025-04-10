<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Config;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use MercadoPago\PP\Sdk\Sdk;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;

/**
 * Gateway setting for the payment method.
 */
class Config extends PaymentConfig
{
    /**
     * Method Name.
     */
    public const METHOD = 'mercadopago_adbpayment';

    /**
     * Product Id.
     */
    public const PRODUCT_ID = 'BC32CANTRPP001U8NHO0';

    /**
     * Plataform Id.
     */
    public const PLATAFORM_ID = 'BP1EF6QIC4P001KBGQ10';

    /**
     * Endpoint API.
     */
    public const ENDPOINT_API = 'https://api.mercadopago.com';

    /**
     * Enviroment Production.
     */
    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * Enviroment Sandbox.
     */
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * Client.
     */
    public const CLIENT = 'AdbPayment';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ResourceInterface
     */
    protected $resourceModule;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceInterface        $resourceModule
     * @param ScopeConfigInterface     $scopeConfig
     * @param Json                     $json
     * @param Logger                   $logger
     * @param string                   $methodCode
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ResourceInterface $resourceModule,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Json $json,
        $methodCode = self::METHOD
    ) {
        parent::__construct($scopeConfig, $methodCode);
        $this->productMetadata = $productMetadata;
        $this->resourceModule = $resourceModule;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * Get SDK instance
     *
     * @param int|null $storeId
     *
     * @return Sdk
     */
    public function getSdkInstance($storeId = null): Sdk {
        $oauth = $this->getMerchantGatewayClientSecret($storeId);
        $integratorId = $this->getMerchantGatewayIntegratorId($storeId);
        if(is_null($integratorId)) $integratorId = '';

        try {
            $sdk = new Sdk($oauth, self::PLATAFORM_ID, self::PRODUCT_ID, $integratorId);
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception($e->getMessage());
        }
        return $sdk;
    }

    /**
     * Formant Price.
     *
     * @param string|int|float $amount
     * @param int|null         $storeId
     *
     * @return float
     */
    public function formatPrice($amount, $storeId = null): float
    {
        if ($this->getMpSiteId($storeId) === 'MCO') {
            return round((float) $amount, 0);
        }

        if ($this->getMpSiteId($storeId) === 'MLC') {
            return round((float) $amount, 0);
        }

        return round((float) $amount, 2);
    }

    /**
     * Gets the API endpoint URL.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return self::ENDPOINT_API;
    }

    /**
     * Gets the Environment Mode.
     *
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getEnvironmentMode($storeId = null, $scope = ScopeInterface::SCOPE_STORES): ?string
    {
        $environment = $this->getAddtionalValue('environment', $storeId, $scope);

        if ($environment === 'sandbox') {
            return self::ENVIRONMENT_SANDBOX;
        }

        return self::ENVIRONMENT_PRODUCTION;
    }

    /**
     * Gets the Merchant Gateway Client Id.
     *
     * @param int|null $storeId
     * @param string $scope
     *
     * @return string
     */
    public function getMerchantGatewayClientId($storeId = null, $scope = ScopeInterface::SCOPE_STORES): ?string
    {
        $clientId = $this->getAddtionalValue('client_id_production', $storeId, $scope);

        $environment = $this->getEnvironmentMode($storeId, $scope);

        if ($environment === 'sandbox') {
            $clientId = $this->getAddtionalValue('client_id_sandbox', $storeId, $scope);
        }

        return $clientId;
    }

    /**
     * Gets the Merchant Gateway Client Secret.
     *
     * @param int|null $storeId
     * @param string $scope
     *
     * @return string|null
     */
    public function getMerchantGatewayClientSecret($storeId = null, $scope = ScopeInterface::SCOPE_STORES): ?string
    {
        $clientSecret = $this->getAddtionalValue('client_secret_production', $storeId, $scope);

        $environment = $this->getEnvironmentMode($storeId, $scope);

        if ($environment === 'sandbox') {
            $clientSecret = $this->getAddtionalValue('client_secret_sandbox', $storeId, $scope);
        }

        return $clientSecret;
    }

    /**
     * Gets the Merchant Gateway OAuth.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMerchantGatewayOauth($storeId = null): ?string
    {
        $oauth = $this->getAddtionalValue('access_token_production', $storeId);

        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            $oauth = $this->getAddtionalValue('access_token_sandbox', $storeId);
        }

        return $oauth;
    }

    /**
     * Gets the Merchant Gateway Integrator Id.
     *
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getMerchantGatewayIntegratorId($storeId = null, $scope = ScopeInterface::SCOPE_STORES): ?string
    {
        return $this->getAddtionalValue('integrator_id', $storeId, $scope);
    }

    /**
     * Get Client Headers According to Mp Plugins PHP SDK format.
     *
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return array
     */
    public function getClientHeadersMpPluginsPhpSdk($storeId = null, $scope = ScopeInterface::SCOPE_STORES): array
    {
        $oauth = $this->getMerchantGatewayClientSecret($storeId, $scope);

        $headers = $this->getClientHeadersNoAuthMpPluginsPhpSdk($storeId, $scope);

        $headers[] = 'Authorization: Bearer ' . $oauth;

        return $headers;
    }

    /**
     * Get Client Headers According to Mp Plugins PHP SDK format.
     *
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return array
     */
    public function getClientHeadersNoAuthMpPluginsPhpSdk($storeId = null, $scope = ScopeInterface::SCOPE_STORES): array
    {
        $integratorId = $this->getMerchantGatewayIntegratorId($storeId, $scope);

        return [
            'x-product-id: '.self::PRODUCT_ID,
            'x-platform-id: '.self::PLATAFORM_ID,
            'x-integrator-id: '.$integratorId,
        ];
    }

    /**
     * Get Client Configs.
     *
     * @return array
     */
    public function getClientConfigs(): array
    {
        return [
            'maxredirects' => 0,
            'timeout'      => 45,
            'useragent'    => 'Magento 2',
        ];
    }

    /**
     * Get Statement Descriptor.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getStatementDescriptor($storeId = null): ?string
    {
        return $this->getAddtionalValue('statement_descriptor', $storeId);
    }

    /**
     * Get Restrict Payment on MP Site Id.
     *
     * @param int|null $storeId
     *
     * @return array|null
     */
    public function getRestrictPaymentOnMpSiteId($storeId = null): ?array
    {
        $restrict = $this->getAddtionalValue('restrict_payment_on_mp_site_id', $storeId);

        return $this->json->unserialize($restrict);
    }

    /**
     * Get Magento Version.
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Get Module Version.
     *
     * @return string|null
     */
    public function getModuleVersion(): ?string
    {
        return $this->resourceModule->getDbVersion('MercadoPago_AdbPayment');
    }

    /**
     * Is Test Mode.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isTestMode($storeId = null): bool
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return true;
        }

        return false;
    }

    /**
     * Get Mp Site Id.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMpSiteId($storeId = null): ?string
    {
        return $this->getAddtionalValue('site_id', $storeId);
    }

    /**
     * Get Mercado Pago Sponsor Id.
     *
     * @param string $siteId
     *
     * @return int|null
     */
    public function getMpSponsorId(string $siteId): ?int
    {
        $sponsorIds = [
            'MCO' => '222570694',
            'MLA' => '222568987',
            'MLB' => '222567845',
            'MLC' => '222570571',
            'MLM' => '222568246',
            'MLU' => '247030424',
            'MPE' => '222568315',
            'MLV' => '222569730',
        ];

        if (!isset($sponsorIds[$siteId])) {
            return null;
        }

        return (int) $sponsorIds[$siteId];
    }

    /**
     * Get Mercado Pago Web Site by Site Id.
     *
     * @param string|null $siteId
     *
     * @return string
     */
    public function getMpWebSiteBySiteId(string $siteId = null): string
    {
        $webSite = [
            'MCO' => 'https://www.mercadopago.com.co/',
            'MLA' => 'https://www.mercadopago.com.ar/',
            'MLB' => 'https://www.mercadopago.com.br/',
            'MLC' => 'https://www.mercadopago.cl/',
            'MLM' => 'https://www.mercadopago.com.mx/',
            'MLU' => 'https://www.mercadopago.com.uy/',
            'MPE' => 'https://www.mercadopago.com.pe/',
        ];

        if (!isset($siteId)) {
            return 'https://www.mercadopago.com/';
        }

        return $webSite[$siteId];
    }

    /**
     * Get Mp Category.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getMpCategory($storeId = null): ?string
    {
        return $this->getAddtionalValue('category', $storeId);
    }

    /**
     * Get Rewrite notification url.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getRewriteNotificationUrl($storeId = null): ?string
    {
        $environment = $this->getEnvironmentMode($storeId);

        if ($environment === 'sandbox') {
            return $this->getAddtionalValue('sandbox_rewrite_notification_url', $storeId);
        }

        return null;
    }

    /**
     * Is Apply Refund.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isApplyRefund($storeId = null): ?bool
    {
        return $this->getAddtionalValue('receive_refund', $storeId);
    }

    /**
     * Value For Field Address.
     *
     * @param OrderAdapterFactory $adress
     * @param string              $field
     *
     * @return string|null
     */
    public function getValueForAddress($adress, $field): ?string
    {
        $value = (int) $this->getAddtionalValue($field);

        switch ($value) {
            case 0:
                return $adress->getStreetLine1();
            case 1:
                return $adress->getStreetLine2();
            case 2:
                return $adress->getStreetLine3() ?: '';
            case 3:
                return $adress->getStreetLine4() ?: '';
            default:
                return '';
        }
    }

    /**
     * Gets the AddtionalValues.
     *
     * @param string   $field
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getAddtionalValue($field, $storeId = null, $scope = ScopeInterface::SCOPE_STORES): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            $scope,
            $storeId
        );
    }

    /**
     * Get Mp Payment Methods.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getMpPaymentMethods(int $storeId = null): array
    {
        $requester = new CurlRequester();
        $baseUrl = $this->getApiUrl();
        $client  = new HttpClient($baseUrl, $requester);

        $uri = '/v1/bifrost/payment-methods';
        $clientHeaders = $this->getClientHeadersMpPluginsPhpSdk($storeId);

        try {
            $result = $client->get($uri, $clientHeaders);
            $response = $result->getData();

            if($result->getStatus() > 299) {
                $this->logger->debug(
                    [
                        'url'       => $baseUrl . $uri,
                        'status'    => $result->getStatus(),
                        'response'  => $response
                    ]
                );
            }
            return [
                'success'    => isset($response['message']) ? false : true,
                'response'   => $response,
            ];
        } catch (\Exception $e) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . $uri,
                    'error'     => $e->getMessage(),
                ]
            );
            return ['success' => false, 'error' =>  $e->getMessage()];
        }
    }
}
