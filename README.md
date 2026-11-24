# Kirby Fragments

Serve snippets as standalone HTML fragments in [Kirby CMS](https://getkirby.com) — for progressive enhancement where a page loads extra markup on demand, without a JSON API in between.

A fragment is an ordinary snippet in `site/snippets/fragments`. The plugin exposes it under `/fragments/{name}` to requests that ask for a fragment, renders it with the requested page, and answers with a cacheable, non-indexable HTML response.

## Requirements

Kirby CMS (`>=5.5`)  
PHP (`>= 8.2`)

## Installation

### Composer

```sh
composer require hksagentur/kirby-fragments
```

### Download

Download the project archive and copy the files to the plugin directory of your kirby installation. By default this directory is located at `/site/plugins`.

## Usage

### Creating a fragment

Place a snippet in `site/snippets/fragments`. Its path below that directory is the fragment's name, so `site/snippets/fragments/lightbox/project.php` is served at `/fragments/lightbox/project`.

```php
<?php /** @var \Kirby\Cms\Page|\Kirby\Cms\File|null $item */ ?>

<?php $abortUnless($item instanceof ProjectPage, code: 404) ?>

<ul>
  <?php foreach ($item->images() as $image) : ?>
    <li><?= $image ?></li>
  <?php endforeach ?>
</ul>
```

Besides Kirby's usual `$kirby`, `$site` and `$pages`, a fragment receives:

| Variable | Contents |
| --- | --- |
| `$item` | The page or file named by the `item` query parameter, or `null` without one |
| `$items` | The pages or files named by the `items` query parameter, as `Kirby\Cms\Pages` or `Kirby\Cms\Files` in the requested order, or `null` without one |
| `$query` | The query parameters of the request (`Kirby\Http\Request\Query`), e.g. `$query->get('q')` |
| `$language` | The current language on multi-language sites, otherwise `null` |
| `$abort`, `$abortIf`, `$abortUnless` | Helpers to cancel the response, see below |
| `$cacheFor` | Helper to set how long the response may be cached, see below |

### Requesting a fragment

Fragments answer `GET` and `HEAD` requests whose `Accept` header names the fragment media type, `text/vnd.hksagentur.fragment` by default. Everything else gets a `404`, so the URLs never compete with your pages. This is plain content negotiation: proxies, caches and CORS handle `Accept` without extra configuration, unlike a custom header such as `X-Requested-With`. The media type is a vendor type of its own, like Turbo's `text/vnd.turbo-stream.html`, because a fragment is HTML without a document around it; it has to be named exactly, so a browser's `*/*` never matches.

```js
const response = await fetch('/fragments/lightbox/project?item=page://9c4jq8wq3dc8r7wd', {
  headers: { Accept: 'text/vnd.hksagentur.fragment' },
});

const template = document.createElement('template');
template.innerHTML = await response.text();
```

GitHub's [`<include-fragment>`](https://github.com/github/include-fragment-element) sends its `accept` attribute as the `Accept` header and checks that the response's `Content-Type` contains it, which the plugin's responses do:

```html
<include-fragment src="/fragments/lightbox/project?item=page://9c4jq8wq3dc8r7wd" accept="text/vnd.hksagentur.fragment" loading="lazy">
  <p>Loading…</p>
</include-fragment>
```

Name the item by its [UUID](https://getkirby.com/docs/guide/uuids), e.g. from `$page->uuid()` or `$file->uuid()` in the template that renders the trigger. A UUID stays the same across languages and slug changes. Only `page://` and `file://` UUIDs are accepted; anything else answers with a `404`. A valid UUID whose page or file no longer exists or is not published (a draft, or a file of a draft) answers with a `410` before the snippet runs, so a script can forget an item it saved earlier instead of retrying it.

To render several items at once, name them in `items`, separated by commas: `/fragments/stores/favorites?items=page://abc,page://def`. The snippet gets them as `$items` in that order, without duplicates. An entry that is no page or file UUID answers with a `404`, like a broken `item`, and so does a list that mixes pages and files: `$items` is either `Pages` or `Files`, with their methods (`listed()`, `template()`, `images()` …). Entries whose page or file no longer exists or is not published are left out instead of answering with a `410`, since the others are still worth rendering; a script that remembered them can compare what it asked for with what came back and forget the rest. An empty collection means the items were asked for but none is left, `null` that none were asked for. Limit the number of items in the snippet if it matters (`$items->limit(3)`).

What the fragment does with the item is up to the snippet: check its type with `$abortUnless()`, and check any access rules your templates enforce — a fragment does not run the item's template or controller, so a page that is published but protected there (for example behind a login) is not protected here.

On a multi-language site, request the fragment under the language's URL (for example `/en/fragments/…`) so it renders in that language. Build the URL on the server, e.g. with `url('fragments/lightbox/project')`, rather than in JavaScript.

### Responses

| Status | When |
| --- | --- |
| `200` | The fragment rendered markup |
| `204` | The fragment rendered nothing — a valid, but empty result |
| `404` | No fragment media type in `Accept`, an unknown fragment, an `item` or an entry of `items` that is no page or file UUID, a list of `items` that mixes pages and files, or `$abort()` without a code |
| `410` | The `item` names a page or file that no longer exists or is not published |
| `500` | The snippet failed unexpectedly; in debug mode the body shows the error and its stack trace |
| any | The code passed to `$abort()`, `$abortIf()` or `$abortUnless()` |

Every response has the fragment media type as `Content-Type` (`text/vnd.hksagentur.fragment; charset=UTF-8`), carries `Vary: Accept` and `X-Robots-Tag: noindex`, and is cached privately for five minutes by default (see `$cacheFor()` below and the `cacheControl` option).

### Cancelling a fragment

A fragment can cancel its response when it cannot render anything meaningful, for example when it was requested for the wrong kind of page. Whatever the snippet printed before is discarded.

```php
<?php $abortUnless($item instanceof ProjectPage, code: 404) ?>
```

```php
<?php $abortIf($item->isExpired(), code: 410, message: 'The project has ended') ?>
```

```php
<?php $abort(code: 403) ?>
```

The message is optional and only meant for logs and debugging, prefixed with the fragment's name (`The fragment "lightbox/project" was aborted: The project has ended`); the response body stays empty.

### Caching a fragment

A fragment can decide for itself how long a browser may reuse its response. Call `$cacheFor()` with the number of seconds:

```php
<?php $cacheFor(3600) ?>
```

The response then sends `Cache-Control: private, max-age=3600` instead of the `cacheControl` option. `0` sends `private, no-cache`: the browser may store the response, but has to ask the server again every time. Use it when a script acts on what the fragment lists, for example forgets items that are missing from it, so a cached copy can't make it forget an item published in the meantime.

```php
<?php $cacheFor(0) ?>
```

Called more than once, the shortest duration wins, so a branch of the snippet can shorten it without knowing the others. A negative value is an error. Errors and cancelled responses are never cached, whatever the fragment set.

A fragment that reads a cookie or the session (for example through `csrf()` or `$kirby->user()`) or checks the `Authorization` header follows Kirby's own rule for such responses: if the visitor sent that cookie or header, the response is `no-store, private`, whatever `$cacheFor()` said; otherwise it adds `Cookie` or `Authorization` to `Vary`, so the browser doesn't reuse it once the visitor has one.

## Options

```php
// site/config/config.php
return [
    'hksagentur.fragments' => [
        'route' => 'fragments',
        'snippets' => 'fragments',
        'query' => [
            'item' => 'item',
            'items' => 'items',
        ],
        'mediaType' => 'text/vnd.hksagentur.fragment',
        'cacheControl' => 'private, max-age=300',
    ],
];
```

| Option | Default | Description |
| --- | --- | --- |
| `route` | `fragments` | URL prefix fragments are served under |
| `snippets` | `fragments` | Directory below `site/snippets` fragments are read from |
| `query.item` | `item` | Query parameter that names the item by its UUID (`$item`) |
| `query.items` | `items` | Query parameter that names several items by their UUIDs, separated by commas (`$items`) |
| `mediaType` | `text/vnd.hksagentur.fragment` | Media type a request must name in `Accept` to get a fragment (compared exactly, ignoring case and `q`; wildcards don't count); also the `Content-Type` of every response |
| `cacheControl` | `private, max-age=300` | `Cache-Control` header of successful responses, with a matching `Expires`, unless the fragment calls `$cacheFor()`; errors always send `no-store`; `null` leaves both out |

## License

ISC License. Please see [License File](LICENSE.txt) for more information.
