<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCheckoutProClient;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;

class CreateOrderPaymentCheckoutProClientTest extends TestCase
{
    private function getTestClass(): CreateOrderPaymentCheckoutProClient
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        return new CreateOrderPaymentCheckoutProClient($logger, $config, $json);
    }

    public function testCalculateDiscountAmountWithoutQuantityAndNoDiscount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50],
            ['id' => 'item2', 'unit_price' => 30],
        ];
        $transactionAmount = 80.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(2, $result);
        $this->assertEquals($items, $result);
    }

    public function testCalculateDiscountAmountWithQuantityAndNoDiscount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50, 'quantity' => 2],
            ['id' => 'item2', 'unit_price' => 30, 'quantity' => 1],
        ];
        $transactionAmount = 130.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(2, $result);
        $this->assertEquals($items, $result);
    }

    public function testCalculateDiscountAmountWithDiscount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50, 'quantity' => 2],
            ['id' => 'item2', 'unit_price' => 30, 'quantity' => 1],
        ];
        $transactionAmount = 120.0; // desconto de 10
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(3, $result);
        $discountItem = $result[2];
        $this->assertEquals('store_discount', $discountItem['id']);
        $this->assertEquals('Store Discount', $discountItem['title']);
        $this->assertEquals(-10.0, $discountItem['unit_price']);
        $this->assertEquals(1, $discountItem['quantity']);
    }

    public function testCalculateDiscountAmountWithMissingUnitPrice()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50, 'quantity' => 2],
            ['id' => 'item2', 'quantity' => 1], // sem unit_price
        ];
        $transactionAmount = 100.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(1, $result); // só o item válido
        $this->assertEquals('item1', $result[0]['id']);
    }

    public function testCalculateDiscountAmountWithDecimalValuesAndPercentageDiscount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 33.33, 'quantity' => 2],
            ['id' => 'item2', 'unit_price' => 15.75, 'quantity' => 1],
        ];
        $total = 33.33 * 2 + 15.75;
        $discountPercent = 0.07;
        $discountValue = round($total * $discountPercent, 2);
        $transactionAmount = round($total - $discountValue, 2);
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(3, $result);
        $discountItem = $result[2];
        $this->assertEquals('store_discount', $discountItem['id']);
        $this->assertEquals('Store Discount', $discountItem['title']);
        $this->assertEquals(-$discountValue, $discountItem['unit_price'], '');
        $this->assertEquals(1, $discountItem['quantity']);
    }

    private function invokePrepareItemsWithDiscount($client, $items, $transactionAmount)
    {
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('prepareItemsWithDiscount');
        $method->setAccessible(true);
        return $method->invoke($client, $items, $transactionAmount);
    }
} 