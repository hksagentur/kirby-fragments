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
        $code ??= empty($body) ? 204 : 200;

        $cacheControl = match (true) {
            $code >= 400 => 'no-store',
            default => Options::cacheControl(),
        };

        parent::__construct(
            body: $body,
            type: 'text/html',
            code: $code,
            headers: array_filter([
                'Cache-Control' => $cacheControl,
                'Expires' => static::expiresFor($cacheControl),
                'Vary' => Options::header(),
                'X-Robots-Tag' => 'noindex',
            ]),
        );
    }

    protected static function expiresFor(?string $cacheControl): ?string
    {
        if ($cacheControl === null) {
            return null;
        }

        if (! preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            return gmdate('D, d M Y H:i:s \G\M\T', 0);
        }

        return gmdate('D, d M Y H:i:s \G\M\T', time() + (int) $matches[1]);
    }
}
