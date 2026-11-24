<?php

Kirby::plugin('hksagentur/fragments', [
    'options' => [
        'route' => 'fragments',
        'snippets' => 'fragments',
        'query' => [
            'item' => 'item',
            'items' => 'items',
        ],
        'mediaType' => 'text/vnd.hksagentur.fragment',
        'cacheControl' => 'private, max-age=300',
    ],
    'routes' => require __DIR__ . '/config/routes.php',
]);
