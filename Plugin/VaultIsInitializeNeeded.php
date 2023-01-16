<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Plugin;

use Magento\Vault\Model\Method\Vault;

/**
 * Plugin to disable config of is Initialize Needed.
 */
class VaultIsInitializeNeeded
{
    /**
     * Around Is Initialize Needed.
     *
     * @param Vault    $config
     * @param callable $proceed
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsInitializeNeeded(
        Vault $config,
        callable $proceed,
    ): bool {
        return false;
    }
}
