<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Sales\Info;

use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Payment details form block.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Info extends ConfigurableInfo
{
  private TimezoneInterface $timezone;

  /** @var PriceCurrencyInterface $priceCurrency */
  protected $priceCurrency;

  /**
   * @param Context $context
   * @param ConfigInterface $config
   * @param TimezoneInterface $timezone
   * @param PriceCurrencyInterface $priceCurrency
   * @param array $data
   */
  public function __construct(
    Context $context,
    ConfigInterface $config,
    TimezoneInterface $timezone,
    PriceCurrencyInterface $priceCurrency,
    array $data = []
  ) {
    parent::__construct($context, $config, $data);
    $this->timezone = $timezone;
    $this->priceCurrency = $priceCurrency;
  }

  /**
   * Function FormatedDate
   *
   * @param string $date
   *
   * @return string
   */
  public function date($date)
  {
    $localeDate = $this->timezone->date($date);

    $format = $localeDate->format('Y-m-d\TH:i:s.000O');


    return $this->formatDate(
      $format,
      \IntlDateFormatter::MEDIUM,
      true,
    );
  }

  /**
   * Function getFormatedPrice
   *
   * @param float $price
   * @param bool $includeContainer
   *
   * @return string
   */
  public function getFormatedPrice($amount, $includeContainer = true)
  {
    return $this->priceCurrency->convertAndFormat($amount, $includeContainer);
  }
}
