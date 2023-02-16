<?php 

namespace MercadoPago\Test\Unit\Mock;

class PaymentResponseMock
{

    public const PAYMENT_RESPONSE_MOCK = [    
        
        "id" => 20359978,
        "date_created" => "2019-07-10T14:47:58.000Z",
        "date_approved" => "2019-07-10T14:47:58.000Z",
        "date_last_updated" => "2019-07-10T14:47:58.000Z",
        "money_release_date" => "2019-07-24T14:47:58.000Z",
        "issuer_id" => 25,
        "payment_method_id" => "visa",
        "payment_type_id" => "credit_card",
        "status" => "approved",
        "status_detail" => "accredited",
        "currency_id" => "BRL",
        "description" => "Point Mini a maquininha que dá o dinheiro de suas vendas na hora.",
        "taxes_amount" => 0,
        "shipping_amount" => 0,
        "collector_id" => 448876418,
        "payer" => [
            "id" => 123,
            "email" => "test_user_80507629@testuser.com",
            "identification" => [
                "number" => 19119119100,
                "type" => "CPF"
            ],
            "type" => "customer"
        ],
        "metadata" => [],
        "additional_info" => [
            "items" => [
                [
                    "id" => "PR0001",
                    "title" => "Point Mini",
                    "description" => "Producto Point para cobros con tarjetas mediante bluetooth",
                    "picture_url" => "https://http2.mlstatic.com/resources/frontend/statics/growth-sellers-landings/device-mlb-point-i_medium@2x.png",
                    "category_id" => "electronics",
                    "quantity" => 1,
                    "unit_price" => 58.8
                ]
            ],
            "payer" => [
                "registration_date" => "2019-01-01T15:01:01.000Z"
            ],
            "shipments" => [
                "receiver_address" => [
                    "street_name" => "Av das Nacoes Unidas",
                    "street_number" => 3003,
                    "zip_code" => 6233200,
                    "city_name" => "Buzios",
                    "state_name" => "Rio de Janeiro"
                ]
            ]
        ],
        "external_reference" => "MP0001",
        "transaction_amount" => 58.8,
        "transaction_amount_refunded" => 0,
        "coupon_amount" => 0,
        "transaction_details" => [
            "net_received_amount" => 56.16,
            "total_paid_amount" => 58.8,
            "overpaid_amount" => 0,
            "installment_amount" => 58.8
        ],
        "fee_details" => [
            [
                "type" => "coupon_fee",
                "amount" => 2.64,
                "fee_payer" => "payer"
            ]
        ],
        "statement_descriptor" => "MercadoPago",
        "installments" => 1,
        "card" => [
            "first_six_digits" => 423564,
            "last_four_digits" => 5682,
            "expiration_month" => 6,
            "expiration_year" => 2023,
            "date_created" => "2019-07-10T14:47:58.000Z",
            "date_last_updated" => "2019-07-10T14:47:58.000Z",
            "cardholder" => [
                "name" => "APRO",
                "identification" => [
                    "number" => 19119119100,
                    "type" => "CPF"
                ]
            ]
        ],
        "notification_url" => "https://www.suaurl.com/notificacoes/",
        "processing_mode" => "aggregator",
        "point_of_interaction" => [
            "type" => "PIX",
            "application_data" => [
                "name" => "NAME_SDK",
                "version" => "VERSION_NUMBER"
            ]
        ]
    ]; 
 
}