<?php

namespace Hks\Fragments\Cms;

use Kirby\Cms\App;
use Kirby\Plugin\Plugin;

final class Options
{
    public static function routePrefix(): string
    {
        return trim(static::option('route'), '/');
    }

    public static function snippetDirectory(): string
    {
        return trim(static::option('snippets'), '/');
    }

    public static function mediaType(): string
    {
        return static::option('mediaType');
    }

    public static function cacheControl(): ?string
    {
        return static::option('cacheControl');
    }

    /**
     * @param 'item'|'items' $key
     */
    public static function queryParameter(string $key): string
    {
        $parameters = static::option('query');

        if (! $parameters) {
            return $key;
        }

        return $parameters[$key] ?? $key;
    }

    protected static function plugin(): Plugin
    {
        return App::instance()->plugin('hksagentur/fragments');
    }

    protected static function option(string $key): mixed
    {
        return static::plugin()->option($key);
    }
}
