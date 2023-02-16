<?php 

namespace MercadoPago\Test\Unit\Mock;

class PaymentRefundMock
{

    public const PAYMENT_REFUND_REQUEST_MOCK = [    
        "amount" => 5,
    ]; 
 
    public const PAYMENT_REFUND_RESPONSE_MOCK = [
       "id" => 1009042015,
       "payment_id" => 18552260055,
       "amount" => 10,
       "metadata" => [
           []
       ],
       "source" => [
           [
               "name" => "Nome e sobrenome",
               "id" => "1003743392",
               "type" => "collector"
           ]
       ],
       "date_created" => "2021-11-24T13:58:49.312-04:00",
       "unique_sequence_number" => null,
       "refund_mode" => "standard",
       "adjustment_amount" => 0,
       "status" => "approved",
       "reason" => null,
       "label" => [
           []
       ],
       "partition_details" => [
           []
       ]
   ]; 

}