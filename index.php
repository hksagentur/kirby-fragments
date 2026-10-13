<?php

Kirby::plugin('hksagentur/fragments', [
    'options' => [
        'directory' => 'fragments',
        'header' => 'X-Fragment',
        'path' => 'fragments',
        'parameter' => 'item',
        'cacheControl' => 'private, max-age=300',
    ],
    'routes' => require __DIR__ . '/config/routes.php',
]);
