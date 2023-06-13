<?php

namespace MercadoPago\AdbPayment\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class Color extends Value {
    public function beforeSave() {
        $value = $this->getValue();

        if ($value === null || !preg_match("/^#[0-9A-Fa-f]{6}$/", $value)) {
            throw new LocalizedException(__('Please fill a valid color in the Checkout Pro configuration'));
        }

        return $this;
    }
}
