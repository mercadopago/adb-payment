<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidateCompleteTest extends TestCase {

    public function testUpdateCompleteToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertTrue($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_APPROVED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateCompleteToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertFalse($response->getIsValid());
    }
}
