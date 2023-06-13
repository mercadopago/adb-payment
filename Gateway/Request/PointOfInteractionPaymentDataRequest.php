<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Gateway requests for point of interaction data in method Pix.
 */
class PointOfInteractionPaymentDataRequest implements BuilderInterface
{
    /**
     * Point Of Interaction block name.
     */
    public const POINT_OF_INTERACTION = 'point_of_interaction';

    /**
     * Type block name.
     */
    public const TYPE = 'type';

    /**
     * Type Checkout value.
     */
    public const TYPE_CHECKOUT = 'CHECKOUT';

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $result = [
            self::POINT_OF_INTERACTION  => [
                self::TYPE => self::TYPE_CHECKOUT,
            ],
        ];

        return $result;
    }
}
