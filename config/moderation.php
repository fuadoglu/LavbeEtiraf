<?php

return [
    'mfa_key' => env('MODERATOR_MFA_KEY', ''),
    'allow_bootstrap' => (bool) env('LAVBE_ALLOW_ADMIN_BOOTSTRAP', false),
    'local_skip_mfa' => (bool) env('MODERATOR_LOCAL_SKIP_MFA', false),
    'mfa_fresh_seconds' => 300,
    'totp_period_seconds' => 30,
    'totp_window' => 1,
];
