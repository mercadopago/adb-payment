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
use MercadoPago\PaymentMagento\Gateway\Config\Config as PaymentConfig;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Sales\Model\Order;

/**
 * Success page additional information.
 */
class Additional extends Template
{

    /**
     * Status Approved.
     */
    public const MP_STATUS = 'mp_status';

    /**
     * Status Approved.
     */
    public const STATUS_APPROVED = 'approved';

    /**
     * Title default.
     */
    public const TITLE_DEFAULT = 'Thank you for your purchase!';

    /**
     * Title for unapproved orders.
     */
    public const TITLE_PROCESSING_ORDER = 'We are processing your payment';

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderConfig
     */
    protected $orderConfig;

    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;
    
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param OrderConfig   $orderConfig
     * @param PaymentConfig $paymentConfig
     * @param HttpContext   $httpContext
     * @param array         $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderConfig $orderConfig,
        PaymentConfig $paymentConfig,
        HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->orderConfig = $orderConfig;
        $this->paymentConfig = $paymentConfig;
        $this->httpContext = $httpContext;

        if ($this->getMethodCode() === 'mercadopago_paymentmagento_boleto') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/boleto.phtml');
        } elseif ($this->getMethodCode() === 'mercadopago_paymentmagento_pec') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/pec.phtml');
        } elseif ($this->getMethodCode() === 'mercadopago_paymentmagento_pix') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/pix.phtml');
        } elseif ($this->getMethodCode() === 'mercadopago_paymentmagento_twocc') {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/twocc.phtml');
        } elseif (str_contains($this->getMethodCode(), 'mercadopago_paymentmagento')) {
            $this->setTemplate('MercadoPago_PaymentMagento::order/success/default.phtml');
        }
    }

    /**
     * Get OrderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getIncrementId();
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
        return $this->getPayment()->getInfoInstance()->getAdditionalInformation($info);
    }

    /**
     * Statement Descriptor.
     *
     * @return string
     */
    public function getStatementDescriptor()
    {
        $storeId = (int) $this->checkoutSession->getLastRealOrder()->getStoreId();

        return $this->paymentConfig->getStatementDescriptor($storeId);
    }

    /**
     * Title.
     *
     * @return string
     */
    public function getTitleByPaymentStatus()
    {
        $status = $this->getInfo(self::MP_STATUS);
        
        if ($this->getMethodCode() === 'mercadopago_paymentmagento_twocc' 
            && strcasecmp(isset($status) ? $status : '', self::STATUS_APPROVED) <> 0
        ) {
            return self::TITLE_PROCESSING_ORDER;
        }

        return self::TITLE_DEFAULT;
    }
}
