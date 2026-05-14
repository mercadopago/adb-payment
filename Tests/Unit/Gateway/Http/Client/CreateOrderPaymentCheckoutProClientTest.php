<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Gateway\Http\Client\CreateOrderPaymentCheckoutProClient;
use Magento\Payment\Model\Method\Logger;
use MercadoPago\AdbPayment\Gateway\Config\Config;
use Magento\Framework\Serialize\Serializer\Json;

class CreateOrderPaymentCheckoutProClientTest extends TestCase
{
    /**
     * @return CreateOrderPaymentCheckoutProClient
     */
    private function getTestClass(): CreateOrderPaymentCheckoutProClient
    {
        $logger = $this->createMock(Logger::class);
        $config = $this->createMock(Config::class);
        $json = $this->createMock(Json::class);
        return new CreateOrderPaymentCheckoutProClient($logger, $config, $json);
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function testCalculateDiscountAmountWithDiscount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50, 'quantity' => 2],
            ['id' => 'item2', 'unit_price' => 30, 'quantity' => 1],
        ];
        $transactionAmount = 120.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(3, $result);
        $discountItem = $result[2];
        $this->assertEquals('store_discount', $discountItem['id']);
        $this->assertEquals(-10.0, $discountItem['unit_price']);
        $this->assertEquals(1, $discountItem['quantity']);
    }

    /**
     * @return void
     */
    public function testPositiveAdjustmentWhenItemsSumLessThanTransactionAmount()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'product', 'unit_price' => 22, 'quantity' => 1],
            ['id' => 'discount', 'unit_price' => -5.5, 'quantity' => 1],
            ['id' => 'shipping', 'unit_price' => 2990, 'quantity' => 1],
        ];
        $transactionAmount = 3007.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(4, $result);
        $adjustmentItem = $result[3];
        $this->assertEquals('store_adjustment', $adjustmentItem['id']);
        $this->assertEquals('Store Adjustment', $adjustmentItem['title']);
        $this->assertEquals(0.5, $adjustmentItem['unit_price']);
        $this->assertEquals(1, $adjustmentItem['quantity']);
        $finalTotal = array_sum(array_map(
            fn($i) => $i['unit_price'] * ($i['quantity'] ?? 1),
            $result
        ));
        $this->assertEquals($transactionAmount, round($finalTotal, 2));
    }

    /**
     * @return void
     */
    public function testZeroDecimalCurrencyFractionalDiscountReconciliation()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'product', 'unit_price' => 8990, 'quantity' => 1],
            ['id' => 'discount', 'unit_price' => -2248.5, 'quantity' => 1],
            ['id' => 'shipping', 'unit_price' => 2990, 'quantity' => 1],
        ];
        $transactionAmount = 9732.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(4, $result);
        $adjustmentItem = $result[3];
        $this->assertEquals('store_adjustment', $adjustmentItem['id']);
        $this->assertEquals('Store Adjustment', $adjustmentItem['title']);
        $this->assertEquals(0.5, $adjustmentItem['unit_price']);
        $finalTotal = array_sum(array_map(
            fn($i) => $i['unit_price'] * ($i['quantity'] ?? 1),
            $result
        ));
        $this->assertEquals($transactionAmount, round($finalTotal, 2));
    }

    /**
     * @return void
     */
    public function testDecimalCurrencyNegativeAdjustmentStillWorks()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 33.33, 'quantity' => 2],
            ['id' => 'item2', 'unit_price' => 15.75, 'quantity' => 1],
        ];
        $transactionAmount = 76.64;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(3, $result);
        $discountItem = $result[2];
        $this->assertEquals('store_discount', $discountItem['id']);
        $this->assertLessThan(0, $discountItem['unit_price']);
        $finalTotal = array_sum(array_map(
            fn($i) => $i['unit_price'] * ($i['quantity'] ?? 1),
            $result
        ));
        $this->assertEquals($transactionAmount, round($finalTotal, 2));
    }

    /**
     * @return void
     */
    public function testDecimalCurrencyExactMatchNoAdjustment()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'product', 'unit_price' => 89.90, 'quantity' => 1],
            ['id' => 'discount', 'unit_price' => -22.48, 'quantity' => 1],
            ['id' => 'shipping', 'unit_price' => 29.90, 'quantity' => 1],
        ];
        $transactionAmount = 97.32;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(3, $result);
    }

    /**
     * @return void
     */
    public function testCalculateDiscountAmountWithMissingUnitPrice()
    {
        $client = $this->getTestClass();
        $items = [
            ['id' => 'item1', 'unit_price' => 50, 'quantity' => 2],
            ['id' => 'item2', 'quantity' => 1],
        ];
        $transactionAmount = 100.0;
        $result = $this->invokePrepareItemsWithDiscount($client, $items, $transactionAmount);
        $this->assertCount(1, $result);
        $this->assertEquals('item1', $result[0]['id']);
    }

    /**
     * @return void
     */
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
        $this->assertEquals(-$discountValue, $discountItem['unit_price']);
        $this->assertEquals(1, $discountItem['quantity']);
    }

    /**
     * @param CreateOrderPaymentCheckoutProClient $client
     * @param array $items
     * @param float $transactionAmount
     * @return array
     */
    private function invokePrepareItemsWithDiscount($client, $items, $transactionAmount)
    {
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('prepareItemsWithDiscount');
        $method->setAccessible(true);
        return $method->invoke($client, $items, $transactionAmount);
    }
}
