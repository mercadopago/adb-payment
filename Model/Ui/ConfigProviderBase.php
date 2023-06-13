<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * User interface model for settings Base Method.
 */
class ConfigProviderBase implements ConfigProviderInterface
{
    /**
     * Mercado Pago Payment Magento Code.
     */
    public const CODE = 'mercadopago_adbpayment';

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartInterface
     */
    protected $cart;

    /**
     * @param Resolver      $resolver
     * @param Config        $config
     * @param CartInterface $cart
     */
    public function __construct(
        Resolver $resolver,
        Config $config,
        CartInterface $cart
    ) {
        $this->resolver = $resolver;
        $this->config = $config;
        $this->cart = $cart;
    }

    /**
     * Retrieve assoc array of checkout configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cart->getStoreId();

        return [
            'payment' => [
                Config::METHOD => [
                    'isActive'      => false,
                    'public_key'    => $this->config->getMerchantGatewayClientId($storeId),
                    'locale'        => $this->getLocale(),
                    'mp_site_id'    => $this->config->getMpSiteId($storeId),
                ],
            ],
        ];
    }

    /**
     * Formated Locale.
     *
     * @return string
     */
    public function getLocale()
    {
        $currentStore = $this->resolver->getLocale();

        return str_replace('_', '-', $currentStore);
    }
}
