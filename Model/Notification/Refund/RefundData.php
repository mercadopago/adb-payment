<?php

namespace MercadoPago\AdbPayment\Model\Notification\Refund;

class RefundData {
    private int $id;
    private bool $notifying;
    private float $amount;
    private string $status;
    private string $notificationId;
    private ?string $origin;

    public function __construct(int $id, bool $notifying, float $amount, string $status, string $notificationId, string $origin = null)
    {
        $this->id = $id;
        $this->notifying = $notifying;
        $this->amount = $amount;
        $this->status = $status;
        $this->notificationId = $notificationId;
        $this->origin = $origin;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNotifying(): bool
    {
        return $this->notifying;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }
}
