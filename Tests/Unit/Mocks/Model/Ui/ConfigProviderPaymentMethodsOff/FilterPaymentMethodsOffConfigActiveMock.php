<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Ui\ConfigProviderPaymentMethodsOff;

class FilterPaymentMethodsOffConfigActiveMock {

    public const EXPECTED_PAYMENT_METHODS = [
        0 => [
            'value' => "7eleven",
            'label' => "7 Eleven",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/417ddb90-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => '7eleven'
        ],
        1 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/65467f50-5cf3-11ec-813c-8542a9aff8ea-xl.png",
            'payment_method_id' => "bancomer",
            'payment_type_id' => "atm"
        ],
        2 => [
            'value' => "calimax",
            'label' => "Calimax",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/52efa730-01ec-11ec-ba6b-c5f27048193b-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'calimax'
        ],
        3 => [
            'value' => "circlek",
            'label' => "Circle K",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/6f952c90-34ab-11e9-8357-f13e9b392369-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'circlek'
        ],
        4 => [
            'value' => "banamex",
            'label' => "Citibanamex",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/2b3223a0-eaf7-11eb-9a80-1175871fb85a-xl.png",
            'payment_method_id' => "banamex",
            'payment_type_id' => "atm"
        ],
        5 => [
            'value' => "extra",
            'label' => "Extra",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9c8f26b0-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'extra'
        ],
        6 => [
            'value' => "oxxo",
            'label' => "OXXO",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/91b830e0-f39b-11eb-9984-b7076edb0bb7-xl.png",
            'payment_method_id' => "oxxo",
            'payment_type_id' => "ticket"
        ],
        7 => [
            'value' => "serfin",
            'label' => "Santander",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9eaa6660-f39b-11eb-8e0d-6f4af49bf82e-xl.png",
            'payment_method_id' => "serfin",
            'payment_type_id' => "atm"
        ],
        8 => [
            'value' => "soriana",
            'label' => "Soriana",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/dac0bf10-01eb-11ec-ad92-052532916206-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'soriana'
        ]
    ];

    public const EXPECTED_WITH_7ELEVEN = [
        0 => [
            'value' => "7eleven",
            'label' => "7 Eleven",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/417ddb90-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => '7eleven'
        ]
    ];

    public const EXPECTED_WITH_7ELEVEN_SERFIN = [
        0 => [
            'value' => "7eleven",
            'label' => "7 Eleven",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/417ddb90-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => '7eleven'
        ],
        1 => [
            'value' => "serfin",
            'label' => "Santander",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9eaa6660-f39b-11eb-8e0d-6f4af49bf82e-xl.png",
            'payment_method_id' => "serfin",
            'payment_type_id' => "atm"
        ],
    ];

    public const EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN = [
        0 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/65467f50-5cf3-11ec-813c-8542a9aff8ea-xl.png",
            'payment_method_id' => "bancomer",
            'payment_type_id' => "atm"
        ],
        1 => [
            'value' => "calimax",
            'label' => "Calimax",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/52efa730-01ec-11ec-ba6b-c5f27048193b-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'calimax'
        ],
        2 => [
            'value' => "circlek",
            'label' => "Circle K",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/6f952c90-34ab-11e9-8357-f13e9b392369-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'circlek'
        ],
        3 => [
            'value' => "banamex",
            'label' => "Citibanamex",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/2b3223a0-eaf7-11eb-9a80-1175871fb85a-xl.png",
            'payment_method_id' => "banamex",
            'payment_type_id' => "atm"
        ],
        4 => [
            'value' => "extra",
            'label' => "Extra",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9c8f26b0-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'extra'
        ],
        5 => [
            'value' => "oxxo",
            'label' => "OXXO",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/91b830e0-f39b-11eb-9984-b7076edb0bb7-xl.png",
            'payment_method_id' => "oxxo",
            'payment_type_id' => "ticket"
        ],
        6 => [
            'value' => "serfin",
            'label' => "Santander",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9eaa6660-f39b-11eb-8e0d-6f4af49bf82e-xl.png",
            'payment_method_id' => "serfin",
            'payment_type_id' => "atm"
        ],
        7 => [
            'value' => "soriana",
            'label' => "Soriana",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/dac0bf10-01eb-11ec-ad92-052532916206-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'soriana'
        ]
    ];

    public const EXPECTED_PAYMENT_METHODS_WITHOUT_7ELEVEN_AND_SERFIN = [
        0 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/65467f50-5cf3-11ec-813c-8542a9aff8ea-xl.png",
            'payment_method_id' => "bancomer",
            'payment_type_id' => "atm"
        ],
        1 => [
            'value' => "calimax",
            'label' => "Calimax",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/52efa730-01ec-11ec-ba6b-c5f27048193b-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'calimax'
        ],
        2 => [
            'value' => "circlek",
            'label' => "Circle K",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/6f952c90-34ab-11e9-8357-f13e9b392369-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'circlek'
        ],
        3 => [
            'value' => "banamex",
            'label' => "Citibanamex",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/2b3223a0-eaf7-11eb-9a80-1175871fb85a-xl.png",
            'payment_method_id' => "banamex",
            'payment_type_id' => "atm"
        ],
        4 => [
            'value' => "extra",
            'label' => "Extra",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/9c8f26b0-34ab-11e9-b8b8-15cad73057aa-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'extra'
        ],
        5 => [
            'value' => "oxxo",
            'label' => "OXXO",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/91b830e0-f39b-11eb-9984-b7076edb0bb7-xl.png",
            'payment_method_id' => "oxxo",
            'payment_type_id' => "ticket"
        ],
        6 => [
            'value' => "soriana",
            'label' => "Soriana",
            'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/dac0bf10-01eb-11ec-ad92-052532916206-xl.png",
            'payment_method_id' => "paycash",
            'payment_type_id' => "ticket",
            'payment_option_id' => 'soriana'
        ]
    ];

}
