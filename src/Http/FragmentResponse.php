<?php

namespace Hks\Fragments\Http;

use Kirby\Cms\App;
use Kirby\Http\Response;

class FragmentResponse extends Response
{
    public function __construct(
        string $body = '',
        ?int $code = null
    ) {
        $kirby = App::instance();

        parent::__construct(
            body: $body,
            type: 'text/html',
            code: $code ?? (empty($body) ? 204 : 200),
            headers: array_filter([
                'Cache-Control' => $kirby->option('hksagentur.fragments.cacheControl'),
                'Vary' => $kirby->option('hksagentur.fragments.header'),
                'X-Robots-Tag' => 'noindex',
            ]),
        );
    }
}
