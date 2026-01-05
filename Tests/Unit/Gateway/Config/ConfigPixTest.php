<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigPix;
use MercadoPago\AdbPayment\Gateway\Data\Checkout\Fingerprint;
use PHPUnit\Framework\TestCase;

class ConfigPixTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configMock;

    /**
     * @var DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateMock;

    /**
     * @var Fingerprint|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fingerprintMock;

    /**
     * @var ConfigPix
     */
    protected $configPix;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fingerprintMock = $this->getMockBuilder(Fingerprint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configPix = new ConfigPix(
            $this->scopeConfigMock,
            $this->configMock,
            $this->dateMock,
            $this->fingerprintMock
        );
    }

    /**
     * @dataProvider expirationDurationDataProvider
     */
    public function testGetExpirationDuration(int $minutes, string $expectedDuration): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'payment/mercadopago_adbpayment_pix/expiration',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn($minutes);

        $result = $this->configPix->getExpirationDuration();

        $this->assertEquals($expectedDuration, $result);
    }

    public function expirationDurationDataProvider(): array
    {
        return [
            '15 minutes' => [15, 'PT15M'],
            '30 minutes' => [30, 'PT30M'],
            '1 hour' => [60, 'PT1H'],
            '12 hours' => [720, 'PT12H'],
            '1 day' => [1440, 'P1D'],
            '2 days' => [2880, 'P2D'],
            '3 days' => [4320, 'P3D'],
            '4 days' => [5760, 'P4D'],
            '5 days' => [7200, 'P5D'],
            '6 days' => [8640, 'P6D'],
            '7 days' => [10080, 'P7D'],
        ];
    }

    public function testGetExpirationDurationWithStoreId(): void
    {
        $storeId = 2;

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                'payment/mercadopago_adbpayment_pix/expiration',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(1440);

        $result = $this->configPix->getExpirationDuration($storeId);

        $this->assertEquals('P1D', $result);
    }

    /**
     * @dataProvider invalidExpirationDataProvider
     */
    public function testGetExpirationDurationReturnsDefaultForInvalidValue($invalidValue): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($invalidValue);

        $result = $this->configPix->getExpirationDuration();

        $this->assertEquals('PT30M', $result);
    }

    public function invalidExpirationDataProvider(): array
    {
        return [
            'unknown value 999' => [999],
            'zero' => [0],
            'null' => [null],
            'negative' => [-1],
            'string' => ['invalid'],
        ];
    }
}

