<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;
use MercadoPago\AdbPayment\Model\Metrics\MetricsClient;

/**
 * Mapper to convert Order API status to Payment API status.
 * 
 * This allows the existing state machine to work without refactoring
 * by converting new Order API statuses to equivalent Payment API statuses.
 */
class OrderApiStatusMapper
{
    /**
     * Order API status: Processing
     */
    public const ORDER_STATUS_PROCESSING = 'processing';

    /**
     * Order API status: Action Required
     */
    public const ORDER_STATUS_ACTION_REQUIRED = 'action_required';

    /**
     * Order API status: Processed
     */
    public const ORDER_STATUS_PROCESSED = 'processed';

    /**
     * Order API status: Canceled
     */
    public const ORDER_STATUS_CANCELED = 'canceled';

    /**
     * Order API status: Failed
     */
    public const ORDER_STATUS_FAILED = 'failed';

    /**
     * Order API status: Expired
     */
    public const ORDER_STATUS_EXPIRED = 'expired';

    /**
     * Order API status: Refunded
     */
    public const ORDER_STATUS_REFUNDED = 'refunded';

    /**
     * Order API status: In Review
     */
    public const ORDER_STATUS_IN_REVIEW = 'in_review';

    /**
     * Order API status: In Mediation
     */
    public const ORDER_STATUS_IN_MEDIATION = 'in_mediation';

    /**
     * Order API status: Charged Back
     */
    public const ORDER_STATUS_CHARGED_BACK = 'charged_back';

    /**
     * Mapping from Order API status to Payment API status.
     *
     * @var array
     */
    private static $statusMapping = [
        // Order API Status => Payment API Status
        self::ORDER_STATUS_PROCESSING       => ValidateOrderStatusInterface::MP_STATUS_PENDING,
        self::ORDER_STATUS_ACTION_REQUIRED  => ValidateOrderStatusInterface::MP_STATUS_PENDING,
        self::ORDER_STATUS_PROCESSED        => ValidateOrderStatusInterface::MP_STATUS_APPROVED,
        self::ORDER_STATUS_CANCELED         => ValidateOrderStatusInterface::MP_STATUS_CANCELLED,
        self::ORDER_STATUS_FAILED           => ValidateOrderStatusInterface::MP_STATUS_REJECTED,
        self::ORDER_STATUS_EXPIRED          => ValidateOrderStatusInterface::MP_STATUS_CANCELLED,
        self::ORDER_STATUS_REFUNDED         => ValidateOrderStatusInterface::MP_STATUS_REFUNDED,
        self::ORDER_STATUS_IN_REVIEW        => ValidateOrderStatusInterface::MP_STATUS_PENDING,
        self::ORDER_STATUS_IN_MEDIATION     => ValidateOrderStatusInterface::MP_STATUS_IN_MEDIATION,
        self::ORDER_STATUS_CHARGED_BACK     => ValidateOrderStatusInterface::MP_STATUS_CHARGED_BACK,
    ];

    /**
     * Map Order API status to Payment API status.
     *
     * @param string $orderApiStatus Status from Order API
     * @param MetricsClient|null $metricsClient Optional metrics client to send unmapped status metric
     * @return string Equivalent Payment API status
     */
    public static function mapToPaymentApiStatus(string $orderApiStatus, ?MetricsClient $metricsClient = null): string
    {
        // Check if status is mapped
        if (isset(self::$statusMapping[$orderApiStatus])) {
            return self::$statusMapping[$orderApiStatus];
        }

        // Status not mapped - send metric if metrics client is available
        if ($metricsClient !== null) {
            try {
                $metricsClient->sendEvent(
                    'magento_order_status_unmapped',
                    $orderApiStatus,
                    'Status not mapped in status machine'
                );
            } catch (\Exception $e) {
                // Silently fail - don't break the flow if metric fails
            }
        }

        // Return original status when not mapped
        return $orderApiStatus;
    }

    /**
     * Check if status is from Order API.
     *
     * @param string $status
     * @return bool
     */
    public static function isOrderApiStatus(string $status): bool
    {
        return array_key_exists($status, self::$statusMapping);
    }
}

