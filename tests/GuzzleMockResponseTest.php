<?php

namespace Tomb1n0\GuzzleMockHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Tomb1n0\GuzzleMockHandler\GuzzleMockHandler;
use Tomb1n0\GuzzleMockHandler\GuzzleMockResponse;
use Tomb1n0\GuzzleMockHandler\PathWhere;

class GuzzleMockResponseTest extends TestCase
{
    /** @test */
    public function match_fails_if_method_is_wrong()
    {
        $responseMock = new GuzzleMockResponse('dave/doug');
        $responseMock->withMethod('POST');

        $request = new Request('GET', 'dave/doug');

        $this->assertFalse($responseMock->matches($request));
    }

    /** @test */
    public function match_of_url_with_params_passies_if_url_is_correct()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}');
        $responseMock->where('fire', '.*');

        $request = new Request('GET', 'dave/doug/anything');

        $this->assertTrue($responseMock->matches($request));
    }

    /** @test */
    public function match_of_url_with_params_fails_if_url_is_wrong()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}');
        $responseMock->where('fire', '.*');

        $request = new Request('GET', 'dave/doun/anything');

        $this->assertFalse($responseMock->matches($request));
    }

    /** @test */
    public function match_of_url_with_params_passies_if_param_correct()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}');
        $responseMock->where('fire', '[0-9]*');

        $request = new Request('GET', 'dave/doug/1');

        $this->assertTrue($responseMock->matches($request));
    }

    /** @test */
    public function match_of_url_with_params_fails_if_param_wrong()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}');
        $responseMock->where('fire', '[0-9]*');

        $request = new Request('GET', 'dave/doug/anything');

        $this->assertFalse($responseMock->matches($request));
    }

    /** @test */
    public function test_multiple_parameter_match_passes()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}/{water}');
        $responseMock->where('fire', '[0-9]*');
        $responseMock->where('water', '.*');

        $request = new Request('GET', 'dave/doug/1/3');

        $this->assertTrue($responseMock->matches($request));
    }

    /** @test */
    public function test_multiple_parameter_match_fails_if_one_wrong()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}/{water}');
        $responseMock->where('fire', '[0-9]*');
        $responseMock->where('water', '.*');

        $request = new Request('GET', 'dave/doug/apple/3');

        $this->assertFalse($responseMock->matches($request));
    }

    /** @test */
    public function test_multiple_parameter_match_passes_if_all_correct()
    {
        $responseMock = new GuzzleMockResponse('dave/doug/{fire}/{water}');
        $responseMock->where('fire', '[0-9]*');
        $responseMock->where('water', '.*');

        $request = new Request('GET', 'dave/doug/1/3');

        $this->assertTrue($responseMock->matches($request));
    }
}
