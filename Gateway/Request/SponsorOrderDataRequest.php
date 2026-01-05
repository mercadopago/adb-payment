<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;

/**
 * Gateway Requests for Sponsor Id in Order API format.
 * Converts sponsor_id to string as required by Order API.
 */
class SponsorOrderDataRequest implements BuilderInterface
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

    /**
     * @param Config              $config
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        Config $config,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->config = $config;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     * @return array
     */
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
            'sponsor_id' => $sponsorId,
        ];
    }
}


