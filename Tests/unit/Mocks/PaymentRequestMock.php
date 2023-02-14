<?php

namespace Tests\unit\Mocks;

class PaymentResponseMock {

    public $id;
    public $status;

    function buildPaymentResponse($uid) {    
       $paymentResponse = new PaymentResponseMock();
       $paymentResponse->id="1234567890"
       $paymentResponse->id="approved"
       var_dump($paymentResponse);   
       return $paymentResponse;
    }

}