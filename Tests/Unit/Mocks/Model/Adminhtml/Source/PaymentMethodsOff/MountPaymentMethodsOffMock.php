<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Adminhtml\Source\PaymentMethodsOff;

class MountPaymentMethodsOffMock {

    public const EXPECTED_WITHOUT_PAYMENT_PLACES = [
        0 => [
            'value' => "bolbradesco",
            'label' => "Boleto",
            ],
        1 => [
            'value' => "pec",
            'label' => "Pagamento na lotérica sem boleto",
        ]
    ];

    public const EXPECTED_WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE = [
        0 => [
            'value' => "bolbradesco",
            'label' => "Boleto",
        ]
    ];

    public const EXPECTED_WITH_PAYMENT_PLACES = [
        0 => [
            'value' => "7eleven",
            'label' => "7 Eleven",
        ],
        1 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
        ],
        2 => [
            'value' => "calimax",
            'label' => "Calimax",
        ],
        3 => [
            'value' => "circlek",
            'label' => "Circle K",
        ],
        4 => [
            'value' => "banamex",
            'label' => "Citibanamex",
        ],
        5 => [
            'value' => "extra",
            'label' => "Extra",
        ],
        6 => [
            'value' => "oxxo",
            'label' => "OXXO",
        ],
        7 => [
            'value' => "serfin",
            'label' => "Santander",
        ],
        8 => [
            'value' => "soriana",
            'label' => "Soriana",
        ]
    ];

    public const EXPECTED_WITH_PAYMENT_PLACES_AND_INACTIVE = [
        0 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
        ],
        1 => [
            'value' => "circlek",
            'label' => "Circle K",
        ],
        2 => [
            'value' => "banamex",
            'label' => "Citibanamex",
        ],
        3 => [
            'value' => "extra",
            'label' => "Extra",
        ],
        4 => [
            'value' => "soriana",
            'label' => "Soriana",
        ]
    ];
}
