<?php

namespace MercadoPago\AdbPayment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Context;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderCheckoutCredits;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;

class GroupRenderer extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var ConfigProviderCheckoutCredits
     */
    protected $configProviderCheckoutCredits;

    /**
     * @var BackendAuthSession
     */
    protected $backendAuthSession;

    /**
     * GroupRenderer constructor.
     *
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ConfigProviderCheckoutCredits $configProviderCheckoutCredits
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ConfigProviderCheckoutCredits $configProviderCheckoutCredits,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->configProviderCheckoutCredits = $configProviderCheckoutCredits;
    }

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (!$this->configProviderCheckoutCredits->isActive()) {
            return ''; 
        }
        return parent::render($element);
    }
}
