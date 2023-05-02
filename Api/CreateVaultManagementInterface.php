<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Api;

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
