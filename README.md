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
| `$language` | The current language on multi-language sites, otherwise `null` |
| `$abort`, `$abortIf`, `$abortUnless` | Helpers to cancel the response, see below |

### Requesting a fragment

Fragments answer `GET` and `HEAD` requests that send the `X-Fragment` header. Everything else gets a `404`, so the URLs never compete with your pages.

```js
const response = await fetch('/fragments/lightbox/project?item=page://9c4jq8wq3dc8r7wd', {
  headers: { 'X-Fragment': 'true' },
});

const template = document.createElement('template');
template.innerHTML = await response.text();
```

Name the item by its [UUID](https://getkirby.com/docs/guide/uuids), e.g. from `$page->uuid()` or `$file->uuid()` in the template that renders the trigger. A UUID stays the same across languages and slug changes. Only `page://` and `file://` UUIDs are accepted; unknown ones, drafts and files of drafts answer with a `404` before the snippet runs.

What the fragment does with the item is up to the snippet: check its type with `$abortUnless()`, and check any access rules your templates enforce — a fragment does not run the item's template or controller, so a page that is published but protected there (for example behind a login) is not protected here.

On a multi-language site, request the fragment under the language's URL (for example `/en/fragments/…`) so it renders in that language. Build the URL on the server, e.g. with `url('fragments/lightbox/project')`, rather than in JavaScript.

### Responses

| Status | When |
| --- | --- |
| `200` | The fragment rendered markup |
| `204` | The fragment rendered nothing — a valid, but empty result |
| `404` | No fragment header, an unknown fragment or item, or `$abort()` without a code |
| `500` | The snippet failed unexpectedly; in debug mode the body shows the error and its stack trace |
| any | The code passed to `$abort()`, `$abortIf()` or `$abortUnless()` |

Every response is `text/html`, carries `Vary: X-Fragment` and `X-Robots-Tag: noindex`, and is cached privately for five minutes by default.

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

The message is optional and only surfaces in logs and debugging; the response body stays empty.

## Options

```php
// site/config/config.php
return [
    'hksagentur.fragments' => [
        'path' => 'fragments',
        'directory' => 'fragments',
        'header' => 'X-Fragment',
        'parameter' => 'item',
        'cacheControl' => 'private, max-age=300',
    ],
];
```

| Option | Default | Description |
| --- | --- | --- |
| `path` | `fragments` | URL prefix fragments are served under |
| `directory` | `fragments` | Snippet directory fragments are read from |
| `header` | `X-Fragment` | Request header that marks a fragment request; also sent as `Vary` |
| `parameter` | `item` | Query parameter that names the item by its UUID |
| `cacheControl` | `private, max-age=300` | `Cache-Control` header of every response; `null` leaves it out |

## License

ISC License. Please see [License File](LICENSE.txt) for more information.
