<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Notification\Refund\Order;

/**
 * DTO for Order API refund notification data.
 */
class RefundOrderData
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $notificationId;

    /**
     * @var string
     */
    private $source;

    /**
     * @param string $id
     * @param string $amount
     * @param string $status
     * @param string $notificationId
     * @param string $source
     */
    public function __construct(
        string $id,
        string $amount,
        string $status,
        string $notificationId,
        string $source
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->status = $status;
        $this->notificationId = $notificationId;
        $this->source = $source;
    }

    /**
     * Get refund ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get refund amount.
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get notification ID.
     *
     * @return string
     */
    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    /**
     * Check if refund is processed.
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if refund failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get refund source.
     *
     * @return string
     */
    public function getSource(): ?string
    {
        return $this->source;
    }
}

