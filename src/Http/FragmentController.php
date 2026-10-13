<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Exception\FragmentException;
use Hks\Fragments\Template\Fragment;
use Kirby\Cms\App;
use Kirby\Cms\Language;
use Kirby\Cms\Page;
use Kirby\Http\Request;

class FragmentController
{
    public function __invoke(string $name, ?Language $language = null): FragmentResponse
    {
        try {
            return new FragmentResponse($this->fragmentFor($name, $language)->render());
        } catch (FragmentException $exception) {
            return $exception->toResponse();
        }
    }

    protected function wantsFragment(Request $request): bool
    {
        $header = App::instance()->option('hksagentur.fragments.header');

        return filter_var($request->header($header), FILTER_VALIDATE_BOOLEAN) === true;
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
            'page' => $this->pageFor($request),
        ];
    }

    /**
     * @throws FragmentException
     */
    protected function pageFor(Request $request): ?Page
    {
        $id = $request->get('page');

        if (! $id) {
            return null;
        }

        $page = App::instance()->site()->find($id);

        if (! $page || ! $page->isPublished()) {
            throw new FragmentException(message: "The page \"{$id}\" does not exist");
        }

        return $page;
    }
}
