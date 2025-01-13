<?php

declare(strict_types=1);

namespace Fansipan\Log;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MessageFormatter
{
    /**
     * Returns a formatted message string.
     */
    public function format(RequestInterface $request, ?ResponseInterface $response = null, ?\Throwable $error = null): string;
}
