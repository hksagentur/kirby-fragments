<?php

Kirby::plugin('hksagentur/fragments', [
    'options' => [
        'directory' => 'fragments',
        'path' => 'fragments',
        'parameter' => 'item',
        'mediaType' => 'text/vnd.hksagentur.fragment',
        'cacheControl' => 'private, max-age=300',
    ],
    'routes' => require __DIR__ . '/config/routes.php',
]);
