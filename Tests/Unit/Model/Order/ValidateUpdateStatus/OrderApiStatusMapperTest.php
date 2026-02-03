<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Model\Order\ValidateUpdateStatus;

use PHPUnit\Framework\TestCase;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\OrderApiStatusMapper;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;

/**
 * Test for OrderApiStatusMapper
 */
class OrderApiStatusMapperTest extends TestCase
{
    /**
     * Data provider for Order API to Payment API status mapping
     *
     * @return array
     */
    public function orderApiStatusMappingProvider(): array
    {
        return [
            'processing to pending' => ['processing', ValidateOrderStatusInterface::MP_STATUS_PENDING],
            'action_required to pending' => ['action_required', ValidateOrderStatusInterface::MP_STATUS_PENDING],
            'processed to approved' => ['processed', ValidateOrderStatusInterface::MP_STATUS_APPROVED],
            'canceled to cancelled' => ['canceled', ValidateOrderStatusInterface::MP_STATUS_CANCELLED],
            'failed to rejected' => ['failed', ValidateOrderStatusInterface::MP_STATUS_REJECTED],
            'expired to cancelled' => ['expired', ValidateOrderStatusInterface::MP_STATUS_CANCELLED],
            'refunded to refunded' => ['refunded', ValidateOrderStatusInterface::MP_STATUS_REFUNDED],
            'in_review to pending' => ['in_review', ValidateOrderStatusInterface::MP_STATUS_PENDING],
        ];
    }

    /**
     * Data provider for Payment API status (should remain unchanged)
     *
     * @return array
     */
    public function paymentApiStatusProvider(): array
    {
        return [
            'approved remains approved' => ['approved', 'approved'],
            'pending remains pending' => ['pending', 'pending'],
            'rejected remains rejected' => ['rejected', 'rejected'],
            'cancelled remains cancelled' => ['cancelled', 'cancelled'],
            'refunded remains refunded' => ['refunded', 'refunded'],
            'in_mediation remains in_mediation' => ['in_mediation', 'in_mediation'],
            'charged_back remains charged_back' => ['charged_back', 'charged_back'],
            'in_process remains in_process' => ['in_process', 'in_process'],
            'authorized remains authorized' => ['authorized', 'authorized'],
        ];
    }

    /**
     * Data provider for isOrderApiStatus method
     *
     * @return array
     */
    public function isOrderApiStatusProvider(): array
    {
        return [
            'processing is Order API' => ['processing', true],
            'action_required is Order API' => ['action_required', true],
            'processed is Order API' => ['processed', true],
            'canceled is Order API' => ['canceled', true],
            'failed is Order API' => ['failed', true],
            'expired is Order API' => ['expired', true],
            'refunded is Order API' => ['refunded', true],
            'in_review is Order API' => ['in_review', true],
            'approved is NOT Order API' => ['approved', false],
            'pending is NOT Order API' => ['pending', false],
            'rejected is NOT Order API' => ['rejected', false],
        ];
    }

    /**
     * Test mapping Order API statuses to Payment API statuses
     *
     * @dataProvider orderApiStatusMappingProvider
     */
    public function testMapOrderApiStatusToPaymentApiStatus(string $orderApiStatus, string $expectedPaymentApiStatus)
    {
        $result = OrderApiStatusMapper::mapToPaymentApiStatus($orderApiStatus);
        $this->assertEquals($expectedPaymentApiStatus, $result);
    }

    /**
     * Test that Payment API statuses remain unchanged
     *
     * @dataProvider paymentApiStatusProvider
     */
    public function testPaymentApiStatusRemainsUnchanged(string $paymentApiStatus, string $expectedStatus)
    {
        $result = OrderApiStatusMapper::mapToPaymentApiStatus($paymentApiStatus);
        $this->assertEquals($expectedStatus, $result);
    }

    /**
     * Test isOrderApiStatus method
     *
     * @dataProvider isOrderApiStatusProvider
     */
    public function testIsOrderApiStatus(string $status, bool $expectedResult)
    {
        $result = OrderApiStatusMapper::isOrderApiStatus($status);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test that unmapped status sends metric when MetricsClient is provided
     */
    public function testUnmappedStatusSendsMetric()
    {
        $metricsClient = $this->createMock(MetricsClient::class);
        $unmappedStatus = 'unknown_status';

        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_status_unmapped',
                $unmappedStatus,
                'Status not mapped in status machine'
            );

        $result = OrderApiStatusMapper::mapToPaymentApiStatus($unmappedStatus, $metricsClient);
        $this->assertEquals($unmappedStatus, $result);
    }

    /**
     * Test that unmapped status does not send metric when MetricsClient is null
     */
    public function testUnmappedStatusDoesNotSendMetricWhenClientIsNull()
    {
        $unmappedStatus = 'unknown_status';
        $result = OrderApiStatusMapper::mapToPaymentApiStatus($unmappedStatus, null);
        // Should return original status when not mapped and no metrics client
        $this->assertEquals($unmappedStatus, $result);
    }

    /**
     * Test that mapped status does not send metric
     */
    public function testMappedStatusDoesNotSendMetric()
    {
        $metricsClient = $this->createMock(MetricsClient::class);
        $mappedStatus = 'processing';

        $metricsClient->expects($this->never())
            ->method('sendEvent');

        $result = OrderApiStatusMapper::mapToPaymentApiStatus($mappedStatus, $metricsClient);
        $this->assertEquals(ValidateOrderStatusInterface::MP_STATUS_PENDING, $result);
    }

    /**
     * Test that metric failure does not break the flow
     */
    public function testMetricFailureDoesNotBreakFlow()
    {
        $metricsClient = $this->createMock(MetricsClient::class);
        $unmappedStatus = 'unknown_status';

        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->willThrowException(new \Exception('Metric error'));

        // Should not throw exception, should return original status
        $result = OrderApiStatusMapper::mapToPaymentApiStatus($unmappedStatus, $metricsClient);
        $this->assertEquals($unmappedStatus, $result);
    }

    /**
     * Test that Payment API statuses do not send metric
     *
     * @dataProvider paymentApiStatusProvider
     */
    public function testPaymentApiStatusDoesNotSendMetric(string $paymentApiStatus, string $expectedStatus)
    {
        $metricsClient = $this->createMock(MetricsClient::class);

        // Metrics client should NEVER be called for known Payment API statuses
        $metricsClient->expects($this->never())
            ->method('sendEvent');

        $result = OrderApiStatusMapper::mapToPaymentApiStatus($paymentApiStatus, $metricsClient);
        $this->assertEquals($expectedStatus, $result);
    }

    /**
     * Test that only unknown statuses (not Order API, not Payment API) send metric
     */
    public function testOnlyUnknownStatusesSendMetric()
    {
        $metricsClient = $this->createMock(MetricsClient::class);

        // Test unknown status - should send metric
        $metricsClient->expects($this->once())
            ->method('sendEvent')
            ->with(
                'magento_order_status_unmapped',
                'weird_status',
                'Status not mapped in status machine'
            );

        $result = OrderApiStatusMapper::mapToPaymentApiStatus('weird_status', $metricsClient);
        $this->assertEquals('weird_status', $result);
    }
}
