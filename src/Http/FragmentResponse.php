<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Cms\Options;
use Kirby\Http\Response;

class FragmentResponse extends Response
{
    public function __construct(
        string $body = '',
        ?int $code = null
    ) {
        parent::__construct(
            body: $body,
            type: 'text/html',
            code: $code ?? (empty($body) ? 204 : 200),
            headers: array_filter([
                'Cache-Control' => Options::cacheControl(),
                'Vary' => Options::header(),
                'X-Robots-Tag' => 'noindex',
            ]),
        );
    }
}
