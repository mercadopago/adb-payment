<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Block\Customer;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use MercadoPago\AdbPayment\Model\Ui\ConfigProviderCc;

/**
 * Block to render saved cards.
 */
class CardRenderer extends AbstractCardRenderer
{
    /**
     * Can render specified token.
     *
     * @param PaymentTokenInterface $token
     *
     * @return bool
     */
    public function canRender(PaymentTokenInterface $token): bool
    {
        return $token->getPaymentMethodCode() === ConfigProviderCc::CODE;
    }

    /**
     * Get Last Numbers.
     *
     * @return string
     */
    public function getNumberLast4Digits(): string
    {
        return $this->getTokenDetails()['card_last4'];
    }

    /**
     * Get Expiration Date.
     *
     * @return string
     */
    public function getExpDate(): string
    {
        return $this->getTokenDetails()['card_exp_month'].'/'.$this->getTokenDetails()['card_exp_year'];
    }

    /**
     * Get Icon Url.
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        return $this->getIconForType($this->getTokenDetails()['card_type'])['url'];
    }

    /**
     * Get Icon Height.
     *
     * @return int
     */
    public function getIconHeight(): int
    {
        return $this->getIconForType($this->getTokenDetails()['card_type'])['height'];
    }

    /**
     *  Get Icon Width.
     *
     * @return int
     */
    public function getIconWidth(): int
    {
        return $this->getIconForType($this->getTokenDetails()['card_type'])['width'];
    }
}
