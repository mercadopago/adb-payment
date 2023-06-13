<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Console\Command\Notification;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutProAddChildPayment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line for add child transaction.
 */
class CheckoutProAddChild extends Command
{
    /**
     * Order Id.
     */
    public const ORDER_ID = 'order-id';

    /**
     * Child Transaction Id.
     */
    public const CHILD = 'child';

    /**
     * @var CheckoutProAddChildPayment
     */
    protected $checkoutProAddChild;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State                      $state
     * @param CheckoutProAddChildPayment $checkoutProAddChild
     */
    public function __construct(
        State $state,
        CheckoutProAddChildPayment $checkoutProAddChild
    ) {
        $this->state = $state;
        $this->checkoutProAddChild = $checkoutProAddChild;
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
        $this->checkoutProAddChild->setOutput($output);

        $orderId = $input->getArgument(self::ORDER_ID);
        $transactionId = $input->getArgument(self::CHILD);

        return $this->checkoutProAddChild->add($orderId, $transactionId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:order:checkout_pro_add_child');
        $this->setDescription('Fetch Order Checkout Pro');
        $this->setDefinition(
            [
                new InputArgument(self::ORDER_ID, InputArgument::REQUIRED, 'Order Id'),
                new InputArgument(self::CHILD, InputArgument::REQUIRED, 'Child Transaction Id'),
            ]
        );
        parent::configure();
    }
}
