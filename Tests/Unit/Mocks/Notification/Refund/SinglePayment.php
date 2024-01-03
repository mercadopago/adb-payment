<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Notification\Refund;

class SinglePayment
{
    public const SINGLE_PAYMENT_DATA = [
        "notification_id" => "P-66817921925",
        "notification_url" => "https://ltucillo.ppolimpo.io/mp/notification/checkoutcustom/",
        "status" => "refunded",
        "transaction_id" => "66817921925",
        "transaction_type" => "payment",
        "platform_id" => "BP1EF6QIC4P001KBGQ10",
        "external_reference" => "000000073",
        "preference_id" => "",
        "transaction_amount" => 112.00,
        "total_paid" => 0.00,
        "total_approved" => 0.00,
        "total_pending" => 0.00,
        "total_refunded" => 112.00,
        "total_rejected" => 0.00,
        "total_cancelled" => 0.00,
        "total_charged_back" => 0.00,
        "multiple_payment_transaction_id" => "",
        "payments_metadata" => [
            "checkout" => "custom",
            "checkout_type" => "credit_card",
            "cpp_extra" => [
                "checkout" => "custom",
                "checkout_type" => "credit_card",
                "module_version" => "1.5.0",
                "platform" => "BP1EF6QIC4P001KBGQ10",
                "platform_version" => "2.4.6-p2",
                "site_id" => "MLB",
                "sponsor_id" => 222567845,
                "store_id" => 1,
                "test_mode" => false
            ],
            "module_version" => "1.5.0",
            "original_notification_url" => "https://ltucillo.ppolimpo.io/mp/notification/checkoutcustom/",
            "platform" => "BP1EF6QIC4P001KBGQ10",
            "platform_version" => "2.4.6-p2",
            "site_id" => "MLB",
            "sponsor_id" => 222567845,
            "store_id" => 1,
            "test_mode" => false
        ],
        "payments_details" => [
            [
                "id" => 66817921925,
                "payment_method_id" => "master",
                "payment_method_info" => [
                    "barcode_content" => "",
                    "external_resource_url" => "",
                    "payment_method_reference_id" => "",
                    "date_of_expiration" => "",
                    "last_four_digits" => "6351",
                    "qr_code_base64" => "",
                    "qr_code" => "",
                    "installments" => 2.00,
                    "installment_rate" => 7.64,
                    "installment_amount" => 60.28,
                    "charges_details" => [
                        [
                            "id" => "66817921925-001",
                            "name" => "mercadopago_fee",
                            "type" => "fee",
                            "accounts" => [
                                "from" => "collector",
                                "to" => "mp"
                            ],
                            "client_id" => 0,
                            "date_created" => "2023-11-14T13:55:33.000-04:00",
                            "last_updated" => "2023-11-14T13:56:18.000-04:00",
                            "amounts" => [
                                "original" => 5.58,
                                "refunded" => 5.58
                            ],
                            "metadata" => [],
                            "reserve_id" => 0.00,
                            "refund_charges" => [
                                [
                                    "amount" => 5.58,
                                    "client_id" => 6572843794902693.00,
                                    "currency_id" => "BRL",
                                    "date_created" => "2023-11-14T13:56:18.000-04:00",
                                    "operation" => [
                                        "id" => 1538130155.00,
                                        "type" => 0.00
                                    ],
                                    "reserve_id" => 0.00
                                ]
                            ]
                        ],
                        [
                            "id" => "66817921925-002",
                            "name" => "financing_fee",
                            "type" => "fee",
                            "accounts" => [
                                "from" => "payer",
                                "to" => "mp"
                            ],
                            "client_id" => 0,
                            "date_created" => "2023-11-14T13:55:33.000-04:00",
                            "last_updated" => "2023-11-14T13:56:18.000-04:00",
                            "amounts" => [
                                "original" => 8.56,
                                "refunded" => 8.56
                            ],
                            "metadata" => [],
                            "reserve_id" => 0.00,
                            "refund_charges" => [
                                [
                                    "amount" => 8.56,
                                    "client_id" => 6572843794902693.00,
                                    "currency_id" => "BRL",
                                    "date_created" => "2023-11-14T13:56:18.000-04:00",
                                    "operation" => [
                                        "id" => 1538130155.00,
                                        "type" => 0.00
                                    ],
                                    "reserve_id" => 0.00
                                ]
                            ]
                        ]
                    ]
                ],
                "payment_type_id" => "credit_card",
                "total_amount" => 112.00,
                "paid_amount" => 120.56,
                "shipping_cost" => 0.00,
                "coupon_amount" => 0.00,
                "status" => "refunded",
                "status_detail" => "refunded",
                "refunds" => [
                    "1538130155" => [
                        "id" => 1538130155,
                        "status" => "approved",
                        "notifying" => true,
                        "metadata" => [
                            "status_detail" => null
                        ]
                    ]
                ]
            ]
        ],
        "refunds_notifying" => [
            [
                "id" => 1538130155,
                "notifying" => true,
                "amount" => 112.00
            ]
        ]
    ];

