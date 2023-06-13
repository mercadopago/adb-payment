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
 * Payment by Pix expiry options on Mercado Pago.
 */
class PixExpiration implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '15'    => __('15 minutes'),
            '30'    => __('30 minutes - recommended'),
            '60'    => __('1 hour'),
            '720'   => __('12 hours'),
            '1440'  => __('24 hours'),
        ];
    }
}
