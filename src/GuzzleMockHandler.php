<?php

namespace Tomb1n0\GuzzleMockHandler;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

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
            'assertion' => $response->getAssertion(),
            'requestJsonAssertion' => $response->getAssertRequestJson(),
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

    private function rewindRequestAndResponseBodies(RequestInterface $request, Response $response)
    {
        $request->getBody()->rewind();
        $response->getBody()->rewind();
    }

    public function __invoke(RequestInterface $request, $options = [])
    {
        $response = $this->getResponse($request);

        $guzzleResponse = $response['response'];

        // Call the users assertion method, providing the request and our mocked response
        if (!empty($response['assertion'])) {
            $response['assertion']($request, $guzzleResponse);
        }

        $this->rewindRequestAndResponseBodies($request, $guzzleResponse);

        if (!empty($response['requestJsonAssertion'])) {
            $response['requestJsonAssertion']($request);
        }

        $this->rewindRequestAndResponseBodies($request, $guzzleResponse);


        $this->called[] = $response['name'];

        return Create::promiseFor($guzzleResponse);
    }
}
