<?php

namespace MercadoPago\AdbPayment\Test\Unit\Block\Sales\Info;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\ConfigInterface;
use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Block\Sales\Info\Info;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;

class InfoTest extends TestCase
{

  /**
   * @var Info
   */
  private $block;

  /**
   * @var TimezoneInterface
   */
  private $timezone;

  /**
   * @var Context
   */
  private $context;

  /**
   * @var PriceCurrencyInterface
   */
  private $priceCurrencyMock;

  protected function setUp(): void
  {
    $config = $this->getMockForAbstractClass(ConfigInterface::class);

    $this->timezone = $this->getMockForAbstractClass(TimezoneInterface::class);

    $this->priceCurrencyMock = $this->getMockForAbstractClass(PriceCurrencyInterface::class);

    $this->context = $this->createMock(Context::class);
    $this->context->expects($this->any())
      ->method('getLocaleDate')
      ->willReturn($this->timezone);

    $this->block = new Info(
      $this->context,
      $config,
      $this->timezone,
      $this->priceCurrencyMock,
      []
    );
  }

  public function testDate()
  {

    $date = '2023-11-10T12:34:32.000-04:00';
    $timestamp = strtotime($date);
    $expected = '10 de nov. de 2023 13:34:32';

    $this->timezone->expects($this->once())
      ->method('date')
      ->willReturn((new \DateTime('now', new \DateTimeZone('UTC')))->setTimestamp($timestamp));

    $resDate = (new \DateTime('now', new \DateTimeZone('America/Sao_Paulo')))->setTimestamp($timestamp);

    $this->timezone->expects($this->once())
      ->method('formatDateTime')
      ->willReturn(strtolower($resDate->format('d \d\e M\. \d\e Y H:i:s')));

    $result = $this->block->date($date);

    $this->assertEquals($expected, $result);
  }

  public function testGetFormatedPrice()
  {
    $amount = 100.0;
    $expected = '$100.00';
    $this->priceCurrencyMock->expects($this->once())
      ->method('convertAndFormat')
      ->with($amount)
      ->willReturn($expected);
    $result = $this->block->getFormatedPrice($amount);
    $this->assertEquals($expected, $result);
  }
}
