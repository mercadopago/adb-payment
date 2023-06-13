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
 * Redirection type options in Checkout Pro.
 */
class TypeRedirect implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            'modal'     => __('Modal window in store environment'),
            'redirect'  => __('Redirection to Mercado Pago environment'),
        ];
    }
}
