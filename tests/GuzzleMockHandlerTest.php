<?php

namespace Tomb1n0\GuzzleMockHandler\Tests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GuzzleMockHandler\GuzzleMockHandler;
use Tomb1n0\GuzzleMockHandler\GuzzleMockResponse;

class GuzzleMockHandlerTest extends TestCase
{
    /** @test */
    public function it_starts_off_with_an_empty_state()
    {
        $handler = new GuzzleMockHandler;

        $this->assertEmpty($handler->getResponses());
        $this->assertEmpty($handler->getCalled());
    }

    /** @test */
    public function it_can_store_a_mock_response()
    {
        $handler = new GuzzleMockHandler;
        $response = new GuzzleMockResponse('/login');
        $handler->expect($response);

        $this->assertContains([
            'mock' => $response,
            'name' => 'response-1'
        ], $handler->getResponses());

        $this->assertCount(1, $handler->getResponses());
    }

    /** @test */
    public function it_can_store_a_mock_response_with_a_given_name()
    {
        $handler = new GuzzleMockHandler;
        $response = new GuzzleMockResponse('/login');
        $handler->expect($response, 'login');

        $this->assertContains([
            'mock' => $response,
            'name' => 'login'
        ], $handler->getResponses());

        $this->assertCount(1, $handler->getResponses());
    }

    /** @test */
    public function when_storing_multiple_responses_the_name_is_incremented()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = new GuzzleMockResponse('/login');
        $logoutResponse = new GuzzleMockResponse('/logout');
        $handler->expect($loginResponse);

        $this->assertContains([
            'mock' => $loginResponse,
            'name' => 'response-1'
        ], $handler->getResponses());

        $this->assertCount(1, $handler->getResponses());

        $handler->expect($logoutResponse);

        $this->assertContains([
            'mock' => $logoutResponse,
            'name' => 'response-2'
        ], $handler->getResponses());

