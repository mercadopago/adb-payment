<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Color Pick Element for Jquery.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class ColorPicker extends Field
{
    /**
     * Get Element Html used to get color from color picker.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        AbstractElement $element
    ) {
        $html = $element->getElementHtml();
        $value = $this->escapeHtml($element->getData('value'));

        $html .= '<script type="text/javascript">
            require(["jquery","jquery/colorpicker/js/colorpicker"], function ($) {
                $(document).ready(function () {
                    var thisElement = $("#'.$element->getHtmlId().'");
                    thisElement.css("backgroundColor", "'.$value.'");
                    thisElement.ColorPicker({
                        color: "'.$value.'",
                        onChange: function (hsb, hex, rgb) {
                            thisElement.css("backgroundColor", "#" + hex).val("#" + hex);
                        }
                    });
                });
            });
            </script>';

        return $html;
    }
}
