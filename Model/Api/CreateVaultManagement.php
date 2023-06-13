<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Api;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;
use MercadoPago\AdbPayment\Api\CreateVaultManagementInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config as ConfigBase;

/**
 * Model for creating the Vault on Mercado Pago.
 */
class CreateVaultManagement implements CreateVaultManagementInterface
{
    /**
     * Result Code block name.
     */
    public const RESULT_CODE = 'result_code';

    /**
     * User Id block name.
     */
    public const USER_ID = 'mp_user_id';

    /**
     * Card Id block name.
     */
    public const CARD_ID = 'card_id';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * CreateVaultManagement constructor.
     *
     * @param Logger                  $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param ConfigInterface         $config
     * @param ConfigBase              $configBase
     * @param ZendClientFactory       $httpClientFactory
     * @param Json                    $json
     */
    public function __construct(
        Logger $logger,
        CartRepositoryInterface $quoteRepository,
        ConfigInterface $config,
        ConfigBase $configBase,
        ZendClientFactory $httpClientFactory,
        Json $json
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
        $this->configBase = $configBase;
        $this->httpClientFactory = $httpClientFactory;
        $this->json = $json;
    }

    /**
     * Create Vault Card Id.
     *
     * @param int   $cartId
     * @param array $vaultData
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     *
     * @return array
     */
    public function createVault(
        $cartId,
        $vaultData
    ) {
        $token = [];
        $mpCustomerId = null;

        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $storeId = $quote->getData(QuoteCartInterface::KEY_STORE_ID);

        $createUser = $this->createUser($storeId, $quote, $vaultData);
        $token['tokenize'] = [
            'error' => __('There was an error when creating the payment. Please try again later.'),
        ];

        if ($createUser[self::RESULT_CODE] === 0) {
            unset($token);
            $mpUserId = $this->findUser($storeId, $quote);
            $mpCustomerId = $mpUserId[self::USER_ID];
        }

        if ($createUser[self::RESULT_CODE] === 1) {
            unset($token);
            unset($vaultData['identificationNumber']);
            $mpCustomerId = $createUser[self::USER_ID];
        }

        $saveCcNumber = $this->saveCcNumber($storeId, $mpCustomerId, $vaultData);
        $token['tokenize'] = [
            self::USER_ID    => $mpCustomerId,
            self::CARD_ID    => $saveCcNumber[self::CARD_ID],
        ];

        return $token;
    }

    /**
     * Create User.
     *
     * @param int                     $storeId
     * @param CartRepositoryInterface $quote
     * @param array                   $vaultData
     *
     * @return array
     */
    public function createUser($storeId, $quote, $vaultData): array
    {
        $response[self::RESULT_CODE] = false;
        $data = [
            'email'          => $quote->getCustomerEmail(),
            'first_name'     => $quote->getCustomerFirstName(),
            'last_name'      => $quote->getCustomerLastName(),
            'identification' => [
                'type'   => $vaultData['identificationType'],
                'number' => $vaultData['identificationNumber'],
            ],
        ];

        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        $serializeResquest = $this->json->serialize($data);

        $url = $this->configBase->getApiUrl();
        $clientConfigs = $this->configBase->getClientConfigs();
        $clientHeaders = $this->configBase->getClientHeaders($storeId);

        try {
            $client->setUri($url.'/v1/customers');
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);
            $client->setRawData($serializeResquest, 'application/json');
            $client->setMethod(ZendClient::POST);
            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data['id'])) {
                $response = array_merge(
                    [
                        self::RESULT_CODE   => 1,
                        self::USER_ID       => $data['id'],
                    ],
                    $data
                );
            }
            $this->logger->debug(
                [
                    'storeId'  => $storeId,
                    'url'      => $url.'/v1/customers',
                    'request'  => $serializeResquest,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'storeId'   => $storeId,
                    'url'       => $url.'/v1/customers/1225574550-7EBfhWjd9vyLqH/cards',
                    'request'   => $vaultData,
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }

    /**
     * Find User.
     *
     * @param int                     $storeId
     * @param CartRepositoryInterface $quote
     *
     * @return array
     */
    public function findUser($storeId, $quote): array
    {
        $response[self::RESULT_CODE] = false;

        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();
        $search = ['email' => $quote->getCustomerEmail()];
        $url = $this->configBase->getApiUrl();
        $clientConfigs = $this->configBase->getClientConfigs();
        $clientHeaders = $this->configBase->getClientHeaders($storeId);

        try {
            $client->setUri($url.'/v1/customers/search?email='.$quote->getCustomerEmail());
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);
            $client->setMethod(ZendClient::GET);
            $responseBody = $client->request()->getBody();
            $this->logger->debug(
                [
                    'storeId'  => $storeId,
                    'url'      => $url.'/v1/customers/search',
                    'request'  => $search,
                    'response' => $responseBody,
                ]
            );
            $data = $this->json->unserialize($responseBody);

            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );

            if (isset($data['paging']['total'])) {
                if (isset($data['results'][0])) {
                    $response = array_merge(
                        [
                            self::RESULT_CODE   => 1,
                            self::USER_ID       => $data['results'][0]['id'],
                        ],
                        $data
                    );
                }
            }
            $this->logger->debug(
                [
                    'storeId'  => $storeId,
                    'url'      => $url.'/v1/customers/search',
                    'request'  => $search,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'storeId'   => $storeId,
                    'url'       => $url.'/v1/customers/search',
                    'request'   => $search,
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }

    /**
     * Save Cc Number.
     *
     * @param int    $storeId
     * @param string $mpCustomerId
     * @param array  $vaultData
     *
     * @return array
     */
    public function saveCcNumber($storeId, $mpCustomerId, $vaultData): array
    {
        /** @var ZendClient $client */
        $client = $this->httpClientFactory->create();

        $serializeResquest = $this->json->serialize($vaultData);

        $url = $this->configBase->getApiUrl();
        $clientConfigs = $this->configBase->getClientConfigs();
        $clientHeaders = $this->configBase->getClientHeaders($storeId);

        try {
            $client->setUri($url.'/v1/customers/'.$mpCustomerId.'/cards');
            $client->setConfig($clientConfigs);
            $client->setHeaders($clientHeaders);
            $client->setRawData($serializeResquest, 'application/json');
            $client->setMethod(ZendClient::POST);

            $responseBody = $client->request()->getBody();
            $data = $this->json->unserialize($responseBody);
            $response = array_merge(
                [
                    self::RESULT_CODE  => 0,
                ],
                $data
            );
            if (isset($data['id'])) {
                $response = array_merge(
                    [
                        self::RESULT_CODE   => 1,
                        self::CARD_ID       => $data['id'],
                    ],
                    $data
                );
            }
            $this->logger->debug(
                [
                    'storeId'  => $storeId,
                    'url'      => $url.'/v1/customers/'.$mpCustomerId.'/cards',
                    'request'  => $serializeResquest,
                    'response' => $responseBody,
                ]
            );
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'storeId'   => $storeId,
                    'url'       => $url.'/v1/customers/'.$mpCustomerId.'/cards',
                    'request'   => $vaultData,
                    'error'     => $exc->getMessage(),
                ]
            );
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new Exception('Invalid JSON was returned by the gateway');
        }

        return $response;
    }
}
