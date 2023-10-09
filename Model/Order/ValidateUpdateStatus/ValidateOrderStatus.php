<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus;

use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\ValidateOrderStatusResponse;
use MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces\ValidateOrderStatusInterface;

abstract class ValidateOrderStatus implements ValidateOrderStatusInterface {

    public function validateStatus(string $newStatus)
    {
        $validStatus = $this->getMpListStatus();

        return in_array($newStatus, $validStatus);
    }

    public function mountResponse($isValid, $mpStatus)
    {
        $response = new ValidateOrderStatusResponse();

        if(!$isValid)
        {
            $response->setIsValid($isValid);
            $response->setMessage('Status (MP) ' . $mpStatus . ' cannot update status (Adobe) ' . $this->getAdobeOrderStatus());
            $response->setCode(200);
        } else {
            $response->setIsValid($isValid);
            $response->setMessage('Status (MP) ' . $mpStatus . ' can update status (Adobe) ' . $this->getAdobeOrderStatus());
            $response->setCode(200);
        }

        return $response;
    }

    public function verifyStatus(string $mpStatus)
    {
        $result = $this->validateStatus($mpStatus);

        return $this->mountResponse($result, $mpStatus);
    }

}
