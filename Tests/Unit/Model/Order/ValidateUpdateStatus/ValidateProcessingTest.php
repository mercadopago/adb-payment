<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidateProcessingTest extends TestCase {

    public function testUpdateProcessingToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_APPROVED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateProcessingToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertFalse($response->getIsValid());
    }
}

