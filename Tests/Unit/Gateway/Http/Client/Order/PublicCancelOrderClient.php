<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Tests\Unit\Gateway\Http\Client\Order;

use MercadoPago\AdbPayment\Gateway\Http\Client\Order\CancelOrderClient;

/**
 * Subclass with public methods for testing protected methods.
 */
class PublicCancelOrderClient extends CancelOrderClient
{
    public function normalizeCancelResponse($data): array
    {
        return parent::normalizeCancelResponse($data);
    }

    public function buildClientHeaders($storeId, ?string $idempotencyKey = null): array
    {
        return parent::buildClientHeaders($storeId, $idempotencyKey);
    }

    public function logError(string $url, ?string $request, string $error): void
    {
        parent::logError($url, $request, $error);
    }
}

