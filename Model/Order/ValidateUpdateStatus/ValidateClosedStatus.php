<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;

class ValidateClosedStatus extends ValidateOrderStatus {
    
    /**
    * MP Status list
    */
    private array $closedCanUpdateStatus = [];
   
    public function getMpListStatus(){
        return $this->closedCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return Order::STATE_CLOSED;
    }

}
