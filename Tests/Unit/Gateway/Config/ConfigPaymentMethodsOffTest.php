<?php

namespace Tests\Unit\Gateway\Config;

use PHPUnit\Framework\TestCase;

use Magento\Framework\Stdlib\DateTime\Datetime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;

class ConfigPaymentMethodsOffTest extends TestCase {
    /**
     * path pattern.
     */
    public const PATH_PATTERN = 'payment/%s/%s';
    /**
     * @var configPaymentMethodsOff
     */
    private $configPaymentMethodsOff;

    /**
     * @var configPaymentMethodsOffMock
     */
    private $configPaymentMethodsOffMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var DateTime
     */
    private $dateMock;

    /**
     * @var Config
     */
    private $configMock;

    public function setUp(): void 
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->dateMock = $this->getMockBuilder(DateTime::class)->disableOriginalConstructor()->getMock();

        $this->configPaymentMethodsOffMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)
                ->setConstructorArgs([
                    'scopeConfig' => $this->scopeConfigMock,
                    'date' => $this->dateMock,
                    'config' => $this->configMock,
                    'methodCode' => 'mercadopago_paymentmagento_payment_methods_off'
            ])->getMock();
    }

    public function test_isActive_get_true(){

        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::ACTIVE))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->isActive(null);

        $this->assertTrue($result);
    }

    public function test_getTitle_get_with_value(){

        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::TITLE))->willReturn('PaymentsOff');

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->getTitle(null);

        $this->assertEquals('PaymentsOff', $result);
    }

    // public function test_getExpirationFormatted_get_with_value(){
    //     $date = $dateMock->gmtDate('Y-m-d\T23:59:59.000O', strtotime("+2 days"));

    //     $this->scopeConfigMock->expects($this->any())->method('getValue')
    //     ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::EXPIRATION))->willReturn(2);

    //     $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
    //         $this->scopeConfigMock,
    //         $this->dateMock,
    //         $this->configMock,
    //     );

    //      $result = $configPaymentMethodsOff->getExpirationFormatted(null);

    //     $this->assertEquals($date, $result);
    // }

    public function test_hasUseDocumentIdentificationCapture_get_true(){

        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::USE_GET_DOCUMENT_IDENTIFICATION))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->hasUseDocumentIdentificationCapture(null);

        $this->assertTrue($result);
    }

    public function test_hasUseNameCapture_get_true(){

        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::USE_GET_NAME))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->hasUseNameCapture(null);

        $this->assertTrue($result);
    }

    public function test_getPaymentMethodsOffActive_get_value(){

        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::PAYMENT_METHODS))->willReturn('teste1,teste2');

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->getPaymentMethodsOffActive(null);

         $this->assertEquals('teste1,teste2', $result);
    }
}