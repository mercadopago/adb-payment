<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Order\ValidateUpdateStatus\Interfaces;

interface ValidateOrderStatusInterface {

   /**
     * MP Status approved
   */
   const MP_STATUS_APPROVED = 'approved';

      /**
     * MP Status In Mediation
   */
   const MP_STATUS_IN_MEDIATION = 'in_mediation';

      /**
     * MP Status In Process
   */
   const MP_STATUS_IN_PROCCESS = 'in_process';

      /**
     * MP Status Pending
   */
   const MP_STATUS_PENDING = 'pending';

      /**
     * MP Status Authorized
   */
   const MP_STATUS_AUTHORIZED = 'authorized';

      /**
     * MP Status Refunded
   */
   const MP_STATUS_REFUNDED = 'refunded';

      /**
     * MP Status Charged Back
   */
   const MP_STATUS_CHARGED_BACK = 'charged_back';

      /**
     * MP Status Cancelled
   */
   const MP_STATUS_CANCELLED = 'cancelled';

      /**
     * MP Status Rejected
   */
   const MP_STATUS_REJECTED = 'rejected';

   /**
     * Get valid MP status update.
     *
     * @return []
   */
    public function getMpListStatus();

    /**
     * Get adobe status.
     *
     * @return string
    */
    public function getAdobeOrderStatus();

 }