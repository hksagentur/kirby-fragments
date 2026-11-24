<?php

namespace Hks\Fragments\Template;

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Exception\FragmentException;
use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Template\Snippet;

class Fragment
{
    protected ?int $cacheDuration = null;

    public function __construct(
        protected string $name,
        protected array $data = [],
    ) {
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

    public function cacheDuration(): ?int
    {
        return $this->cacheDuration;
    }

    public function snippet(): string
    {
        return Options::snippetDirectory() . '/' . $this->name();
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
            'abort' => $this->abort(...),
            'abortIf' => $this->abortIf(...),
            'abortUnless' => $this->abortUnless(...),
            'cacheFor' => $this->cacheFor(...),
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

    protected function abort(int $code, ?string $message = null): never
    {
        $reason = $message === null ? '' : ": {$message}";

        throw new FragmentException(
            httpCode: $code,
            message: "The fragment \"{$this->name()}\" was aborted{$reason}",
        );
    }

    protected function abortIf(bool $condition, int $code, ?string $message = null): void
    {
        if ($condition) {
            $this->abort($code, $message);
        }
    }

    protected function abortUnless(bool $condition, int $code, ?string $message = null): void
    {
        if (! $condition) {
            $this->abort($code, $message);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function cacheFor(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(message: 'The cache duration must not be negative');
        }

        $this->cacheDuration = min($this->cacheDuration ?? $seconds, $seconds);
    }
}
