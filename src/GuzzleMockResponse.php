<?php

namespace Tomb1n0\GuzzleMockHandler;

use PHPUnit\Framework\Assert;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleMockResponse
{
    private $method = 'get';
    private $status = 200;
    private $path;
    private $body = [];
    private $headers = [];
    private $assertion;
    private $assertRequestJson;
    private $once = false;

    public function __construct($path)
    {
        $this->path = $path;
        $this->body = json_encode($this->body);
        $this->headers = ['Content-Type' => 'application/json'];
    }

    public function asGuzzleResponse()
    {
        return new Response(
            $this->getStatus(),
            $this->getHeaders(),
            $this->getBody()
        );
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getAssertion()
    {
        return $this->assertion;
    }

    public function getAssertRequestJson()
    {
        return $this->assertRequestJson;
    }

    public function getOnce()
    {
        return $this->once;
    }

    public function withMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function withBody($body)
    {
        $this->body = json_encode($body);

        return $this;
    }

    public function withHeaders($headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    public function withAssertion(callable $assertion)
    {
        $this->assertion = $assertion;

        return $this;
    }

    public function assertRequestJson($expectedRequestBody, $key = null)
    {
        $this->assertRequestJson = function (RequestInterface $request) use ($expectedRequestBody, $key) {
            $message = 'Failed asserting request bodies matched';
            $requestBody = json_decode($request->getBody()->getContents(), true);

            if (!is_null($key) && array_key_exists($key, $requestBody)) {
                $requestBody = $requestBody[$key];

                $message .= ' for key "' . $key  . '"';
            }

            Assert::assertEquals($expectedRequestBody, $requestBody, $message);
        };

        return $this;
    }

    public function once()
    {
        $this->once = true;

        return $this;
    }
}
