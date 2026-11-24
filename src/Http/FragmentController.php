<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Exception\FragmentException;
use Hks\Fragments\Template\Fragment;
use Kirby\Cms\App;
use Kirby\Cms\Responder;
use Kirby\Toolkit\Escape;
use Throwable;

class FragmentController
{
    public function __invoke(string $name): FragmentResponse
    {
        try {
            $fragment = $this->fragmentFor($name);

            return new FragmentResponse(
                body: $fragment->render(),
                headers: $this->headersFor($fragment),
            );
        } catch (FragmentException $exception) {
            return $exception->toResponse();
        } catch (Throwable $exception) {
            return new FragmentResponse($this->debugInfo($exception), 500);
        }
    }

    /**
     * @throws FragmentException
     */
    protected function fragmentFor(string $name): Fragment
    {
        $request = FragmentRequest::current();

        if (! $request->acceptsFragment()) {
            throw new FragmentException(message: 'The request does not ask for a fragment');
        }

        $fragment = Fragment::factory($name, $this->dataFor($request));

        if (! $fragment) {
            throw new FragmentException(message: "The fragment \"{$name}\" does not exist");
        }

        return $fragment;
    }

    /**
     * @throws FragmentException
     */
    protected function dataFor(FragmentRequest $request): array
    {
        return [
            'language' => App::instance()->language(),
            'item' => $request->item(),
            'items' => $request->items(),
            'query' => $request->query(),
        ];
    }

    protected function headersFor(Fragment $fragment): array
    {
        $responder = App::instance()->response();

        return array_filter([
            'Cache-Control' => $this->cacheControlFor($fragment, $responder),
            'Vary' => $this->varyFor($responder),
        ]);
    }

    protected function cacheControlFor(Fragment $fragment, Responder $responder): ?string
    {
        $private = Responder::isPrivate(
            $responder->usesAuth(),
            $responder->usesCookies(),
        );

        if ($private) {
            return 'no-store, private';
        }

        return match ($duration = $fragment->cacheDuration()) {
            null => null,
            0 => 'private, no-cache',
            default => "private, max-age={$duration}",
        };
    }

    protected function varyFor(Responder $responder): string
    {
        return implode(', ', array_filter([
            'Accept',
            $responder->usesAuth() ? 'Authorization' : null,
            $responder->usesCookies() !== [] ? 'Cookie' : null,
        ]));
    }

    protected function debugInfo(Throwable $exception): string
    {
        if (App::instance()->option('debug') !== true) {
            return '';
        }

        return '<pre>' . Escape::html((string) $exception) . '</pre>';
    }
}
