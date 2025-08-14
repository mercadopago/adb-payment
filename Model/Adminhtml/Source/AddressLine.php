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
 * Address line options for configuration.
 */
class AddressLine implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => __('Address Line 1')],
            ['value' => '1', 'label' => __('Address Line 2')],
            ['value' => '2', 'label' => __('Address Line 3')],
            ['value' => '3', 'label' => __('Address Line 4')],
        ];
    }
}
