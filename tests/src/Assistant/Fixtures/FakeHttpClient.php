<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class FakeHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return new class implements ResponseInterface
        {
            public function getStatusCode(): int
            {
                return 200;
            }

            public function getBody(): StreamInterface
            {
                return fopen('php://temp', 'r+');
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion($version): MessageInterface
            {
                return $this;
            }

            public function getHeaders(): array
            {
                return [];
            }

            public function hasHeader($name): bool
            {
                return false;
            }

            public function getHeader($name): array
            {
                return [];
            }

            public function getHeaderLine($name): string
            {
                return '';
            }

            public function withHeader($name, $value): MessageInterface
            {
                return $this;
            }

            public function withAddedHeader($name, $value): MessageInterface
            {
                return $this;
            }

            public function withoutHeader($name): MessageInterface
            {
                return $this;
            }

            public function withBody(StreamInterface $body): MessageInterface
            {
                return $this;
            }

            public function getReasonPhrase(): string
            {
                return 'OK';
            }
        };
    }
}
