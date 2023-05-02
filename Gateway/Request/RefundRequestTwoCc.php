<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigTwoCc;

/**
 * Gateway requests for Payment refund.
 */
class RefundRequestTwoCc implements BuilderInterface
{
  /**
   * External Payment Id block name.
   */
  public const MERCADOPAGO_PAYMENT_ID = 'payment_id';

  /**
   * Idempotency Key block name.
   */
  public const X_IDEMPOTENCY_KEY = 'x-idempotency-key';

  /**
   * Amount block name.
   */
  public const AMOUNT = 'amount';

  /**
   * @var ConfigInterface
   */
  protected $config;

  /**
   * @var Config
   */
  protected $configPayment;

  /**
   * @var ConfigTwoCc
   */
  protected $configTwoCc;

  /**
   * @param ConfigInterface $config
   * @param Config          $configPayment
   * @param ConfigCc        $configCc
   * @param ConfigTwoCc        $configTwoCc
   */
  public function __construct(
    ConfigInterface $config,
    Config $configPayment,
    ConfigTwoCc $configTwoCc
  ) {
    $this->config = $config;
    $this->configPayment = $configPayment;
    $this->configTwoCc = $configTwoCc;
  }

  /**
   * Build.
   *
   * @param array $buildSubject
   */
  public function build(array $buildSubject)
  {
    if (
      !isset($buildSubject['payment'])
      || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
    ) {
      throw new InvalidArgumentException('Payment data object should be provided');
    }

    $paymentDO = $buildSubject['payment'];

    $payment = $paymentDO->getPayment();

    $order = $payment->getOrder();

    $storeId = $order->getStoreId();

    $grandTotal = $this->configPayment->formatPrice($order->getGrandTotal(), $storeId);

    $creditmemo = $payment->getCreditMemo();

    $totalCreditmemo = $this->configPayment->formatPrice($creditmemo->getGrandTotal(), $storeId);

    $result = [
      self::MERCADOPAGO_PAYMENT_ID => preg_replace('/[^0-9]/', '', $payment->getTransactionId()),
      self::X_IDEMPOTENCY_KEY      => $payment->getTransactionId() . '-' .  uniqid(),
      self::AMOUNT                 => $totalCreditmemo,
    ];

    return $result;
  }
}
