<?php

namespace Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidatePaymentReviewStatus;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidatePaymentReviewTest extends TestCase {

    public function testUpdatePaymentReviewToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePaymentReviewToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_APPROVED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePaymentReviewToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePaymentReviewToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePaymentReviewToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdatePaymentReviewToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdatePaymentReviewToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PAYMENT_REVIEW);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

}
