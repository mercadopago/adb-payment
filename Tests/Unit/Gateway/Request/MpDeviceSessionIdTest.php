<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Request\MpDeviceSessionId;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment\Interceptor;

class MpDeviceSessionIdTest extends TestCase {
    public function testBuildWithSession()
    {
        $paymentInterceptorMock = $this->createMock(Interceptor::class);
        $paymentInterceptorMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with(MpDeviceSessionId::MP_DEVICE_SESSION_ID)
            ->willReturn('arm:1234');
        
        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInterceptorMock);
        
        $class = new MpDeviceSessionId();

        $result = $class->build(['payment' => $paymentDataObjectMock]);

        $this->assertEquals([MpDeviceSessionId::MP_DEVICE_SESSION_ID => 'arm:1234'], $result);
    }

    public function testBuildWithoutSession()
    {
        $paymentInterceptorMock = $this->createMock(Interceptor::class);
        $paymentInterceptorMock->expects($this->any())
            ->method('getAdditionalInformation')
            ->with(MpDeviceSessionId::MP_DEVICE_SESSION_ID)
            ->willReturn(null);
        
        $paymentDataObjectMock = $this->createMock(PaymentDataObject::class);
        $paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentInterceptorMock);
        
        $class = new MpDeviceSessionId();

        $result = $class->build(['payment' => $paymentDataObjectMock]);

        $this->assertEquals([], $result);
    }

    public function testBuildWithoutPayment()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $class = new MpDeviceSessionId();
        $class->build([]);
    }
}
