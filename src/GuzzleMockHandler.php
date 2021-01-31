<?php

namespace Tomb1n0\GuzzleMockHandler;

use GuzzleHttp\Promise\Create;
use PHPUnit\Framework\Assert;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseNotFound extends \Exception
{
};

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
            'mock' => $response,
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

            if ($response['mock']->matches($request)) {
                $responseKey = $key;
            }
        }

        if (is_null($responseKey)) {
            throw new ResponseNotFound('No response found for ' . $method . ' => ' . $path);
        }

        $response = $this->responses[$responseKey];

        if ($response['mock']->getOnce()) {
            unset($this->responses[$responseKey]);
        }

        return $response;
    }

    private function rewindRequestAndResponseBodies(RequestInterface $request, ResponseInterface $response)
    {
        $request->getBody()->rewind();
        $response->getBody()->rewind();
    }

    private function callAssertions($assertions = [], RequestInterface $request, ResponseInterface $response, $options = [])
    {
        foreach ($assertions as $callback) {
            $callback($request, $response, $options);

            $this->rewindRequestAndResponseBodies($request, $response);
        }
    }

    public function __invoke(RequestInterface $request, $options = [])
    {
        $response = $this->getResponse($request);

        $mockResponse = $response['mock'];
        $guzzleResponse = $mockResponse->asGuzzleResponse();

        if (!empty($mockResponse->getAssertions())) {
            $this->callAssertions($mockResponse->getAssertions(), $request, $guzzleResponse, $options);
        }

        $this->rewindRequestAndResponseBodies($request, $guzzleResponse);

        $this->called[] = $response['name'];

        return Create::promiseFor($guzzleResponse);
    }
}
