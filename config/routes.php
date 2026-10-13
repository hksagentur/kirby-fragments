<?php

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Http\FragmentController;
use Hks\Fragments\Http\FragmentResponse;
use Kirby\Cms\App;
use Kirby\Cms\Language;

return function (App $kirby): array {
    $path = Options::path();

    if ($kirby->multilang()) {
        return [
            [
                'pattern' => "{$path}/(:all)",
                'method' => 'GET|HEAD',
                'language' => '*',
                'action' => function (Language $language, string $name): FragmentResponse {
                    return (new FragmentController())($name, $language);
                },
            ],
        ];
    }

    return [
        [
            'pattern' => "{$path}/(:all)",
            'method' => 'GET|HEAD',
            'action' => function (string $name): FragmentResponse {
                return (new FragmentController())($name);
            },
        ],
    ];
};
