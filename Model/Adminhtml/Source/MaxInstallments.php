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
 * Mercado Pago max installments options.
 */
class MaxInstallments implements ArrayInterface
{
    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            '1'     => __('1 installment'),
            '2'     => __('2 installments'),
            '3'     => __('3 installments'),
            '4'     => __('4 installments'),
            '5'     => __('5 installments'),
            '6'     => __('6 installments'),
            '7'     => __('7 installments'),
            '8'     => __('8 installments'),
            '9'     => __('9 installments'),
            '10'    => __('10 installments'),
            '11'    => __('11 installments'),
            '12'    => __('12 installments'),
            '13'    => __('13 installments'),
            '14'    => __('14 installments'),
            '15'    => __('15 installments'),
            '16'    => __('16 installments'),
            '17'    => __('17 installments'),
            '18'    => __('18 installments'),
            '19'    => __('19 installments'),
            '20'    => __('20 installments'),
            '21'    => __('21 installments'),
            '22'    => __('22 installments'),
            '23'    => __('23 installments'),
            '24'    => __('24 installments'),
            '25'    => __('25 installments'),
            '26'    => __('26 installments'),
            '27'    => __('27 installments'),
            '28'    => __('28 installments'),
            '29'    => __('29 installments'),
            '30'    => __('30 installments'),
            '31'    => __('31 installments'),
            '32'    => __('32 installments'),
            '33'    => __('33 installments'),
            '34'    => __('34 installments'),
            '35'    => __('35 installments'),
            '36'    => __('36 installments'),
        ];
    }
}
