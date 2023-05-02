<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Console\Command\Adminstrative;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Adminstrative\PaymentExpiration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line for payment expiration in Mercado Pago.
 */
class PaymentExpirations extends Command
{
    /**
     * Preference Id.
     */
    public const PREFERENCE_ID = 'preference_id';

    /**
     * Store Id.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var PaymentExpiration
     */
    protected $paymentExpiration;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State             $state
     * @param PaymentExpiration $paymentExpiration
     */
    public function __construct(
        State $state,
        PaymentExpiration $paymentExpiration
    ) {
        $this->state = $state;
        $this->paymentExpiration = $paymentExpiration;
        parent::__construct();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->paymentExpiration->setOutput($output);

        $preferenceId = $input->getArgument(self::PREFERENCE_ID);
        $storeId = $input->getArgument(self::STORE_ID);

        return $this->paymentExpiration->expire($preferenceId, $storeId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:admin:expire_payment');
        $this->setDescription('Set Expired Payment');
        $this->setDefinition(
            [
                new InputArgument(self::PREFERENCE_ID, InputArgument::REQUIRED, 'Preference Id'),
                new InputArgument(self::STORE_ID, InputArgument::REQUIRED, 'Store Id'),
            ]
        );
        parent::configure();
    }
}
