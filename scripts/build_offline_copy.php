<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$catalog = json_decode(file_get_contents($root.'/spec/catalog.json'), true, flags: JSON_THROW_ON_ERROR);
$theme = require $root.'/config/theme.php';
$copy = [];

foreach ($catalog['locales'] as $entry) {
    $locale = $entry['code'];
    $messages = require $root.'/resources/lang/'.$locale.'/lavbe.php';
    $copy[$locale] = [
        'title' => $messages['offline_title'],
        'body' => $messages['offline_body'],
        'retry' => $messages['retry'],
        'dir' => $entry['dir'],
    ];
}

$json = json_encode(['messages' => $copy, 'theme' => $theme['day']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents($root.'/public/offline-copy.json', $json);
