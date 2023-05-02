<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * Gateway Requests for Checkout Pro Notification Url.
 */
class CheckoutProNotificationUrlDataRequest implements BuilderInterface
{
    /**
     * Back Urls block name.
     */
    public const NOTIFICATION_URL = 'notification_url';

    /**
     * Path to Notification - url magento.
     */
    public const PATH_TO_NOTIFICATION = 'mp/notification/checkoutpro';

    /**
     * @var UrlInterface
     */
    protected $frontendUrlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param UrlInterface $frontendUrlBuilder
     * @param Config       $config
     */
    public function __construct(
        UrlInterface $frontendUrlBuilder,
        Config $config
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->config = $config;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [];

        $notificationUrl = $this->frontendUrlBuilder->getUrl(self::PATH_TO_NOTIFICATION);

        $result[self::NOTIFICATION_URL] = $notificationUrl;

        $rewrite = $this->config->getRewriteNotificationUrl();

        if ($rewrite) {
            $result[self::NOTIFICATION_URL] = $rewrite;
        }

        return $result;
    }
}
