<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Request;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Request\ItemsAndDatailsCostDataRequest;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use MercadoPago\AdbPayment\Gateway\Data\Order\OrderAdapterFactory;
use Magento\Catalog\Helper\Image;
use MercadoPago\AdbPayment\Gateway\SubjectReader;

class ItemsAndDatailsCostDataRequestTest extends TestCase
{
    private Config $config;
    private ItemsAndDatailsCostDataRequest $request;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $image = $this->createMock(Image::class);
        $subjectReader = $this->createMock(SubjectReader::class);
        $orderAdapterFactory = $this->createMock(OrderAdapterFactory::class);

        $this->request = new ItemsAndDatailsCostDataRequest(
            $image,
            $subjectReader,
            $this->config,
            $orderAdapterFactory
        );
    }

    /**
     * @return void
     */
    public function testAddItemsDiscountPassesStoreIdToFormatPrice()
    {
        $discountAmount = -5.5;
        $storeId = 1;

        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDiscountDescription'])
            ->getMock();
        $orderAdapter->method('getDiscountDescription')->willReturn('H20');

        $this->config->expects($this->once())
            ->method('formatPrice')
            ->with($discountAmount, $storeId)
            ->willReturn(-6.0);

        $this->config->method('getMpCategory')->willReturn('clothing');

        $result = $this->request->addItemsDiscount($orderAdapter, $discountAmount, $storeId);

        $this->assertCount(1, $result);
        $this->assertEquals(-6.0, $result[0]['unit_price']);
    }

    /**
     * @return void
     */
    public function testAddItemsDiscountWithZeroAmountReturnsEmpty()
    {
        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDiscountDescription'])
            ->getMock();

        $result = $this->request->addItemsDiscount($orderAdapter, 0.0, 1);

        $this->assertEmpty($result);
    }

    /**
     * @return void
     */
    public function testAddItemsShippingPassesStoreIdToFormatPrice()
    {
        $shippingAmount = 2990.0;
        $storeId = 1;

        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getShippingMethod', 'getShippingDescription'])
            ->getMock();
        $orderAdapter->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $orderAdapter->method('getShippingDescription')->willReturn('Flat Rate - Fixed');

        $this->config->expects($this->once())
            ->method('formatPrice')
            ->with($shippingAmount, $storeId)
            ->willReturn(2990.0);

        $this->config->method('getMpCategory')->willReturn('clothing');

        $result = $this->request->addItemsShipping($orderAdapter, $shippingAmount, $storeId);

        $this->assertCount(1, $result);
        $this->assertEquals(2990.0, $result[0]['unit_price']);
    }

    /**
     * @return void
     */
    public function testAddItemsTaxPassesStoreIdToFormatPrice()
    {
        $taxAmount = 2.0;
        $storeId = 1;

        $this->config->expects($this->once())
            ->method('formatPrice')
            ->with($taxAmount, $storeId)
            ->willReturn(2.0);

        $this->config->method('getMpCategory')->willReturn('clothing');

        $result = $this->request->addItemsTax($taxAmount, $storeId);

        $this->assertCount(1, $result);
        $this->assertEquals(2.0, $result[0]['unit_price']);
    }

    /**
     * @return void
     */
    public function testAddItemsTaxWithZeroAmountReturnsEmpty()
    {
        $result = $this->request->addItemsTax(0.0, 1);

        $this->assertEmpty($result);
    }

    /**
     * @return void
     */
    public function testAddItemsShippingWithZeroAmountReturnsEmpty()
    {
        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getShippingMethod', 'getShippingDescription'])
            ->getMock();

        $result = $this->request->addItemsShipping($orderAdapter, 0.0, 1);

        $this->assertEmpty($result);
    }

    /**
     * @return void
     */
    public function testAddItemsDiscountPassesStoreIdForZeroDecimalCurrency()
    {
        $discountAmount = -5.5;
        $storeId = 1;

        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDiscountDescription'])
            ->getMock();
        $orderAdapter->method('getDiscountDescription')->willReturn('coupon');

        $this->config->method('formatPrice')
            ->with($discountAmount, $storeId)
            ->willReturn(-6.0);
        $this->config->method('getMpCategory')->willReturn('clothing');

        $result = $this->request->addItemsDiscount($orderAdapter, $discountAmount, $storeId);

        $this->assertEquals(-6.0, $result[0]['unit_price']);
    }

    /**
     * @return void
     */
    public function testAddItemsDiscountPassesStoreIdForTwoDecimalCurrency()
    {
        $discountAmount = -22.48;
        $storeId = 2;

        $orderAdapter = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getDiscountDescription'])
            ->getMock();
        $orderAdapter->method('getDiscountDescription')->willReturn('coupon');

        $this->config->method('formatPrice')
            ->with($discountAmount, $storeId)
            ->willReturn(-22.48);
        $this->config->method('getMpCategory')->willReturn('clothing');

        $result = $this->request->addItemsDiscount($orderAdapter, $discountAmount, $storeId);

        $this->assertEquals(-22.48, $result[0]['unit_price']);
    }
}
