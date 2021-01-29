<?php

namespace Tomb1n0\GuzzleMockHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use Tomb1n0\GuzzleMockHandler\GuzzleMockHandler;
use Tomb1n0\GuzzleMockHandler\GuzzleMockResponse;

class GuzzleMockHandlerTest extends TestCase
{
    /** @test */
    public function works_as_expected()
    {
        $handler = new GuzzleMockHandler();

        $handler->expect(
            (new GuzzleMockResponse('account/signin'))
                ->once()
                ->withStatus(200)
                ->withMethod('post')
                ->withBody([
                    'accessToken' => 'access_token1',
                    'expiry' => '2021-12-03T20:17:32.8811472Z',
                ])
                ->assertRequestJson([
                    'userName' => '',
                    'password' => ''
                ]),
            'sign-in'
        );

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('account/signin', [
            'json' => [
                'userName' => '',
                'password' => ''
            ]
        ]);
        $this->assertTrue(true);
    }

    /** @test */
    public function works_as_expected_with_param()
    {
        $handler = new GuzzleMockHandler();

        $handler->expect(
            (new GuzzleMockResponse('account/signin/{id}'))
                ->where('id', '.*')
                ->once()
                ->withStatus(200)
                ->withMethod('post')
                ->withBody([
                    'accessToken' => 'access_token1',
                    'expiry' => '2021-12-03T20:17:32.8811472Z',
                ])
                ->assertRequestJson([
                    'userName' => '',
                    'password' => ''
                ]),
            'sign-in'
        );

        $stack = HandlerStack::create($handler);
        $guzzle = new Client(['handler' => $stack]);

        $guzzle->post('account/signin/1', [
            'json' => [
                'userName' => '',
                'password' => ''
            ]
        ]);

        $this->assertTrue(true);
    }
}
