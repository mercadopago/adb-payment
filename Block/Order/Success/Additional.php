<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Block\Order\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order\Config;

/**
 * Success page additional information.
 */
class Additional extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @param Context     $context
     * @param Session     $checkoutSession
     * @param Config      $orderConfig
     * @param HttpContext $httpContext
     * @param array       $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->orderConfig = $orderConfig;
        $this->httpContext = $httpContext;

        if ($this->getMethodCode() === 'mercadopago_paymentmagento_boleto') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/boleto.phtml');
        } elseif ($this->getMethodCode() === 'mercadopago_paymentmagento_pec') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/pec.phtml');
        } elseif ($this->getMethodCode() === 'mercadopago_paymentmagento_pix') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/pix.phtml');
        } elseif (str_contains($this->getMethodCode(), 'mercadopago_paymentmagento')) {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/default.phtml');
        }
    }

    /**
     * Get Payment.
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getPayment()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getPayment()->getMethodInstance();
    }

    /**
     * Method Code.
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getPayment()->getCode();
    }

    /**
     * Info payment.
     *
     * @param string $info
     *
     * @return string
     */
    public function getInfo(string $info)
    {
        return  $this->getPayment()->getInfoInstance()->getAdditionalInformation($info);
    }
}
