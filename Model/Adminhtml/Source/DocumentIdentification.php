<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Identification Document collection options.
 */
class DocumentIdentification implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            null       => __('Please select'),
            'customer' => __('by customer form (taxvat - customer account)'),
            'address'  => __('by address form (vat_id - checkout)'),
        ];
    }
}
