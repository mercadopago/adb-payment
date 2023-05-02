<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Adminhtml\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Privacy Information Block.
 */
class PrivacyInformation extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Render element value.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $output = '<div class="mercadopago-featured-session">';
        // phpcs:ignore
        $output .= '<p>'.__('É necessário que na seção de Privacidade da sua loja contenha as seguintes informações do Mercado Pago sobre cookies criados e suas finalidades. Esta informação é obrigatória pela Lei Geral de Proteção de Dados, Lei nº 13.709/2018.').'</p>';
        $output .= '<p>'.__('Nome do novo cookie: "edsid"').'</p>';
        $output .= '<p>'.__('Description of use: ...').'</p>';
        $output .= '<p>'.__('Nome do novo cookie: "dsid"').'</p>';
        $output .= '<p>'.__('Description of use: ...').'</p>';
        $output .= '</div>';

        return '<div id="row_'.$element->getHtmlId().'">'.$output.'</div>';
    }
}
