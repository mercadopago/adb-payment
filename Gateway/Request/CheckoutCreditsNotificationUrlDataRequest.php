<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Request\CheckoutProNotificationUrlDataRequest as CheckoutProNotificationUrlDataRequest;

/**
 * Gateway Requests for Checkout Credits Notification Url.
 */
class CheckoutCreditsNotificationUrlDataRequest extends CheckoutProNotificationUrlDataRequest implements BuilderInterface
{

  /**
   * Path to Notification - url magento.
   */
  public const PATH_TO_NOTIFICATION = 'mp/notification/checkoutcredits';

  /**
   * @param UrlInterface $frontendUrlBuilder
   * @param Config       $config
   */
  public function __construct(
    UrlInterface $frontendUrlBuilder,
    Config $config,
    $pathNotication = self::PATH_TO_NOTIFICATION
  ) {
    $this->frontendUrlBuilder = $frontendUrlBuilder;
    $this->config = $config;
    $this->pathNotication = $pathNotication;
  }
}
