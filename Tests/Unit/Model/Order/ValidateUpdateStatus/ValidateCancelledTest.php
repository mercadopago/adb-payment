<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidateCancelledTest extends TestCase {

    public function testUpdateCancelledToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCanceledToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertFalse($response->getIsValid());
    }

}

