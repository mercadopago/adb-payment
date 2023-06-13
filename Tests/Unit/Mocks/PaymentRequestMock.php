<?php

namespace Tests\unit\Mocks;

class PaymentRequestMock {

  public const ASGARD_PAYMENT_URI = 'v1/asgard/internal/payments';

  public const PLATFORM_ID = 'TEST';

  public const KEY_MOCK = 'APP_USR-00000000000-000000-000000-0000000000';
  public const TOKEN_MOCK = 'APP_USR-0000000000000000-000000-00000000000000000000000000000000-0000000000';

  public const PAYMENT_REQUEST_MOCK = [    
    "additional_info" => [
        "items" => [
            [
                "id" => "MLB2907679857",
                "title" => "Point Mini",
                "description" => "Producto Point para cobros con tarjetas mediante bluetooth",
                "picture_url" => "https://http2.mlstatic.com/resources/frontend/statics/growth-sellers-landings/device-mlb-point-i_medium@2x.png",
                "category_id" => "electronics",
                "quantity" => 1,
                "unit_price" => 58.8
            ]
        ],
        "payer" => [
            "first_name" => "Test",
            "last_name" => "Test",
            "phone" => [
                "area_code" => 11,
                "number" => "987654321"
            ],
            "address" => []
        ],
        "shipments" => [
            "receiver_address" => [
                "zip_code" => "12312-123",
                "state_name" => "Rio de Janeiro",
                "city_name" => "Buzios",
                "street_name" => "Av das Nacoes Unidas",
                "street_number" => 3003
            ]
        ],
        "barcode" => []
    ],
    "description" => "Payment for product",
    "external_reference" => "MP0001",
    "installments" => 1,
    "metadata" => [],
    "payer" => [
        "entity_type" => "individual",
        "type" => "customer",
        "identification" => []
    ],
    "payment_method_id" => "visa",
    "transaction_amount" => 58.8
]; 
 
}