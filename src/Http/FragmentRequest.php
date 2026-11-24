<?php

namespace Hks\Fragments\Http;

use Hks\Fragments\Cms\Options;
use Hks\Fragments\Exception\FragmentException;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Http\Request\Query;
use Kirby\Http\Request;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;
use Kirby\Uuid\Uuid;

class FragmentRequest
{
    public function __construct(
        protected Request $request,
    ) {
    }

    public static function current(): static
    {
        return new static(App::instance()->request());
    }

    public function acceptsFragment(): bool
    {
        $mediaType = Str::lower(Options::mediaType());

        foreach (Str::accepted((string) $this->request->header('Accept')) as $accepted) {
            if (Str::lower($accepted['value']) === $mediaType) {
                return true;
            }
        }

        return false;
    }

    public function query(): Query
    {
        return $this->request->query();
    }

    /**
     * @throws FragmentException
     */
    public function item(): Page|File|null
    {
        $uuid = $this->uuid();

        if ($uuid === null) {
            return null;
        }

        $item = Uuid::for($uuid)?->model();

        if (! $item) {
            throw new FragmentException(
                httpCode: 410,
                message: "The page or file \"{$uuid}\" does not exist"
            );
        }

        if (! $this->isPublished($item)) {
            throw new FragmentException(
                httpCode: 410,
                message: "The page or file \"{$uuid}\" is not published"
            );
        }

        return $item;
    }

    /**
     * @throws FragmentException
     */
    public function items(): Pages|Files|null
    {
        $uuids = $this->uuids();

        if ($uuids === null) {
            return null;
        }

        $items = match (true) {
            Str::startsWith(A::first($uuids), 'file://') => new Files(),
            default => new Pages(),
        };

        foreach ($uuids as $uuid) {
            $item = Uuid::for($uuid)?->model();

            if (! $item || ! $this->isPublished($item)) {
                continue;
            }

            $items->append($item);
        }

        return $items;
    }

    protected function isPublished(Page|File $item): bool
    {
        $page = $item instanceof File ? $item->page() : $item;

        if (! $page?->isPublished()) {
            return false;
        }

        return $page->parents()->filterBy('isDraft', true)->isEmpty();
    }

    /**
     * @throws FragmentException
     */
    protected function uuid(): ?string
    {
        $value = $this->request->get(
            $parameter = Options::queryParameter('item')
        );

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! Uuid::is($value, ['page', 'file'])) {
            throw new FragmentException(
                message: "The \"{$parameter}\" parameter is not a page or file uuid"
            );
        }

        return $value;
    }

    /**
     * @return non-empty-list<string>|null
     * @throws FragmentException
     */
    protected function uuids(): ?array
    {
        $value = $this->request->get(
            $parameter = Options::queryParameter('items')
        );

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new FragmentException(
                message: "The \"{$parameter}\" parameter is not a comma-separated list of uuids"
            );
        }

        $uuids = array_values(array_unique(Str::split($value, ',')));

        if (empty($uuids)) {
            return null;
        }

        foreach ($uuids as $uuid) {
            if (! Uuid::is($uuid, ['page', 'file'])) {
                throw new FragmentException(
                    message: "The \"{$parameter}\" parameter contains \"{$uuid}\", which is not a page or file uuid"
                );
            }
        }

        $schemes = array_unique(array_map(
            callback: fn (string $uuid): string => Str::before($uuid, '://'),
            array: $uuids,
        ));

        if (count($schemes) > 1) {
            throw new FragmentException(
                message: "The \"{$parameter}\" parameter mixes pages and files"
            );
        }

        return $uuids;
    }
}
