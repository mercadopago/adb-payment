<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

class ValidateOrderStatusResponse {
    private $isValid;
    private $message;
    private $code;

    public function __construct(){}

    public function setIsValid($isValid) {
        $this->isValid = $isValid;
    }

    public function getIsValid() {
        return $this->isValid;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function getCode() {
        return $this->code;
    }
}
