<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Console\Command\Notification;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\FetchStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line for fetch Order Status.
 */
class FetchOrderStatus extends Command
{
    /**
     * Order Id.
     */
    public const ORDER_ID = 'order_id';

    /**
     * @var FetchStatus
     */
    protected $fetchStatus;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State       $state
     * @param FetchStatus $fetchStatus
     */
    public function __construct(
        State $state,
        FetchStatus $fetchStatus
    ) {
        $this->state = $state;
        $this->fetchStatus = $fetchStatus;
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
        $this->fetchStatus->setOutput($output);

        $orderId = $input->getArgument(self::ORDER_ID);

        return $this->fetchStatus->fetch($orderId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:order:fetch_status');
        $this->setDescription('Fetch Order Status');
        $this->setDefinition(
            [new InputArgument(self::ORDER_ID, InputArgument::REQUIRED, 'Order Id')]
        );
        parent::configure();
    }
}
