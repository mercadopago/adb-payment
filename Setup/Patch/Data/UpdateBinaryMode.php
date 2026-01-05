<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use MercadoPago\PP\Sdk\HttpClient\HttpClient;
use MercadoPago\PP\Sdk\HttpClient\Requester\CurlRequester;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;
use InvalidArgumentException;
use Exception;

class UpdateBinaryMode implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $writerInterface;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * Class Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writerInterface
     * @param Logger $logger
     * @param Config $config
     * @param CollectionFactory $orderCollectionFactory
     * @param Json $json
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup, 
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writerInterface,
        Logger $logger,
        Config $config,
        CollectionFactory $orderCollectionFactory,
        Json $json
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->writerInterface = $writerInterface;
        $this->logger = $logger;
        $this->config = $config;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $path = 'payment/mercadopago_adbpayment_cc/can_initialize';

        $binaryMode = $this->getBinaryModeFromPayment();

        if ($binaryMode || $binaryMode == null) {
            $this->writerInterface->save(
                $path, 
                0, 
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                $scopeId = 0
            );
        } else {
            $this->writerInterface->save(
                $path, 
                1, 
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                $scopeId = 0
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get Binary Mode From Payment
     * 
     * @return mixed
     */
    protected function getBinaryModeFromPayment()
    {
        $binaryMode = null;
        $requester = new CurlRequester();
        $baseUrl = $this->config->getApiUrl();
        $client = new HttpClient($baseUrl, $requester);
        $clientHeaders = $this->config->getClientHeadersMpPluginsPhpSdk();
        $paymentId = $this->getPaymentIdFromLastOrder();
        $uri = '/v1/payments/'.$paymentId;

        try {
            $responseBody = $client->get($uri, $clientHeaders);
            $data = $responseBody->getData();

            if ($data && isset($data['binary_mode'])) {
                $binaryMode = $data['binary_mode'];
            }
        } catch (InvalidArgumentException $exc) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . '/v1/payments/' . $paymentId,
                    'error'     => $exc->getMessage(),
                ]
            );
        } catch (\Throwable $exc) {
            $this->logger->debug(
                [
                    'url'       => $baseUrl . $uri,
                    'error'     => $exc->getMessage(),
                ]
            );
        }

        return $binaryMode;
    }

    /**
     * Get Payment Id From Last Order
     * 
     * @return null|string
     */
    protected function getPaymentIdFromLastOrder(): ?string
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $select = $orderCollection->getSelect();

        $select->join(
            ["sop" => $orderCollection->getTable("sales_order_payment")],
            "main_table.entity_id = sop.entity_id",
            ['method', 'additional_information']
        );

        $orderCollection
            ->addFieldToFilter('sop.method', 'mercadopago_adbpayment_cc')
            ->setOrder('main_table.entity_id', 'DESC');

        $lastOrder = $orderCollection->getFirstItem();

        if ($lastOrder && $jsonData = $lastOrder->getData('additional_information')) {
            $additionalData = get_object_vars(json_decode($jsonData));
            
            if (isset($additionalData["mp_payment_id"])) {
                return (string)$additionalData["mp_payment_id"];
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}