    public static function refundFromMagento(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;
        $data['payments_details'][0]['refunds']['1538130155']['metadata']['origem'] = 'magento';

        return $data;
    }

    public static function twoRefunds(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['payments_details'][0]['refunds'] = [
            "1538130155" => [
                "id" => 1538130155,
                "status" => "approved",
                "notifying" => true,
                "metadata" => [
                    "status_detail" => null
                ]
            ],
            "1538130156" => [
                "id" => 1538130156,
                "status" => "approved",
                "notifying" => true,
                "metadata" => [
                    "status_detail" => null
                ]
            ]
        ];

        $data['refunds_notifying'] = [
            [
                "id" => 1538130155,
                "notifying" => true,
                "amount" => 66.00
            ],
            [
                "id" => 1538130156,
                "notifying" => true,
                "amount" => 66.00
            ]
        ];

        return $data;
    }

    public static function twoRefundsWithProcessingOne(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['payments_details'][0]['refunds'] = [
            "1538130155" => [
                "id" => 1538130155,
                "status" => "processing",
                "notifying" => true,
                "metadata" => [
                    "status_detail" => null
                ]
            ],
            "1538130156" => [
                "id" => 1538130156,
                "status" => "approved",
                "notifying" => true,
                "metadata" => [
                    "status_detail" => null
                ]
            ]
        ];

        $data['refunds_notifying'] = [
            [
                "id" => 1538130155,
                "notifying" => true,
                "amount" => 66.00
            ],
            [
                "id" => 1538130156,
                "notifying" => true,
                "amount" => 66.00
            ]
        ];

        return $data;
    }

    public static function customTicket(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['payments_metadata']['checkout_type'] = 'ticket';
        $data['payments_metadata']['cpp_extra'] = [
            "checkout_type" => "ticket",
            "site_id" => "MLA",
            "sponsor_id" => 222568987,
        ];
        $data['payments_details'][0]['payment_type_id'] = 'ticket';
        $data['payments_details'][0]['payment_method_id'] = 'pagofacil';
        $data['payments_details'][0]['payment_method_id'] = 'pagofacil';
        $data['payments_details'][0]['payment_type_id'] = 'ticket';
        $data['payments_details'][0]['paid_amount'] = 112.0;
        $data['payments_details'][0]['status'] = 'refunded';
        $data['payments_details'][0]['status_detail'] = 'refunded';
        $data['payments_details'][0]['refunds'] = [
            "1574058619" => [
                "id" => 1574058619,
                "status" => "approved",
                "notifying" => true,
                "metadata" => [
                    "origem" => "magento",
                    "status_detail" => null
                ]
            ]
        ];

        return $data;
    }

