<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Api;

/**
 * Interface for creating the vault on Mercado Pago.
 *
 * @api
 */
interface CreateVaultManagementInterface
{
    /**
     * Create Vault.
     *
     * @param int   $cartId
     * @param mixed $vaultData
     *
     * @return mixed
     */
    public function createVault(
        $cartId,
        $vaultData
    );
}
