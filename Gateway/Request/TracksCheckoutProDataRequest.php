<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigCheckoutPro;

/**
 * Gateway Requests for tracks in Checkout Pro.
 */
class TracksCheckoutProDataRequest implements BuilderInterface
{
    /**
     * Tracks block name.
     */
    public const TRACKS = 'tracks';

    /**
     * Type block name.
     */
    public const TYPE = 'type';

    /**
     * Values block name.
     */
    public const VALUES = 'values';

    /**
     * Pixel Id block name.
     */
    public const PIXEL_ID = 'pixel_id';

    /**
     * @var ConfigCheckoutPro
     */
    protected $config;

    /**
     * @param ConfigCheckoutPro $config
     */
    public function __construct(
        ConfigCheckoutPro $config
    ) {
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

        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $storeId = $order->getStoreId();
        $result = [];

        $facebookAd = $this->config->getFacebookAd($storeId);

        if (isset($facebookAd)) {
            $result[self::TRACKS][] = [
                self::TYPE      => 'facebook_ad',
                self::VALUES    => [
                    self::PIXEL_ID => $facebookAd,
                ],
            ];
        }

        $googleAds = $this->config->getGoogleAds($storeId);

        if (isset($googleAds)) {
            $result[self::TRACKS][] = [
                self::TYPE      => 'google_ad',
                self::VALUES    => [
                    self::PIXEL_ID => $googleAds,
                ],
            ];
        }

        return $result;
    }
}
