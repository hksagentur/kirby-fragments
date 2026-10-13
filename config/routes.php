<?php

use Hks\Fragments\Http\FragmentController;
use Hks\Fragments\Http\FragmentResponse;
use Kirby\Cms\App;
use Kirby\Cms\Language;

return function (App $kirby): array {
    $pattern = trim($kirby->option('hksagentur.fragments.path', 'fragments'), '/') . '/(:all)';

    if ($kirby->multilang()) {
        return [
            [
                'pattern' => $pattern,
                'method' => 'GET',
                'language' => '*',
                'action' => function (Language $language, string $name): FragmentResponse {
                    return (new FragmentController())($name, $language);
                },
            ],
        ];
    }

    return [
        [
            'pattern' => $pattern,
            'method' => 'GET',
            'action' => function (string $name): FragmentResponse {
                return (new FragmentController())($name);
            },
        ],
    ];
};
