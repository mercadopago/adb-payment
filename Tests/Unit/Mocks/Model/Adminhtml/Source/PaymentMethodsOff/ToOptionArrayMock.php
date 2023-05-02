<?php

namespace MercadoPago\AdbPayment\Tests\Unit\Mocks\Model\Adminhtml\Source\PaymentMethodsOff;

class ToOptionArrayMock {

    public const EXPECTED_WITHOUT_PAYMENT_PLACES = [
        0 => [
            'value' => null,
            'label' => 'Accept all payment methods',
        ],
        1 => [
            'value' => "bolbradesco",
            'label' => "Boleto",
        ],
        2 => [
            'value' => "pec",
            'label' => "Pagamento na lotérica sem boleto",
        ]
    ];

    public const EXPECTED_WITHOUT_PAYMENT_PLACES_AND_WITH_INACTIVE = [
        0 => [
            'value' => null,
            'label' => 'Accept all payment methods',
        ],
        1 => [
            'value' => "bolbradesco",
            'label' => "Boleto",
        ]
    ];

    public const EXPECTED_WITH_PAYMENT_PLACES = [
        0 => [
            'value' => null,
            'label' => 'Accept all payment methods',
        ],
        1 => [
            'value' => "7eleven",
            'label' => "7 Eleven",
        ],
        2 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
        ],
        3 => [
            'value' => "calimax",
            'label' => "Calimax",
        ],
        4 => [
            'value' => "circlek",
            'label' => "Circle K",
        ],
        5 => [
            'value' => "banamex",
            'label' => "Citibanamex",
        ],
        6 => [
            'value' => "extra",
            'label' => "Extra",
        ],
        7 => [
            'value' => "oxxo",
            'label' => "OXXO",
        ],
        8 => [
            'value' => "serfin",
            'label' => "Santander",
        ],
        9 => [
            'value' => "soriana",
            'label' => "Soriana",
        ]
    ];

    public const EXPECTED_WITH_PAYMENT_PLACES_AND_INACTIVE = [
        0 => [
            'value' => null,
            'label' => 'Accept all payment methods',
        ],
        1 => [
            'value' => "bancomer",
            'label' => "BBVA Bancomer",
        ],
        2 => [
            'value' => "circlek",
            'label' => "Circle K",
        ],
        3 => [
            'value' => "banamex",
            'label' => "Citibanamex",
        ],
        4 => [
            'value' => "extra",
            'label' => "Extra",
        ],
        5 => [
            'value' => "soriana",
            'label' => "Soriana",
        ]
    ];
}
