<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use Magento\Sales\Model\Order;

class ValidateNotValidOrderTest extends TestCase {

    public const STATE_NOT_VALID = "not_valid_order_status";

    public function testNotUpdateNotValidOrderToMpRejectedStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REJECTED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpApprovedStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpInMediationStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpPendingStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_PENDING);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpRefundedStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_REFUNDED);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpChargedBackStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK);

        $this->assertFalse($response->getIsValid());
    }

    public function testNotUpdateNotValidOrderToMpCancelledStatus(): void{
        $validate = ValidateFactory::createValidate(self::STATE_NOT_VALID);

        $response = $validate->verifyStatus(ValidateOrderStatusInterface::MP_STATUS_CANCELLED);

        $this->assertFalse($response->getIsValid());
    }
}
