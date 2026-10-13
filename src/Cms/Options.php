<?php

namespace Hks\Fragments\Cms;

use Kirby\Cms\App;
use Kirby\Plugin\Plugin;

final class Options
{
    public static function path(): string
    {
        return trim(static::option('path'), '/');
    }

    public static function directory(): string
    {
        return trim(static::option('directory'), '/');
    }

    public static function header(): string
    {
        return static::option('header');
    }

    public static function parameter(): string
    {
        return static::option('parameter');
    }

    public static function cacheControl(): ?string
    {
        return static::option('cacheControl');
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
