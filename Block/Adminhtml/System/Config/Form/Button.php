<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use MercadoPago\AdbPayment\Gateway\Config\Config;

/**
 * Button Element.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Button extends Field
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Config           $config
     * @param Context          $context
     * @param RequestInterface $request
     * @param array            $data
     */
    public function __construct(
        Config $config,
        Context $context,
        RequestInterface $request,
        array $data = []
    ) {
        $this->request = $request;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Set template.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MercadoPago_AdbPayment::system/config/form/field/button.phtml');
    }

    /**
     * Get Web Site Id.
     *
     * @return int|null
     */
    public function getWebsiteId()
    {
        return $this->request->getParam('website') ?: null;
    }

    /**
     * Get Web Site Id.
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->request->getParam('store') ?: null;
    }

    /**
     * Generate button html.
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $storeId = $this->getStoreId();

        if (!$storeId) {
            $storeId = $this->getWebsiteId();
        }

        $siteId = $this->config->getMpSiteId($storeId);
        $mpWebSite = $this->config->getMpWebSiteBySiteId($siteId);
        $url = $mpWebSite.'costs-section#from-section=menu';

        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id'      => 'installments_button',
                'label'   => __('Set up installments and interest'),
                'onclick' => 'javascript:window.open(\''.$url.'\')',
            ]
        );

        return $button->toHtml();
    }

    /**
     * Render button.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Return element html.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
