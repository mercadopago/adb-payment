<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model;

use Magento\Framework\Model\AbstractModel;
use MercadoPago\AdbPayment\Api\Data\QuoteMpPaymentInterface;
use \MercadoPago\AdbPayment\Model\ResourceModel\QuoteMpPayment as ResourceModel;

class QuoteMpPayment extends AbstractModel implements QuoteMpPaymentInterface
{

    protected $_idFieldName = self::ENTITY_ID;

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->_getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId()
    {
        return $this->_getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentId()
    {
        return $this->_getData(self::PAYMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentId($paymentId)
    {
        return $this->setData(self::PAYMENT_ID, $paymentId);
    }

    /**
     * @inheritDoc
     */
    public function getThreeDsExternalResourceUrl()
    {
        return $this->_getData(self::THREE_DS_EXT_RESOURCE_URL);
    }

    /**
     * @inheritDoc
     */
    public function setThreeDsExternalResourceUrl($threeDsExternalResourceUrl)
    {
        return $this->setData(self::THREE_DS_EXT_RESOURCE_URL, $threeDsExternalResourceUrl);
    }

    /**
     * @inheritDoc
     */
    public function getThreeDsCreq()
    {
        return $this->_getData(self::THREE_DS_CREQ);
    }

    /**
     * @inheritDoc
     */
    public function setThreeDsCreq($threeDsCreq)
    {
        return $this->setData(self::THREE_DS_CREQ, $threeDsCreq);
    }

}