<?php

namespace Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateFactory;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidatePendingStatus;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;

use Magento\Sales\Model\Order;

class ValidateFactoryTest extends TestCase {

    public function testInvalidAdobeStatus(): void{
        $validate = ValidateFactory::createValidate('OTHER');

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidCancelledAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CANCELED);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidClosedAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_CLOSED);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidCompleteAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_COMPLETE);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidPendingPaymentAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PENDING_PAYMENT);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidPendingAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(ValidatePendingStatus::STATE_PENDING);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

    public function testValidProcessingAdobeStatus(): void{
        $validate = ValidateFactory::createValidate(Order::STATE_PROCESSING);

        $this->assertInstanceOf(ValidateOrderStatusInterface::class, $validate);
    }

}

