<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidatePendingStatus;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidatePendingTest extends TestCase {

    public function testUpdatePendingToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_APPROVED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertTrue($response->getIsValid());
    }

    public function testUpdatePendingToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdatePendingToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdatePendingToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

}

