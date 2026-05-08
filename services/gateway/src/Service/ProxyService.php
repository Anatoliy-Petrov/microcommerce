<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ProxyService
{
    private const HOP_BY_HOP_HEADERS = [
        'connection', 'keep-alive', 'proxy-authenticate', 'proxy-authorization',
        'te', 'trailers', 'transfer-encoding', 'upgrade', 'host',
    ];

    public function __construct(private HttpClientInterface $httpClient) {}

    public function forward(
        Request $request,
        string  $upstreamBase,
        array   $extraHeaders = [],
    ): Response {
        $uri     = $upstreamBase.($request->getRequestUri() ?: '/');
        $method  = $request->getMethod();
        $headers = $this->filterHeaders($request->headers->all());

        // Remove Authorization from upstream requests — services use X-User-Id instead
        unset($headers['authorization']);

        $headers = array_merge($headers, array_change_key_case($extraHeaders, CASE_LOWER));

        $options = ['headers' => $headers, 'timeout' => 30];

        $body = $request->getContent();
        if ($body !== '') {
            $options['body'] = $body;
        }

        $upstream = $this->httpClient->request($method, $uri, $options);

        $statusCode      = $upstream->getStatusCode();
        $responseHeaders = $this->filterHeaders($upstream->getHeaders(false));
        $content         = $upstream->getContent(false);

        $response = new Response($content, $statusCode);
        foreach ($responseHeaders as $name => $values) {
            $response->headers->set($name, $values);
        }

        return $response;
    }

    private function filterHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            fn (string $key) => !in_array(strtolower($key), self::HOP_BY_HOP_HEADERS, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
