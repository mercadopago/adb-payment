<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use Magento\Sales\Model\Order;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateOrderStatusResponse;

class ValidateNotValidOrderStatus extends ValidateOrderStatus {

    /**
     * @var string
     */
    protected $orderStatus;

    /**
     * Constructor.
     *
     * @param string $orderStatus
     */
    public function __construct(
        string $orderStatus
    ) {
        $this->orderStatus = $orderStatus;
    }

    /**
    * MP Status list
    */
    private array $notValidOrderStatusCanUpdateStatus = [];
   
    public function getMpListStatus(){
        return $this->notValidOrderStatusCanUpdateStatus;
    }

    public function getAdobeOrderStatus(){
        return $this->orderStatus;
    }

    public function verifyStatus(string $mpStatus)
    {
        $response = new ValidateOrderStatusResponse();
        $response->setIsValid(false);
        $response->setMessage('Cannot update status (Adobe) ' . $this->orderStatus);
        $response->setCode(200);
        return $response;
    }

}
