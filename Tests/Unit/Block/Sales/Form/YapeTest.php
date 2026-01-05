<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Block\Sales\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Framework\View\Element\Template\Context;
use MercadoPago\AdbPayment\Block\Sales\Form\Yape;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Config\ConfigYape;
use PHPUnit\Framework\TestCase;



class YapeTest extends TestCase
{
    /**
     * @var Yape
     */
    private $yapeBlock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var ConfigYape|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configYapeMock;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionQuoteMock;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configYapeMock = $this->createMock(ConfigYape::class);
        $this->sessionQuoteMock = $this->createMock(Quote::class);
        $this->contextMock = $this->createMock(Context::class);

        $this->yapeBlock = new Yape(
            $this->contextMock,
            $this->configMock,
            $this->configYapeMock,
            $this->sessionQuoteMock
        );
    }

    public function testGetBackendSessionQuote()
    {
        $quoteMock = $this->createMock(QuoteModel::class);
        $this->sessionQuoteMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->assertSame($quoteMock, $this->yapeBlock->getBackendSessionQuote());
    }

    public function testGetTitle()
    {
        $storeId = 1;
        $quoteMock = $this->createMock(QuoteModel::class);
        $quoteMock->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->sessionQuoteMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $title = 'Yape Payment';
        $this->configYapeMock->expects($this->once())
            ->method('getTitle')
            ->with($storeId)
            ->willReturn($title);

        $this->assertEquals($title, $this->yapeBlock->getTitle());
    }
}