    public static function proAccountMoney(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['notification_id'] = 'M-14433610808';
        $data['notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['transaction_id'] = '14433610808';
        $data['transaction_type'] = 'merchant_order';
        $data['payments_metadata']['checkout_type'] = 'modal';
        $data['payments_metadata']['checkout'] = 'pro';
        $data['payments_metadata']['cpp_extra'] = [
            "checkout" => "pro",
            "checkout_type" => "modal",
            "site_id" => "MLA",
        ];
        $data['payments_details'][0]['payment_type_id'] = 'account_money';
        $data['payments_details'][0]['paid_amount'] = 64.0;
        $data['payments_details'][0]['status'] = 'refunded';
        $data['payments_details'][0]['status_detail'] = 'refunded';
        $data['payments_details'][0]['id'] = 69496770722;
        $data['payments_details'][0]['payment_method_info'] = [
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "",
            "installment_amount" => 0.0
        ];

        return $data;
    }

    public static function proCard(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['notification_id'] = 'M-14433707978';
        $data['notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['transaction_id'] = '14433707978';
        $data['transaction_type'] = 'merchant_order';
        $data['payments_metadata']['checkout_type'] = 'modal';
        $data['payments_metadata']['checkout'] = 'pro';
        $data['payments_metadata']['cpp_extra'] = [
            "checkout" => "pro",
            "checkout_type" => "modal",
            "site_id" => "MLA",
        ];
        $data['payments_metadata']['original_notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['payments_metadata']['site_id'] = 'MLA';
        $data['payments_details'][0]['payment_type_id'] = 'debit_card';
        $data['payments_details'][0]['payment_method_id'] = 'debvisa';
        $data['payments_details'][0]['paid_amount'] = 112.0;
        $data['payments_details'][0]['status'] = 'refunded';
        $data['payments_details'][0]['status_detail'] = 'refunded';
        $data['payments_details'][0]['id'] = 69496770722;
        $data['payments_details'][0]['payment_method_info'] = [
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "5619",
            "installment_amount" => 64.0
        ];

        return $data;
    }

    public static function proTicket(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['notification_id'] = 'M-14433829744';
        $data['notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['transaction_id'] = '14433829744';
        $data['transaction_type'] = 'merchant_order';
        $data['payments_metadata']['checkout_type'] = 'modal';
        $data['payments_metadata']['checkout'] = 'pro';
        $data['payments_metadata']['cpp_extra'] = [
            "checkout" => "pro",
            "checkout_type" => "modal",
            "site_id" => "MLA",
        ];
        $data['payments_metadata']['original_notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['payments_metadata']['site_id'] = 'MLA';
        $data['payments_details'][0]['payment_type_id'] = 'ticket';
        $data['payments_details'][0]['payment_method_id'] = 'rapipago';
        $data['payments_details'][0]['paid_amount'] = 112.0;
        $data['payments_details'][0]['status'] = 'refunded';
        $data['payments_details'][0]['status_detail'] = 'refunded';
        $data['payments_details'][0]['id'] = 69496770722;
        $data['payments_details'][0]['payment_method_info'] = [
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "",
            "installment_amount" => 0.0
        ];

        return $data;
    }

    public static function proCredits(): array
    {
        $data = self::SINGLE_PAYMENT_DATA;

        $data['notification_id'] = 'M-14427225055';
        $data['notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['transaction_id'] = '14427225055';
        $data['transaction_type'] = 'merchant_order';
        $data['payments_metadata']['checkout_type'] = 'modal';
        $data['payments_metadata']['checkout'] = 'pro';
        $data['payments_metadata']['cpp_extra'] = [
            "checkout" => "pro",
            "checkout_type" => "modal",
            "site_id" => "MLA",
        ];
        $data['payments_metadata']['original_notification_url'] = 'https =>//albalmeida.ppolimpo.io/mp/notification/checkoutpro/';
        $data['payments_metadata']['site_id'] = 'MLA';
        $data['payments_details'][0]['payment_type_id'] = 'digital_currency';
        $data['payments_details'][0]['payment_method_id'] = 'consumer_credits';
        $data['payments_details'][0]['paid_amount'] = 64.0;
        $data['payments_details'][0]['status'] = 'refunded';
        $data['payments_details'][0]['status_detail'] = 'refunded';
        $data['payments_details'][0]['id'] = 69496770722;
        $data['payments_details'][0]['payment_method_info'] = [
            "installments" => 1,
            "installment_rate" => 0.0,
            "last_four_digits" => "",
            "installment_amount" => 0.0
        ];

        return $data;
    }
}
