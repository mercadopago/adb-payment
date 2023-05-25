<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Payment processing options.
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
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
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorization for future capture'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorization for immediate capture'),
            ],

        ];
    }
}