        $this->assertCount(2, $handler->getResponses());
    }

    /** @test */
    public function default_response_names_are_correct_even_if_some_responses_have_names()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = new GuzzleMockResponse('/login');
        $logoutResponse = new GuzzleMockResponse('/logout');
        $handler->expect($loginResponse, 'login');

        $this->assertContains([
            'mock' => $loginResponse,
            'name' => 'login'
        ], $handler->getResponses());

        $this->assertCount(1, $handler->getResponses());

        $handler->expect($logoutResponse);

        // second response should still be "response-2" even if we dont have a "response-1"
        $this->assertContains([
            'mock' => $logoutResponse,
            'name' => 'response-2'
        ], $handler->getResponses());

        $this->assertCount(2, $handler->getResponses());
    }

    /** @test */
    public function it_will_return_the_correct_response_when_called()
    {
        $handler = new GuzzleMockHandler;

        // provide an irrelevant response to prove we get the right one back
        $loginResponse = (new GuzzleMockResponse('/login'))
            ->withStatus(204)
            ->withBody('Success!')
            ->withHeaders(['login-header' => 'login-header__value']);
        $rogueResponse = (new GuzzleMockResponse('/logout'))
            ->withStatus(404)
            ->withBody('Fail!')
            ->withHeaders(['logout-header' => 'logout-header__value']);

        $handler->expect($loginResponse);
        $handler->expect($rogueResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $response = $guzzle->get('/login');
        $expectedResponse = $loginResponse->asGuzzleResponse();

        $this->assertEquals($expectedResponse->getHeaders(), $response->getHeaders());
        $this->assertEquals('Success!', json_decode((string) $response->getBody()));
        $this->assertEquals(204, $response->getStatusCode());
    }

    /** @test */
    public function it_can_assert_responses_are_called_in_the_right_order()
    {
        $handler = new GuzzleMockHandler;

        $loginResponse = new GuzzleMockResponse('/login');
        $logoutResponse = new GuzzleMockResponse('/logout');

        $handler->expect($loginResponse, 'login');
        $handler->expect($logoutResponse, 'logout');

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->get('/login');

        $this->assertSame(['login'], $handler->getCalled());

        $guzzle->get('/logout');

        $this->assertSame(['login', 'logout'], $handler->getCalled());
        $handler->assertCalledOrder(['login', 'logout']);
    }

    /** @test */
    public function it_supports_all_verbs()
    {
        $handler = new GuzzleMockHandler;

        $getResponse = (new GuzzleMockResponse('/get'))->withMethod('get');
        $postResponse = (new GuzzleMockResponse('/post'))->withMethod('post');
        $putResponse = (new GuzzleMockResponse('/put'))->withMethod('put');
        $patchResponse = (new GuzzleMockResponse('/patch'))->withMethod('patch');
        $deleteResponse = (new GuzzleMockResponse('/delete'))->withMethod('delete');
        $optionsResponse = (new GuzzleMockResponse('/options'))->withMethod('options');
        $headResponse = (new GuzzleMockResponse('/head'))->withMethod('head');

        $handler->expect($getResponse, 'get');
        $handler->expect($postResponse, 'post');
        $handler->expect($putResponse, 'put');
        $handler->expect($patchResponse, 'patch');
        $handler->expect($deleteResponse, 'delete');
        $handler->expect($optionsResponse, 'options');
        $handler->expect($headResponse, 'head');

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->get('/get');
        $guzzle->post('/post');
        $guzzle->put('/put');
        $guzzle->patch('/patch');
        $guzzle->delete('/delete');
        $guzzle->options('/options');
        $guzzle->head('/head');

        $handler->assertCalledOrder([
            'get', 'post', 'put', 'patch', 'delete', 'options', 'head'
        ]);
    }

    /** @test */
    public function it_can_match_a_url_with_variables_in_it()
    {
        $handler = new GuzzleMockHandler;

        $userResponse = (new GuzzleMockResponse('/users/{id}'))
            ->where('id', '1');
        $productResponse = (new GuzzleMockResponse('/products/{id}'))
            ->where('id', '1');

        $handler->expect($userResponse);
        $handler->expect($productResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $response = $guzzle->get('/users/1');
        $this->assertSame(200, $response->getStatusCode());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No response set for get => /products/2');

        $response = $guzzle->get('/products/2');
        $this->assertSame(200, $response->getStatusCode());
    }


    /** @test */
    public function it_throws_an_exception_when_there_is_no_matching_response()
    {
        $handler = new GuzzleMockHandler;
        $getResponse = new GuzzleMockResponse('/get');
        $handler->expect($getResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No response set for post => /get');

        $guzzle->post('/get');
    }

    /** @test */
    public function a_response_can_be_allowed_once()
    {
        $handler = new GuzzleMockHandler;
        $getResponse = (new GuzzleMockResponse('/get'))->once();
        $handler->expect($getResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->get('/get');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No response set for get => /get');

        $guzzle->get('/get');

        $this->assertEmpty($handler->getResponses());
    }

    /** @test */
    public function it_can_run_assertions()
    {
        $handler = new GuzzleMockHandler;
        $getResponse = (new GuzzleMockResponse('/get'))->withAssertion(function (RequestInterface $request, ResponseInterface $response) {
            $this->assertInstanceOf(Request::class, $request);
            $this->assertInstanceOf(Response::class, $response);
        });
        $handler->expect($getResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->get('/get');
    }

    /** @test */
    public function it_can_assert_the_request_json_matches()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = (new GuzzleMockResponse('/login'))->withMethod('post')
            ->assertRequestJson([
                'username' => 'tomb1n0'
            ]);
        $handler->expect($loginResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('/login', [
            'json' => ['username' => 'tomb1n0']
        ]);
    }

    /** @test */
    public function it_can_assert_a_sub_property_of_the_request_json_matches()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = (new GuzzleMockResponse('/login'))->withMethod('post')
            ->assertRequestJson('tomb1n0', 'username');
        $handler->expect($loginResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('/login', [
            'json' => ['username' => 'tomb1n0']
        ]);
    }

    /** @test */
    public function it_can_assert_the_headers_match()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = (new GuzzleMockResponse('/login'))->withMethod('post')
            ->assertRequestHeaders([
                'User-Agent' => [
                    'phpunit'
                ],
                'foo' => [
                    'bar'
                ]
            ]);
        $handler->expect($loginResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('/login', ['headers' => [
            'User-Agent' => [
                'phpunit'
            ],
            'foo' => 'bar'
        ]]);
    }

    /** @test */
    public function it_can_assert_a_specific_header_is_correct()
    {
        $handler = new GuzzleMockHandler;
        $loginResponse = (new GuzzleMockResponse('/login'))->withMethod('post')
            ->assertRequestHeaders(
                [
                    'bar'
                ],
                'foo'
            );
        $handler->expect($loginResponse);

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('/login', ['headers' => [
            'User-Agent' => [
                'phpunit'
            ],
            'foo' => 'bar'
        ]]);
    }

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
