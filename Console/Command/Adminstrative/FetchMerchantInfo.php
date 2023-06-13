<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Console\Command\Adminstrative;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Adminstrative\FetchMerchant;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line for fetch merchant data.
 */
class FetchMerchantInfo extends Command
{
    /**
     * Store Id.
     */
    public const STORE_ID = 'store_id';

    /**
     * @var FetchMerchant
     */
    protected $fetchMerchant;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State         $state
     * @param FetchMerchant $fetchMerchant
     */
    public function __construct(
        State $state,
        FetchMerchant $fetchMerchant
    ) {
        $this->state = $state;
        $this->fetchMerchant = $fetchMerchant;
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
        $this->fetchMerchant->setOutput($output);

        $storeId = $input->getArgument(self::STORE_ID);

        return $this->fetchMerchant->fetch($storeId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:admin:fetch_merchant_info');
        $this->setDescription('Fetch Merchant Account Information');
        $this->setDefinition(
            [new InputArgument(self::STORE_ID, InputArgument::OPTIONAL, 'Store Id')]
        );
        parent::configure();
    }
}
