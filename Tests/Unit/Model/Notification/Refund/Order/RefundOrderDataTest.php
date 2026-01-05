<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Notification\Refund\Order;

use MercadoPago\AdbPayment\Model\Notification\Refund\Order\RefundOrderData;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RefundOrderData DTO.
 */
class RefundOrderDataTest extends TestCase
{
    /**
     * Data provider for constructor and getters tests.
     */
    public function refundDataProvider(): array
    {
        return [
            'processed refund' => [
                'id' => 'REFUND123',
                'amount' => '100.50',
                'status' => 'processed',
                'notificationId' => 'PPORD456',
                'source' => 'mp-op-pp-order-api',
            ],
            'processing refund' => [
                'id' => 'REFUND789',
                'amount' => '50.00',
                'status' => 'processing',
                'notificationId' => 'PPORD111',
                'source' => 'merchant',
            ],
            'failed refund' => [
                'id' => 'REFUND000',
                'amount' => '25.99',
                'status' => 'failed',
                'notificationId' => 'PPORD222',
                'source' => '',
            ],
        ];
    }

    /**
     * @dataProvider refundDataProvider
     */
    public function testGettersReturnCorrectValues(
        string $id,
        string $amount,
        string $status,
        string $notificationId,
        string $source
    ): void {
        $refundData = new RefundOrderData($id, $amount, $status, $notificationId, $source);

        $this->assertEquals($id, $refundData->getId());
        $this->assertEquals($amount, $refundData->getAmount());
        $this->assertEquals($notificationId, $refundData->getNotificationId());
        $this->assertEquals($source, $refundData->getSource());
    }

    /**
     * Data provider for status check tests.
     */
    public function statusCheckProvider(): array
    {
        return [
            'processed status' => ['processed', true, false],
            'processing status' => ['processing', false, false],
            'failed status' => ['failed', false, true],
            'unknown status' => ['unknown', false, false],
            'empty status' => ['', false, false],
        ];
    }

    /**
     * @dataProvider statusCheckProvider
     */
    public function testStatusCheckMethods(
        string $status,
        bool $expectedIsProcessed,
        bool $expectedIsFailed
    ): void {
        $refundData = new RefundOrderData('ID', '100', $status, 'NOTIF', 'source');

        $this->assertEquals($expectedIsProcessed, $refundData->isProcessed());
        $this->assertEquals($expectedIsFailed, $refundData->isFailed());
    }

    public function testSourceCanBeNullable(): void
    {
        $refundData = new RefundOrderData('ID', '100', 'processed', 'NOTIF', '');
        
        $this->assertEquals('', $refundData->getSource());
    }
}

