<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QuoteMpPayment extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('quote_mp_payment', 'entity_id');
    }
}
