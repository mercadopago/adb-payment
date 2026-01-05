<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Console\Command\Notification;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutCreditsAddChildPayment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MercadoPago\AdbPayment\Console\Command\Notification\CheckoutCreditsAddChild;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

class CheckoutCreditsAddChildTest extends TestCase
{

  /**
   * @var CheckoutCreditsAddChildPayment
   */
  protected $checkoutCreditsAddChildPaymentMock;

  /**
   * @var CheckoutCreditsAddChild
   */
  protected $checkoutCreditsAddChildMock;

  /**
   * @var CheckoutCreditsAddChild
   */
  protected $checkoutCreditsAddChild;

  /**
   * @var State
   */
  protected $stateMock;

  public function setUp(): void
  {  
    $this->checkoutCreditsAddChildPaymentMock = $this->getMockBuilder(CheckoutCreditsAddChildPayment::class)->disableOriginalConstructor()->getMock();
    $this->stateMock = $this->getMockBuilder(State::class)->disableOriginalConstructor()->getMock();
    $this->checkoutCreditsAddChild = new CheckoutCreditsAddChild
    (
      $this->stateMock,
      $this->checkoutCreditsAddChildPaymentMock
    );
  }

  public function testExecute()
  {
    $inputMock = $this->getMockBuilder(InputInterface::class)->disableOriginalConstructor()->getMock();
    $outputMock = $this->getMockBuilder(OutputInterface::class)->disableOriginalConstructor()->getMock();

    $orderId = 1;
    $transactionId = 2;
    
    $inputMock->expects($this->any())
      ->method('getArgument')
      ->withConsecutive([CheckoutCreditsAddChild::ORDER_ID], [CheckoutCreditsAddChild::CHILD])
      ->willReturnOnConsecutiveCalls($orderId, $transactionId);
  
    $reflection = new ReflectionClass($this->checkoutCreditsAddChild);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(true);
    $method->invoke($this->checkoutCreditsAddChild, $inputMock, $outputMock);
    
    $reflectionProperty = new ReflectionProperty($this->checkoutCreditsAddChild, 'state');
    $reflectionProperty->setAccessible(true);
    $stateValue = $reflectionProperty->getValue($this->checkoutCreditsAddChild);
    
    $this->assertEquals($stateValue, $this->stateMock);
  }

  public function testConfigure()
  {
    $reflection = new ReflectionClass(CheckoutCreditsAddChild::class);
    $method = $reflection->getMethod('configure');
    $method->setAccessible(true);
    $method->invoke($this->checkoutCreditsAddChild);

    $this->assertEquals($this->checkoutCreditsAddChild->getName(), 'mercadopago:order:checkout_credits_add_child');
    $this->assertEquals($this->checkoutCreditsAddChild->getDescription(), 'Fetch Order Checkout Credits');
    $this->assertIsArray($this->checkoutCreditsAddChild->getDefinition()->getArguments());
    $this->assertArrayHasKey(CheckoutCreditsAddChild::ORDER_ID, $this->checkoutCreditsAddChild->getDefinition()->getArguments());
  }
}