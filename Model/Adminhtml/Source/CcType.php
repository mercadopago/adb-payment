<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Model\Adminhtml\Source;

use Magento\Payment\Model\Source\Cctype as MagentoCcType;

/**
 * Card Options on Mercado Pago.
 */
class CcType extends MagentoCcType
{
    /**
     * Get Allwed Types.
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return [
            'VI',
            'MC',
            'ELO',
            'AE',
            'MCD',
            'HI',
            'VID',
            'ELOD',
            'CABAL',
            'DEBCABAL',
            'CMR',
            'CENCOSUD',
            'DN',
            'TARSHOP',
            'ARGENCARD',
            'NARANJA',
            'MAESTRO',
            'TENGO',
            'SODEXO',
            'CARNET',
            'TOKA',
            'MERCADOPAGOCARD',
            'EDENRED',
        ];
    }

    /**
     * Returns Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $allowed = $this->getAllowedTypes();
        $options = [];

        foreach ($this->_paymentConfig->getCcTypes() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }
}
