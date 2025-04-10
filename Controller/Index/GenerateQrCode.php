<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;

class GenerateQrCode extends Action
{
    protected LoggerInterface $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $qrCodeBase64 = $this->getRequest()->getParam('data');

            if (!$qrCodeBase64) {
                throw new \InvalidArgumentException("'data' parameter not provided.");
            }

            $imageData = base64_decode($qrCodeBase64);

            if (!$imageData) {
                throw new \RuntimeException("Error decoding image.");
            }

            $image = imagecreatefromstring($imageData);

            if (!$image) {
                throw new \RuntimeException("Error creating image.");
            }

            $image = imagescale($image, 447);

            $this->getResponse()
                ->setHeader('Content-Type', 'image/png')
                ->setBody($this->generatePngOutput($image));

            imagedestroy($image);
        } catch (\Exception $e) {
            $this->logger->debug(json_encode(['error' => $e->getMessage()]));
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->setBody('Error processing QR Code.');
        }

        return $this->getResponse();
    }

    private function generatePngOutput($image)
    {
        ob_start();
        imagepng($image);
        return ob_get_clean();
    }
}
