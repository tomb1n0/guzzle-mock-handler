<?php

namespace Tomb1n0\GuzzleMockHandler;

use GuzzleHttp\Promise\Create;
use PHPUnit\Framework\Assert;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleMockHandler
{
    private $responses;
    private $called;

    public function __construct()
    {
        $this->responses = [];
        $this->called = [];
    }

    public function expect(GuzzleMockResponse $response, $name = null)
    {
        $name = $name ?? 'response-' . (count($this->responses) + 1);

        $this->responses[] = [
            'method' => strtolower($response->getMethod()),
            'path' => strtolower($response->getPath()),
            'response' => $response->asGuzzleResponse(),
            'assertions' => $response->getAssertions(),
            'once' => $response->getOnce(),
            'name' => $name
        ];
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getCalled()
    {
        return $this->called;
    }

    public function assertCalledOrder($expectedOrder = [])
    {
        Assert::assertEquals($expectedOrder, $this->called, 'Failed asserting requests were in the right order');
    }

    private function getResponse(RequestInterface $request)
    {
        $method = strtolower($request->getMethod());
        $path = strtolower($request->getUri()->getPath());
        $responseKey = null;

        foreach ($this->responses as $key => $response) {
            if (!is_null($responseKey)) {
                continue;
            }

            if ($response['method'] === $method && $response['path'] === $path) {
                $responseKey = $key;
            }
        }

        if (is_null($responseKey)) {
            throw new \Exception('No response set for ' . $method . ' => ' . $path);
        }

        $response = $this->responses[$responseKey];

        if (!empty($response['once'])) {
            unset($this->responses[$responseKey]);
        }

        return $response;
    }

    private function rewindRequestAndResponseBodies(RequestInterface $request, ResponseInterface $response)
    {
        $request->getBody()->rewind();
        $response->getBody()->rewind();
    }

    private function callAssertions($assertions = [], RequestInterface $request, ResponseInterface $response)
    {
        foreach ($assertions as $callback) {
            $callback($request, $response);

            $this->rewindRequestAndResponseBodies($request, $response);
        }
    }

    public function __invoke(RequestInterface $request, $options = [])
    {
        $response = $this->getResponse($request);

        $guzzleResponse = $response['response'];

        if (!empty($response['assertions'])) {
            $this->callAssertions($response['assertions'], $request, $guzzleResponse);
        }

        $this->called[] = $response['name'];

        return Create::promiseFor($guzzleResponse);
    }
}
