<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Exception\FragmentException;
use Hks\Fragments\Template\Fragment;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Language;
use Kirby\Cms\Page;
use Kirby\Http\Request;
use Kirby\Toolkit\Escape;
use Kirby\Uuid\Uuid;
use Throwable;

class FragmentController
{
    public function __invoke(string $name, ?Language $language = null): FragmentResponse
    {
        try {
            return new FragmentResponse($this->fragmentFor($name, $language)->render());
        } catch (FragmentException $exception) {
            return $exception->toResponse();
        } catch (Throwable $exception) {
            return new FragmentResponse($this->debugInfo($exception), 500);
        }
    }

    protected function isPublished(Page|File $item): bool
    {
        $page = $item instanceof File ? $item->page() : $item;

        if (! $page) {
            return true;
        }

        return $page->isPublished();
    }

    protected function wantsFragment(Request $request): bool
    {
        return filter_var($request->header(Options::header()), FILTER_VALIDATE_BOOLEAN) === true;
    }

    /**
     * @throws FragmentException
     */
    protected function fragmentFor(string $name, ?Language $language): Fragment
    {
        $request = App::instance()->request();

        if (! $this->wantsFragment($request)) {
            throw new FragmentException(message: 'The request does not ask for a fragment');
        }

        $fragment = Fragment::factory($name, $this->dataFor($request, $language));

        if (! $fragment) {
            throw new FragmentException(message: "The fragment \"{$name}\" does not exist");
        }

        return $fragment;
    }

    /**
     * @throws FragmentException
     */
    protected function dataFor(Request $request, ?Language $language): array
    {
        return [
            'language' => $language,
            'item' => $this->itemFor($request),
        ];
    }

    /**
     * @throws FragmentException
     */
    protected function itemFor(Request $request): Page|File|null
    {
        $uuid = $request->get(Options::parameter());

        if (! $uuid) {
            return null;
        }

        $item = Uuid::is($uuid, ['page', 'file']) ? Uuid::for($uuid)?->model() : null;

        if (! ($item instanceof Page || $item instanceof File) || ! $this->isPublished($item)) {
            throw new FragmentException(message: "The item \"{$uuid}\" does not exist");
        }

        return $item;
    }

    protected function debugInfo(Throwable $exception): string
    {
        if (App::instance()->option('debug') !== true) {
            return '';
        }

        return '<pre>' . Escape::html((string) $exception) . '</pre>';
    }
}
