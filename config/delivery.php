<?php

return [
    'indexnow' => [
        'batch_size' => 100,
        'batch_max' => 500,
        'connect_timeout_seconds' => 3,
        'timeout_seconds' => 8,
        'claim_seconds' => 120,
        'retry_initial_seconds' => 60,
        'retry_max_seconds' => 21600,
        'retry_exponent_max' => 8,
    ],
    'notifications' => [
        'batch_size' => 100,
        'batch_max' => 500,
        'email_batch_size' => 100,
        'email_claim_minutes' => 15,
    ],
];
