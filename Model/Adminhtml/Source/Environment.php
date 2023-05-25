<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Mercado Pago environment options.
 */
class Environment implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'production' => __('Production'),
            'sandbox'    => __('Sandbox - Testing environment'),
        ];
    }
}
