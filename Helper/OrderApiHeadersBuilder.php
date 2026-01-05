<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Helper;

use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * Helper to build headers for Order API requests.
 */
class OrderApiHeadersBuilder
{
    /**
     * Content-Type JSON header literal.
     */
    public const CONTENT_TYPE_JSON = 'Content-Type: application/json';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Build headers for Order API requests.
     *
     * @param string $storeId
     * @return array
     */
    public function buildHeaders(string $storeId): array
    {
        $baseHeaders = $this->config->getClientHeadersMpPluginsPhpSdk($storeId);

        return array_merge(
            $baseHeaders,
            [
                self::CONTENT_TYPE_JSON,
            ]
        );
    }
}
