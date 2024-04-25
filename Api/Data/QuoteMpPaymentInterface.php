<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api\Data;

interface QuoteMpPaymentInterface
{
    /**
     * Entity ID.
     */
    const ENTITY_ID = 'entity_id';

    /**
     * Quote ID.
     */
    const QUOTE_ID = 'quote_id';

    /**
     * Payment ID
     */
    const PAYMENT_ID = 'payment_id';

    /**
     * 3DS External Resource URL.
     */
    const THREE_DS_EXT_RESOURCE_URL = 'three_ds_external_resource_url';

    /**
     * 3DS Creq.
     */
    const THREE_DS_CREQ = 'three_ds_creq';

    /**
     * Get Entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set Entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get Quote ID
     *
     * @return int|null
     */
    public function getQuoteId();

    /**
     * Set Quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Get Payment ID
     *
     * @return int|null
     */
    public function getPaymentId();

    /**
     * Set Payment ID
     *
     * @param int $paymentId
     * @return $this
     */
    public function setPaymentId($paymentId);
    
    /**
     * Get 3DS External Resource URL
     *
     * @return string|null
     */
    public function getThreeDsExternalResourceUrl();

    /**
     * Set 3DS External Resource URL
     *
     * @param string $threeDsExtResourceUrl
     * @return $this
     */
    public function setThreeDsExternalResourceUrl($threeDsExtResourceUrl);

    /**
     * Get 3DS Creq
     *
     * @return string|null
     */
    public function getThreeDsCreq();

    /**
     * Set 3DS Creq
     *
     * @param string $threeDsCreq
     * @return $this
     */
    public function setThreeDsCreq($threeDsCreq);
}