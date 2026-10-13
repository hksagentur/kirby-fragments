<?php

namespace Hks\Fragments\Exception;

use Hks\Fragments\Http\FragmentResponse;
use Kirby\Exception\Exception;

class FragmentException extends Exception
{
    protected static string $defaultKey = 'fragment';
    protected static string $defaultFallback = 'The fragment could not be rendered';
    protected static int $defaultHttpCode = 404;

    public function toResponse(): FragmentResponse
    {
        return new FragmentResponse(code: $this->getHttpCode());
    }
}
