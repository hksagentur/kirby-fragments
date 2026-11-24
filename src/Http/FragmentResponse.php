<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Cms\Options;
use Kirby\Http\Response;

class FragmentResponse extends Response
{
    public function __construct(
        string $body = '',
        ?int $code = null,
        array $headers = [],
    ) {
        $code ??= empty($body) ? 204 : 200;

        $defaults = [
            'Cache-Control' => static::cacheControlFor($code),
            'Vary' => 'Accept',
            'X-Robots-Tag' => 'noindex',
        ];

        $headers = static::withExpires([
            ...$defaults,
            ...static::normalizeHeaders($headers),
        ]);

        parent::__construct(
            body: $body,
            type: Options::mediaType(),
            code: $code,
            headers: array_filter($headers),
        );
    }

    protected static function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[ucwords(strtolower($name), '-')] = $value;
        }

        return $normalized;
    }

    protected static function withExpires(array $headers): array
    {
        $cacheControl = $headers['Cache-Control'] ?? null;

        if (! $cacheControl || array_key_exists('Expires', $headers)) {
            return $headers;
        }

        $maxAge = static::maxAgeOf($cacheControl);

        $expiresAt = match (true) {
            is_integer($maxAge) => $maxAge + time(),
            default => 0,
        };

        return [
            ...$headers,
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', $expiresAt),
        ];
    }

    protected static function cacheControlFor(int $code): ?string
    {
        return $code >= 400 ? 'no-store' : Options::cacheControl();
    }

    protected static function maxAgeOf(string $cacheControl): ?int
    {
        if (! preg_match('/\bmax-age=(\d+)/', $cacheControl, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
