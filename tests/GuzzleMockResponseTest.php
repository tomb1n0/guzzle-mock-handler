<?php

namespace Tomb1n0\GuzzleMockHandler\Tests;

use PHPUnit\Framework\TestCase;
use Tomb1n0\GuzzleMockHandler\GuzzleMockResponse;

class GuzzleMockResponseTest extends TestCase
{
    /** @test */
    public function it_has_sensible_defaults()
    {
        $response = new GuzzleMockResponse('/login');

        $this->assertEquals('get', $response->getMethod());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('/login', $response->getPath());
        $this->assertEquals(json_encode([]), $response->getBody());
        $this->assertEquals([], $response->getAssertions());
        $this->assertEquals(null, $response->getOnce());
        $this->assertEquals([], $response->getWheres());
    }

    /** @test */
    public function can_be_constructed_with_static_make_function()
    {
        $response = GuzzleMockResponse::make('/login');

        $this->assertEquals('get', $response->getMethod());
        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('/login', $response->getPath());
        $this->assertEquals(json_encode([]), $response->getBody());
        $this->assertEquals([], $response->getAssertions());
        $this->assertEquals(null, $response->getOnce());
        $this->assertEquals([], $response->getWheres());
    }


    /** @test */
    public function setters_work()
    {
        $response = new GuzzleMockResponse('/login');

        $response->withMethod('post');
        $this->assertEquals('post', $response->getMethod());

        $response->withStatus(500);
        $this->assertEquals(500, $response->getStatus());

        $response->withBody(['foo' => 'bar']);
        $this->assertEquals(json_encode(['foo' => 'bar']), $response->getBody());

        $response->withHeaders(['header' => 'value']);
        $this->assertEquals(['header' => 'value', 'Content-Type' => 'application/json'], $response->getHeaders());

        $assertion = function ($request, $response) {
            $foo = 'bar';
        };
        $response->withAssertion($assertion);
        $this->assertContains($assertion, $response->getAssertions());


        $response->assertRequestJson(['foo' => 'bar']);
        $this->assertCount(2, $response->getAssertions());

        $response->assertRequestHeaders(['foo' => 'bar']);
        $this->assertCount(3, $response->getAssertions());
    }
}
