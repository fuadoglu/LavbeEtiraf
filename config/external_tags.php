<?php

return [
    'ready' => filter_var(env('LAVBE_EXTERNAL_TAGS_READY', false), FILTER_VALIDATE_BOOLEAN),
];
