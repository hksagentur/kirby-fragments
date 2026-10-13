<?php

Kirby::plugin('hksagentur/fragments', [
    'options' => [
        'path' => 'fragments',
        'directory' => 'fragments',
        'header' => 'X-Fragment',
        'cacheControl' => 'private, max-age=300',
    ],
    'routes' => require __DIR__ . '/config/routes.php',
]);
