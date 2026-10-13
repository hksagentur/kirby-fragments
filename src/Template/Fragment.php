<?php

namespace Hks\Fragments\Template;

use Hks\Fragments\Exception\FragmentException;
use Kirby\Cms\App;
use Kirby\Template\Snippet;

class Fragment
{
    public function __construct(
        protected string $name,
        protected array $data = [],
    ) {
    }

    public static function directory(): string
    {
        return trim(App::instance()->option('hksagentur.fragments.directory', 'fragments'), '/');
    }

    public static function factory(string $name, array $data = []): ?static
    {
        if (! static::isSafeName($name)) {
            return null;
        }

        $fragment = new static($name, $data);

        if (! $fragment->exists()) {
            return null;
        }

        return $fragment;
    }

    public function exists(): bool
    {
        return $this->file() !== null;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function snippet(): string
    {
        return static::directory() . '/' . $this->name();
    }

    public function file(): ?string
    {
        return Snippet::file($this->snippet());
    }

    public function snippetData(): array
    {
        return [
            ...$this->data,
            ...$this->snippetHelpers(),
        ];
    }

    public function snippetHelpers(): array
    {
        return [
            'abort' => static::abort(...),
            'abortIf' => static::abortIf(...),
            'abortUnless' => static::abortUnless(...),
        ];
    }

    public function render(): string
    {
        $kirby = App::instance();

        $kirby->data = [
            'kirby' => $kirby,
            'site' => $kirby->site(),
            'pages' => $kirby->site()->children(),
            ...$kirby->data,
        ];

        $markup = snippet(
            name: $this->snippet(),
            data: $this->snippetData(),
            return: true
        );

        return trim($markup);
    }

    protected static function isSafeName(string $name): bool
    {
        return preg_match('!^[\w-]+(/[\w-]+)*$!', $name) === 1;
    }

    protected static function abort(int $code, ?string $message = null): never
    {
        throw new FragmentException(httpCode: $code, message: $message);
    }

    protected static function abortIf(bool $condition, int $code, ?string $message = null): void
    {
        if ($condition) {
            static::abort($code, $message);
        }
    }

    protected static function abortUnless(bool $condition, int $code, ?string $message = null): void
    {
        if (! $condition) {
            static::abort($code, $message);
        }
    }
}
