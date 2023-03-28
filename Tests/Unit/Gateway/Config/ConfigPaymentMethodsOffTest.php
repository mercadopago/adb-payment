<?php

namespace Tests\Unit\Gateway\Config;

use Magento\Framework\Stdlib\DateTime\Datetime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;

use PHPUnit\Framework\TestCase;

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

    public function setUp(): void {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->dateMock = $this->getMockBuilder(DateTime::class)->disableOriginalConstructor()->getMock();

        $this->configPaymentMethodsOffMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)
                ->setConstructorArgs([
                    'scopeConfig' => $this->scopeConfigMock,
                    'date' => $this->dateMock,
                    'config' => $this->configMock,
                    'methodCode' => ConfigPaymentMethodsOff::METHOD
            ])->getMock();
    }

    public function test_isActive_get_true() {

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

    public function test_getTitle_get_with_value() {

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

    public function test_hasUseDocumentIdentificationCapture_get_true() {

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

    public function test_hasUseNameCapture_get_true() {

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

    public function test_getPaymentMethodsOffActive_get_value() {
        $string_get_methods = 'boleto, pec';
        $this->scopeConfigMock->expects($this->any())->method('getValue')
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::PAYMENT_METHODS))->willReturn($string_get_methods);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

         $result = $configPaymentMethodsOff->getPaymentMethodsOffActive(null);

         $this->assertEquals($string_get_methods, $result);
    }

    public function test_getLineCode_with_valid_value() {
        $code_parameter = '23791930400000054003380260600346799100633330';
        $expect_formated_code = '23793.38029 60600.346799 91006.333305 1 93040000005400';

        $this->configPaymentMethodsOffMock->expects($this->any())->method('getLineCode')
            ->with($code_parameter)->willReturn($expect_formated_code);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

        $result = $configPaymentMethodsOff->getLineCode($code_parameter);

        $this->assertEquals($expect_formated_code, $result);
    }

    public function test_calcDigit_get_valid_digit() {
        $expected_digit = 9;
        $code_to_calc = '23793.3802';

        $this->configPaymentMethodsOffMock->expects($this->any())->method('calcDigit')
            ->with($code_to_calc)->willReturn($expected_digit);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

        $result = $configPaymentMethodsOff->calcDigit($code_to_calc);

        $this->assertEquals($expected_digit, $result);
    }

    public function test_calcDigit_get_invalid_digit() {
        $expected_digit = 9;
        $code_to_calc = '23793.3803';
        
        $this->configPaymentMethodsOffMock->expects($this->any())->method('calcDigit')
            ->with($code_to_calc)->willReturn($expected_digit);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
        );

        $result = $configPaymentMethodsOff->calcDigit($code_to_calc);

        $this->assertNotEquals($expected_digit, $result);
    }
}