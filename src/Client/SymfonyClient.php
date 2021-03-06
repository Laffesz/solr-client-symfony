<?php

declare(strict_types=1);

/*
 * This file is part of Solr Client Symfony package.
 *
 * (c) ingatlan.com Zrt. <fejlesztes@ingatlan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace iCom\SolrClient\Client;

use iCom\SolrClient\Client;
use iCom\SolrClient\Exception\CommunicationError;
use iCom\SolrClient\JsonQuery;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SymfonyClient implements Client
{
    private $httpClient;

    public function __construct(array $options = [], HttpClientInterface $httpClient = null)
    {
        $required = ['base_url'];
        if ($missing = array_diff($required, array_keys($options))) {
            throw new \InvalidArgumentException(sprintf('Config is missing the following keys: "%s".', implode(', ', $missing)));
        }

        $this->httpClient = new ScopingHttpClient(
            $httpClient ?: HttpClient::create(),
            [
                '.+' => [
                    // ensure we have a "/" at the end, @see https://tools.ietf.org/html/rfc3986#section-5.2.2
                    'base_uri' => rtrim($options['base_url'], '/').'/',
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ],
            ],
            '.+'
        );
    }

    public function select($jsonBody): array
    {
        return $this->send('GET', 'select', $jsonBody);
    }

    public function update($jsonBody): array
    {
        return $this->send('POST', 'update', $jsonBody);
    }

    private function send(string $method, string $url, $body = null): array
    {
        $options = [];
        if ($body) {
            $options['body'] = $this->getBody($body);
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            throw CommunicationError::fromUpstreamException($e);
        }
    }

    private function getBody($body): string
    {
        if ($body instanceof JsonQuery) {
            return $body->toJson();
        }

        if (!\is_string($body) || '{' !== $body[0]) {
            throw new \InvalidArgumentException(sprintf('Client can accept only string or %s, but "%s" given.', JsonQuery::class, \gettype($body)));
        }

        return $body;
    }
}
