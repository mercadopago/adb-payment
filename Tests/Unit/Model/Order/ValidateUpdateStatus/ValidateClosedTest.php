<?php

namespace Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidateClosedTest extends TestCase {

    public function testNotUpdateClosedToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateClosedToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertFalse($response->getIsValid());
    }
}
