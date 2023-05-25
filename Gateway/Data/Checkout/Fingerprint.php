<?php

namespace MercadoPago\AdbPayment\Gateway\Data\Checkout;

use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class Figerprint
 */

class Fingerprint extends PaymentHelper {
/**
     * Get finger print link
     *
     * @param string $localization
     * @return string
     */
    public function getFingerPrintLink($localization)
    {
        $siteId = [
            'MLA' => 'https://www.mercadopago.com.ar/ayuda/terminos-y-politicas_194',
            'MLB' => 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194',
            'MLC' => 'https://www.mercadopago.cl/ayuda/terminos-y-politicas_194',
            'MLM' => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
            'MLU' => 'https://www.mercadopago.com.uy/ayuda/terminos-y-politicas_194',
            'MPE' => 'https://www.mercadopago.com.pe/ayuda/terminos-y-politicas_194',
            'MCO' => 'https://www.mercadopago.com.co/ayuda/terminos-y-politicas_194',
        ];

        if (array_key_exists($localization, $siteId)) {
            return $siteId[$localization];
        }

        return $siteId['MLA'];
    }

}
