<?php

return [
    'unique_read_tracking_enabled' => filter_var(env('LAVBE_UNIQUE_READ_TRACKING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'reader_consent_cookie_name' => 'lavbe_reader_consent',
    'reader_consent_days' => 365,
    'unique_read_retention_days' => 365,
    'unique_read_transaction_attempts' => 3,
    'unique_read_cleanup_batch' => 1000,
    'unique_read_cleanup_batches_per_run' => 10,
    'unique_read_cleanup_max_batches_per_run' => 50,
    'unique_read_cleanup_schedule' => '03:30',
];
