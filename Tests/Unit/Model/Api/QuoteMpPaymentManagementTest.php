<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace Tests\Unit\Model\Api;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\QuoteMpPaymentRepository;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MercadoPago\AdbPayment\Model\QuoteMpPayment;
use MercadoPago\AdbPayment\Model\Api\QuoteMpPaymentManagement;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Customer\Model\Session as CustomerSession;

class QuoteMpPaymentManagementTest extends TestCase {

    /**
     * @var service
     */
    protected $service;

    /**
     * @var quoteMpPaymentRepository
     */
    protected $quoteMpPaymentRepository;

    /**
     * @var maskedQuoteIdToQuoteIdInterface
     */
    protected $maskedQuoteIdToQuoteIdInterface;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var objectManager
    */
    protected $objectManager;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->quoteMpPaymentRepository = $this->getMockBuilder(QuoteMpPaymentRepository::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->maskedQuoteIdToQuoteIdInterface = $this->getMockBuilder(MaskedQuoteIdToQuoteIdInterface::class)->disableOriginalConstructor()
        ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)->disableOriginalConstructor()
        ->getMock();

        $this->service = new QuoteMpPaymentManagement($this->quoteMpPaymentRepository, $this->maskedQuoteIdToQuoteIdInterface, $this->customerSession);                                    
    }

    public function testGetMpPaymentWhenCustomerLoggedIn() {
        
        $quoteMock = $this->objectManager->getObject(QuoteMpPayment::class);

        $quoteId = 10;
        $paymentId = 123131;

        $quoteMock->setQuoteId($quoteId);
        $quoteMock->setPaymentId($paymentId);

        $this->customerSession->expects($this->once())->method('isLoggedIn')->with()->willReturn(true);
        $this->quoteMpPaymentRepository->expects($this->once())->method('getByQuoteId')->with($quoteId)->willReturn($quoteMock);
        $response = $this->service->getQuoteMpPayment($quoteId);

        $this->assertEquals($paymentId, $response['data']['payment_id']);
    }

    public function testGetMpPaymentWhenCustomerGuest() {
        
        $quoteMock = $this->objectManager->getObject(QuoteMpPayment::class);

        $maskedQuoteId = "ai19828a9a81";
        $quoteId = 10;
        $paymentId = 123131;

        $quoteMock->setQuoteId($quoteId);
        $quoteMock->setPaymentId($paymentId);

        $this->customerSession->expects($this->once())->method('isLoggedIn')->with()->willReturn(false);
        $this->maskedQuoteIdToQuoteIdInterface->expects($this->once())->method('execute')->with($maskedQuoteId)->willReturn($quoteId);
        $this->quoteMpPaymentRepository->expects($this->once())->method('getByQuoteId')->with($quoteId)->willReturn($quoteMock);
        $response = $this->service->getQuoteMpPayment($maskedQuoteId);

        $this->assertEquals($paymentId, $response['data']['payment_id']);
    }
}