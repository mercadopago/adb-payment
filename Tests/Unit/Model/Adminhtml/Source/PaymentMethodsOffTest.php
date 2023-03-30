<?php

namespace Tests\Unit\Model\Adminhtml\Source;

use PHPUnit\Framework\TestCase;

use MercadoPago\PaymentMagento\Tests\Unit\Mocks\Gateway\Config\PaymentMethodsResponseMock;

use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Config\Config as MercadoPagoConfig;
use MercadoPago\PaymentMagento\Model\Adminhtml\Source\PaymentMethodsOff;
use Magento\Framework\App\RequestInterface;

class PaymentMethodsOffTest extends TestCase {
    
    /**
     * @var paymentMethodsOff
     */
    private $paymentMethodsOff;

    /**
     * @var PaymentMethodsOffMock
     */
    private $paymentMethodsOffMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;
     /**
     * @var MercadoPagoConfig
     */
    private $mercadopagoConfigMock;

    public function setUp(): void
    {
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        
        $this->paymentMethodsOffMock = $this->getMockBuilder(PaymentMethodsOff::class)->setConstructorArgs([
            'request' => $this->requestMock,
            'mercadopagoConfig' => $this->mercadopagoConfigMock
        ])->getMock();

        $this->paymentMethodsOff = new PaymentMethodsOff(
            $this->requestMock,
            $this->mercadopagoConfigMock
        );
    }

    public function test_toOptionArray_not_empty(): void
    {
        $storeId = 1;
        
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::WITH_PAYMENT_PLACES);

        $this->requestMock->expects($this->any())
        ->method('getParam')
        ->with('store', 0)
        ->willReturn(1);

        $this->paymentMethodsOff = new PaymentMethodsOff(
            $this->requestMock,
            $this->mercadopagoConfigMock
        );

        $result = $this->paymentMethodsOff->toOptionArray();

        $this->assertNotEmpty($result);
    }

    public function test_toOptionArray_just_default_value(): void
    {
        $storeId = 1;
        
        $this->mercadopagoConfigMock = $this->getMockBuilder(MercadoPagoConfig::class)->disableOriginalConstructor()->getMock();
        $this->mercadopagoConfigMock->expects($this->any())
            ->method('getMpPaymentMethods')
            ->with($storeId)
            ->willReturn(PaymentMethodsResponseMock::SUCCESS_FALSE);

        $this->requestMock->expects($this->any())
        ->method('getParam')
        ->with('store', 0)
        ->willReturn(1);

        $this->paymentMethodsOff = new PaymentMethodsOff(
            $this->requestMock,
            $this->mercadopagoConfigMock
        );

        $result = $this->paymentMethodsOff->toOptionArray();

        $this->assertEquals(1, count($result));
    }
}