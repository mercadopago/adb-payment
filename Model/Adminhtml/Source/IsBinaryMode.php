<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

/**
 * Payment Is Binary Mode options.
 */
class IsBinaryMode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * To Options Array.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 0,
                'label' => __('Yes, Processed Order Synchronous'),
            ],
            [
                'value' => 1,
                'label' => __('No, Processed Order Asynchronous'),
            ],
        ];
    }
}