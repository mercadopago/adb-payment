<?php

namespace Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidatePendingPaymentTest extends TestCase {

    public function testUpdatePendingPaymentToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingPaymentToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_APPROVED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingPaymentToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingPaymentToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingPaymentToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdatePendingPaymentToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdatePendingPaymentToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }
}

