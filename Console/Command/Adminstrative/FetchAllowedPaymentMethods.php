<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\PaymentMagento\Console\Command\Adminstrative;

use Magento\Framework\App\State;
use MercadoPago\PaymentMagento\Model\Console\Command\Adminstrative\FetchPaymentMethods;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line for fetch allowed payment methods data.
 */
class FetchAllowedPaymentMethods extends Command
{
    /**
     * Store Id.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var FetchPaymentMethods
     */
    protected $fetchPaymentMethods;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State               $state
     * @param FetchPaymentMethods $fetchPaymentMethods
     */
    public function __construct(
        State $state,
        FetchPaymentMethods $fetchPaymentMethods
    ) {
        $this->state = $state;
        $this->fetchPaymentMethods = $fetchPaymentMethods;
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
        $this->fetchPaymentMethods->setOutput($output);

        $storeId = $input->getArgument(self::STORE_ID);

        return $this->fetchPaymentMethods->fetch($storeId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:admin:fetch_allowed_payment_methods');
        $this->setDescription('Fetch Allowed Payment Methods');
        $this->setDefinition(
            [new InputArgument(self::STORE_ID, InputArgument::OPTIONAL, 'Store Id')]
        );
        parent::configure();
    }
}
