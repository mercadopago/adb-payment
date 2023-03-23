<?php

namespace Tests\Unit\Mocks;

class PaymentsMethodsActiveMock {
    public const paymentsMethodsActive = [
        0 => [
            "accreditation_time" => 60,
            "additional_info_needed" => [
                "identification_type",
                "identification_number",
                "first_name",
                "last_name",
            ],
            "deferred_capture" => "supported",
            "financial_institutions" => [],
            "id" => "pec",
            "max_allowed_amount" => 2003.49,
            "min_allowed_amount" => 4,
            "name" => "Pagamento na lotérica sem boleto",
            "payment_type_id" => "ticket",
            "processing_modes" => ["aggregator"],
            "secure_thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
            "settings" => [],
            "status" => "active",
            "thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
        ],
        1 => [
            "accreditation_time" => 1440,
            "additional_info_needed" => [
                "identification_type",
                "identification_number",
                "first_name",
                "last_name",
            ],
            "deferred_capture" => "does_not_apply",
            "financial_institutions" => [],
            "id" => "bolbradesco",
            "max_allowed_amount" => 100000,
            "min_allowed_amount" => 4,
            "name" => "Boleto",
            "payment_type_id" => "ticket",
            "processing_modes" => ["aggregator"],
            "secure_thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
            "settings" => [],
            "status" => "active",
            "thumbnail" =>
                "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
        ]
    ];

    public const expectedArray = [
            0 => [     
                'value' => "bolbradesco",
                'label' => "Boleto",
                'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/00174300-571e-11e8-8364-bff51f08d440-xl.png",
                'payment_method_id' => "bolbradesco",
                'payment_type_id' => "ticket"
                ],
            1 => [
                'value' => "pec",
                'label' => "Pagamento na lotérica sem boleto",
                'logo' => "https://http2.mlstatic.com/storage/logos-api-admin/70965f00-f3c2-11eb-a186-1134488bf456-xl.png",
                'payment_method_id' => "pec",
                'payment_type_id' => "ticket"
            ]
        ];
}
