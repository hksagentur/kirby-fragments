<?php

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Http\FragmentController;
use Hks\Fragments\Http\FragmentResponse;

return function (): array {
    return [
        [
            'pattern' => Options::routePrefix() . '/(:all)',
            'method' => 'GET|HEAD',
            'language' => '*',
            'action' => function (): FragmentResponse {
                return (new FragmentController())(...$this->arguments());
            },
        ],
    ];
};
