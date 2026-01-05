<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Api;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MercadoPago\AdbPayment\Model\Api\PaymentStatusManagement;
use MercadoPago\AdbPayment\Model\MPApi\PaymentGet;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as QuoteCartInterface;


use PHPUnit\Framework\TestCase;



class PaymentStatusManagementTest extends TestCase
{

  /**
   * @var objectManager
   */
  protected $objectManager;

  /**
   * @var paymentGet
   */
  protected $paymentGet;

  /**
   * @var quoteRepository
   */
  protected $quoteRepository;

  /**
   * @var service
   */
  protected $service;

  public function setUp(): void
  {
    $this->objectManager = new ObjectManager($this);

    $this->paymentGet = $this->getMockBuilder(PaymentGet::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->service = new PaymentStatusManagement($this->paymentGet, $this->quoteRepository);
  }

  public function testGetPaymentStatus()
  {
    $paymentId = '123131';
    $cartId = '10';
    $storeId = 1;

    $quoteMock = $this->getMockBuilder(QuoteCartInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->quoteRepository->expects($this->any())
      ->method('getActive')
      ->with($cartId)
      ->willReturn($quoteMock);

    $quoteMock->expects($this->any())
      ->method('getStoreId')
      ->willReturn($storeId);

    $this->paymentGet->expects($this->any())
      ->method('get')
      ->with($paymentId, $storeId)->willReturn([
        'id' => $paymentId,
        'status' => 'approved',
        'status_detail' => 'accredited',
      ]);

    $response = $this->service->getPaymentStatus($paymentId, $cartId);

    $this->assertEquals($paymentId, $response['data']['payment_id']);
    $this->assertEquals('approved', $response['data']['status']);
    $this->assertEquals('accredited', $response['data']['status_detail']);
  }
}
