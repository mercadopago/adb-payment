<?php

namespace MercadoPago\AdbPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;

class SponsorDataRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * Test email domain - used for test users
     */
    private const TESTE_EMAIL_DOMAIN = '@testuser.com';

    public function __construct(
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        /** @var OrderAdapterFactory $orderAdapter */
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $paymentDO->getPayment()->getOrder()]
        );

        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $mpSiteId = $this->config->getMpSiteId($storeId);
        $sponsorId = $this->config->getMpSponsorId($mpSiteId);
        $billingAddress = $orderAdapter->getBillingAddress();

        if (substr(strtolower($billingAddress->getEmail()), -strlen(self::TESTE_EMAIL_DOMAIN)) === self::TESTE_EMAIL_DOMAIN) {
            $sponsorId = null;
        }
        
        return [
            'sponsor_id' => $sponsorId !== null ? (int) $sponsorId : null
        ];
    }
}
