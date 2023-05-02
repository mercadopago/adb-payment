<?php
/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Model\Console\Command;

use Magento\Payment\Model\Method\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract Model for the command lines.
 */
class AbstractModel
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Output.
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Console Write.
     *
     * @param string $text
     *
     * @return void
     */
    protected function write(string $text)
    {
        if ($this->output instanceof OutputInterface) {
            $this->output->write($text);
        }
    }

    /**
     * Console WriteLn.
     *
     * @param string $text
     *
     * @return void
     */
    protected function writeln($text)
    {
        if ($this->output instanceof OutputInterface) {
            $this->output->writeln($text);
        }
    }
}
