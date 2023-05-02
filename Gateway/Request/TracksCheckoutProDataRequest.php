<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\ConfigCheckoutPro;

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
     * Conversion Id block name.
     */
    public const CONVERSION_ID = 'conversion_id';

    /**
     * Conversion Label block name.
     */
    public const CONVERSION_LABEL = 'conversion_label';

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

        $includeFacebook = $this->config->isIncludeFacebook($storeId);

        if ($includeFacebook) {
            $facebookAd = $this->config->getFacebookAd($storeId);

            $result[self::TRACKS][] = [
                self::TYPE      => 'facebook_ad',
                self::VALUES    => [
                    self::PIXEL_ID => $facebookAd,
                ],
            ];
        }

        $includeGoogle = $this->config->isIncludeGoogle($storeId);

        if ($includeGoogle) {
            $googleAdsId = $this->config->getGoogleAdsId($storeId);
            $googleAdsLabel = $this->config->getGoogleAdsLabel($storeId);

            $result[self::TRACKS][] = [
                self::TYPE      => 'google_ad',
                self::VALUES    => [
                    self::CONVERSION_ID     => $googleAdsId,
                    self::CONVERSION_LABEL  => $googleAdsLabel,
                ],
            ];
        }

        return $result;
    }
}
