<?php

return [
    'unique_read_retention_days' => 365,
    'unique_read_transaction_attempts' => 3,
    'unique_read_cleanup_batch' => 1000,
    'unique_read_cleanup_batches_per_run' => 10,
    'unique_read_cleanup_max_batches_per_run' => 50,
    'unique_read_cleanup_schedule' => '03:30',
];
