<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

use MercadoPago\AdbPayment\Gateway\Http\Client\Order\RefundOrderClient;

/**
 * Subclass with public methods for testing.
 */
class PublicRefundOrderClient extends RefundOrderClient
{
    public function sanitizeRequest(array $request): array
    {
        return parent::sanitizeRequest($request);
    }

    public function normalizeRefundResponse($data): array
    {
        return parent::normalizeRefundResponse($data);
    }

    public function buildClientHeaders($storeId, ?string $idempotencyKey = null): array
    {
        return parent::buildClientHeaders($storeId, $idempotencyKey);
    }
}

