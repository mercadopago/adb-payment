<?php

/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */

namespace MercadoPago\AdbPayment\Controller;

class Rest extends \Magento\Webapi\Controller\Rest
{
     /**
     * Handle REST request
     *
     * Based on request decide is it schema request or API request and process accordingly.
     * Throws Exception in case if cannot be processed properly.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $response = parent::dispatch($request);

        return $this->validateThreeDsResponse($response);
    }

    private function validateThreeDsResponse($response)
    {
        if(isset($response))
            if($this->getMessage($response) === '3DS')
                if ($response->getHeader('errorRedirectAction'))
                    $response->setHeader('errorRedirectAction', '', true);

        return $response;
    }

    private function getMessage($response)
    {
        $message = '';

        $exceptions = $response->getException();

        if(isset($exceptions[0]))
        {
            $message = $exceptions[0]->getMessage();
        }

        return $message;
    }
}
