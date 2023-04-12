<?php

namespace Tests\Unit\Gateway\Config;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MercadoPago\PaymentMagento\Gateway\Config\Config;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\PaymentMagento\Gateway\Config\ConfigPaymentMethodsOff;
use MercadoPago\PaymentMagento\Gateway\Data\Checkout\Fingerprint;

use PHPUnit\Framework\TestCase;

class ConfigPaymentMethodsOffTest extends TestCase {
    
    /**
     * path pattern.
     */
    public const PATH_PATTERN = 'payment/%s/%s';

    /**
     * config method.
     */
    public const CONFIG_METHOD = 'getValue';

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

    /**
     * @var Fingerprint
     */
    protected $fingerprintMock;

    public function setUp(): void {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods([self::CONFIG_METHOD])->getMockForAbstractClass();

        $this->configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->dateMock = $this->getMockBuilder(DateTime::class)->disableOriginalConstructor()->getMock();
        $this->fingerprintMock = $this->getMockBuilder(Fingerprint::class)->disableOriginalConstructor()->getMock();

        $this->configPaymentMethodsOffMock = $this->getMockBuilder(ConfigPaymentMethodsOff::class)
                ->setConstructorArgs([
                    'scopeConfig' => $this->scopeConfigMock,
                    'date' => $this->dateMock,
                    'config' => $this->configMock,
                    'fingerprint' => $this->fingerprintMock,
                    'methodCode' => ConfigPaymentMethodsOff::METHOD
            ])->getMock();
    }

    /**
     * Tests functions get configs()
    */

    public function testIsActiveGetTrue() {

        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::ACTIVE))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

         $result = $configPaymentMethodsOff->isActive(null);

        $this->assertTrue($result);
    }

    public function testGetTitleGetWithValue() {

        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::TITLE))->willReturn('PaymentsOff');

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

         $result = $configPaymentMethodsOff->getTitle(null);

        $this->assertEquals('PaymentsOff', $result);
    }

    public function testHasUseDocumentIdentificationCaptureGetTrue() {

        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::USE_GET_DOCUMENT_IDENTIFICATION))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

         $result = $configPaymentMethodsOff->hasUseDocumentIdentificationCapture(null);

        $this->assertTrue($result);
    }

    public function testHasUseNameCaptureGetTrue() {

        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::USE_GET_NAME))->willReturn(true);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

         $result = $configPaymentMethodsOff->hasUseNameCapture(null);

        $this->assertTrue($result);
    }

    public function testGetPaymentMethodsOffActiveGetValue() {
        $stringGetMethods = 'boleto, pec';
        $this->scopeConfigMock->expects($this->any())->method(self::CONFIG_METHOD)
        ->with(sprintf(self::PATH_PATTERN, ConfigPaymentMethodsOff::METHOD, ConfigPaymentMethodsOff::PAYMENT_METHODS))->willReturn($stringGetMethods);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

         $result = $configPaymentMethodsOff->getPaymentMethodsOffActive(null);

         $this->assertEquals($stringGetMethods, $result);
    }

    /**
     * Tests functions format bar code value
    */

    public function testGetLineCodeWithValidValue() {
        $codeParameter = '23791930400000054003380260600346799100633330';
        $expectFormatedCode = '23793.38029 60600.346799 91006.333305 1 93040000005400';

        $this->configPaymentMethodsOffMock->expects($this->any())->method('getLineCode')
            ->with($codeParameter)->willReturn($expectFormatedCode);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

        $result = $configPaymentMethodsOff->getLineCode($codeParameter);

        $this->assertEquals($expectFormatedCode, $result);
    }

    /**
     * Tests functions calc bar code digit
    */

    public function testCalcDigitGetValidDigit() {
        $expectedDigit = 9;
        $codeToCalc = '23793.3802';

        $this->configPaymentMethodsOffMock->expects($this->any())->method('calcDigit')
            ->with($codeToCalc)->willReturn($expectedDigit);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

        $result = $configPaymentMethodsOff->calcDigit($codeToCalc);

        $this->assertEquals($expectedDigit, $result);
    }

    public function testCalcDigitGetInvalidDigit() {
        $expectedDigit = 9;
        $invalidCodeToCalc = '23793.3803';
        
        $this->configPaymentMethodsOffMock->expects($this->any())->method('calcDigit')
            ->with($invalidCodeToCalc)->willReturn($expectedDigit);

        $configPaymentMethodsOff = new ConfigPaymentMethodsOff(
            $this->scopeConfigMock,
            $this->dateMock,
            $this->configMock,
            $this->fingerprintMock
        );

        $result = $configPaymentMethodsOff->calcDigit($invalidCodeToCalc);

        $this->assertNotEquals($expectedDigit, $result);
    }
}