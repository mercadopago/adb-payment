<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\ResourceModel\QuoteMpPayment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MercadoPago\AdbPayment\Model\QuoteMpPayment as Model;
use MercadoPago\AdbPayment\Model\ResourceModel\QuoteMpPayment as ResourceModel;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'mercadopago_adbpayment_quote_mp_payment_collection';
    protected $_eventObject = 'quote_mp_payment_collection';
    
    protected function _construct()
    {
        $this->_init(
            Model::class,
            ResourceModel::class
        );
    }
}