<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Block\Sales\Info;

use MercadoPago\AdbPayment\Block\Sales\Info\Yape;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

use PHPUnit\Framework\TestCase;

class YapeTest extends TestCase
{
    /**
     * @var Yape
     */
    private $yapeBlock;

    /**
     * @var Context
     */
    private $contextMock; // @var Context

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrencyMock;


    protected function setUp(): void
    {
        $config = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->timezone = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->priceCurrencyMock = $this->getMockForAbstractClass(PriceCurrencyInterface::class);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getLocaleDate')
            ->willReturn($this->timezone);

        $this->yapeBlock = new Yape(
            $this->contextMock,
            $config,
            $this->timezone,
            $this->priceCurrencyMock,
            []
        );
    }

    public function testTemplateIsSetCorrectly()
    {
        $expectedTemplate = 'MercadoPago_AdbPayment::info/yape/instructions.phtml';
        $this->assertEquals($expectedTemplate, $this->yapeBlock->getTemplate());
    }
}
