<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Console\Command\Notification;

use Magento\Framework\App\State;
use MercadoPago\AdbPayment\Model\Console\Command\Notification\CheckoutCreditsAddChildPayment;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MercadoPago\AdbPayment\Console\Command\Notification\CheckoutProAddChild as ChechoutProAddChild;

/**
 * Command line for add child transaction.
 */
class CheckoutCreditsAddChild extends ChechoutProAddChild
{
    /**
     * @var CheckoutCreditsAddChildPayment
     */
    protected $checkoutCreditsAddChild;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State                          $state
     * @param CheckoutCreditsAddChildPayment $checkoutCreditsAddChild
     */
    public function __construct(
        State $state,
        CheckoutCreditsAddChildPayment $checkoutCreditsAddChild
    ) {
        $this->state = $state;
        $this->checkoutCreditsAddChild = $checkoutCreditsAddChild;
        parent::__construct($state, $checkoutCreditsAddChild);
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
        $this->checkoutCreditsAddChild->setOutput($output);

        $orderId = $input->getArgument(self::ORDER_ID);
        $transactionId = $input->getArgument(self::CHILD);

        return $this->checkoutCreditsAddChild->add($orderId, $transactionId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('mercadopago:order:checkout_credits_add_child');
        $this->setDescription('Fetch Order Checkout Credits');
        $this->setDefinition(
            [
                new InputArgument(self::ORDER_ID, InputArgument::REQUIRED, 'Order Id'),
                new InputArgument(self::CHILD, InputArgument::REQUIRED, 'Child Transaction Id'),
            ]
        );
    }
}
