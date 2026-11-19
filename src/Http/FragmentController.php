<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Exception\FragmentException;
use Hks\Fragments\Template\Fragment;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Responder;
use Kirby\Http\Request;
use Kirby\Toolkit\Escape;
use Kirby\Uuid\Uuid;
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

    protected function isPublished(Page|File $item): bool
    {
        $page = $item instanceof File ? $item->page() : $item;

        if (! $page?->isPublished()) {
            return false;
        }

        return $page->parents()->filterBy('isDraft', true)->isEmpty();
    }

    protected function wantsFragment(Request $request): bool
    {
        return filter_var($request->header(Options::header()), FILTER_VALIDATE_BOOLEAN) === true;
    }

    /**
     * @throws FragmentException
     */
    protected function fragmentFor(string $name): Fragment
    {
        $request = App::instance()->request();

        if (! $this->wantsFragment($request)) {
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
    protected function dataFor(Request $request): array
    {
        return [
            'language' => App::instance()->language(),
            'item' => $this->itemFor($request),
            'query' => $request->query(),
        ];
    }

    /**
     * @throws FragmentException
     */
    protected function itemFor(Request $request): Page|File|null
    {
        $uuid = $request->get(Options::parameter());

        if ($uuid === null || $uuid === '') {
            return null;
        }

        if (! is_string($uuid) || ! Uuid::is($uuid, ['page', 'file'])) {
            throw new FragmentException(message: 'The item is not a valid uuid');
        }

        $item = Uuid::for($uuid)?->model();

        if (! $item) {
            throw new FragmentException(httpCode: 410, message: "The item \"{$uuid}\" does not exist");
        }

        if (! $this->isPublished($item)) {
            throw new FragmentException(httpCode: 410, message: "The item \"{$uuid}\" is not published");
        }

        return $item;
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
            Options::header(),
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
