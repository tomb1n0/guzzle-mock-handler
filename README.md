# Guzzle Mock Handler

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tomb1n0/guzzle-mock-handler.svg?style=flat-square)](https://packagist.org/packages/tomb1n0/guzzle-mock-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/tomb1n0/guzzle-mock-handler.svg?style=flat-square)](https://packagist.org/packages/tomb1n0/guzzle-mock-handler)

When testing third party APIs it is often challenging to mock them in a way that's simple and declarative. This package aims to help make this process simpler by providing a custom handler for guzzle that has router-like behaviour, rather than relying on the responses being popped off the stack in any particular order.

## Installation

You can install the package via composer:

```bash
composer require tomb1n0/guzzle-mock-handler
```

## Usage

### Basic Example
```php
use GuzzleHttp\Client;
use Tomb1n0\GuzzleMockHandler\GuzzleMockHandler;

// Create a new instance of the mock handler
$handler = new GuzzleMockHandler;

// Create a new mock response for '/login', returning ['key' => 'value'] in the body.
// By default responses expect a GET verb, and return a 200 response.
$loginResponse = (new GuzzleMockResponse('/login'))->withBody([
    'key' => 'value'
]);

// Tell the handler that we're expecting this response
$handler->expect($loginResponse);

// Create a new Guzzle Handlerstack, passing in our custom handler
$stack = HandlerStack::create($handler);

// Finally, create the guzzle client, passing our stack in
$guzzle = new Client(['handler' => $stack]);

$response = $guzzle->get('/login');

// A normal guzzle response object
$response->getStatusCode(); // == 200
json_decode((string) $response->getBody()); // == ['key' => 'value']
```

### Request Assertion

Sometimes it is useful to perform assertions on the request that returned your response. Maybe you have a class that logs in to a third party API, and you want to assert the username and password were sent through correctly.

```php
$handler = new GuzzleMockHandler;
$loginResponse = (new GuzzleMockResponse('/login'))
    ->withMethod('post')
    ->assertRequestJson([
        'username' => 'tomb1n0',
        'password' => 'correct-horse-battery-staple'
    ]);
    // NOTE: If you only care about the username in this case, you can pass in a key as the second parameter to assertRequestJson like so:
    /**
     * ->assertRequestJson('tomb1n0, 'username');
     **/

$handler->expect($loginResponse);

$stack = HandlerStack::create($handler);
$guzzle = new Client(['handler' => $stack]);

// Just before the response is actually sent back to guzzle, our handler will assert the request JSON is corect.
$response = $guzzle->post('/login', [
    'json' => [
        'username' => 'tomb1n0',
        'password' => 'correct-horse-battery-staple'
    ]
]);
```

Note: You can also perform the exact same assertions using `->assertRequestHeaders()`, this will allow you to ensure API requests contain a `X-API-KEY` header or similar.

### Custom Assertions

Asserting the body or headers might not be enough, so we allow you to call `->withAssertion()`, passing you the request and response objects, so you can perform your own assertions:

```php
$handler = new GuzzleMockHandler;
$loginResponse = (new GuzzleMockResponse('/login'))
    ->withMethod('post')
    // if you want to perform multiple assertions, you can call ->withAssertion multiple times.
    ->withAssertion(function(RequestInterface $request, ResponseInterface $response) {
        $this->assertEquals('super-secure-key', $request->getHeader('X-API-KEY'));
    });

$handler->expect($loginResponse);

$stack = HandlerStack::create($handler);
$guzzle = new Client(['handler' => $stack]);

$guzzle->post('/login');
```

### Asserting Order

Sometimes it is useful to assert API calls were made in the correct order. Maybe you have to call `/login` before you fetch `/users` for example. This is achieved by giving a name to your responses, then asserting the order after your calls have been made.

```php
$handler = new GuzzleMockHandler;
$loginResponse = (new GuzzleMockResponse('/login'))->withMethod('post');
$usersResponse = new GuzzleMockResponse('/users');

$handler->expect($loginResponse, 'login-response');
$handler->expect($usersResponse, 'users-response');

$stack = HandlerStack::create($handler);
$guzzle = new Client(['handler' => $stack]);

$guzzle->post('/login');
$guzzle->get('/users');

// Performs a assertsEquals behind the scenes, as the handler keeps track of the order calls were made in.
$handler->assertCalledOrder([
    'login-response', 'users-response'
]);
```

### Only allowing responses to be called once

Sometimes you might want to only allow an endpoint to be called once in your tests - this can be achieved by calling `->once()` on your response object.

```php
$handler = new GuzzleMockHandler;
$loginResponse = (new GuzzleMockResponse('/login'))
    ->withMethod('post')
    ->once();

$handler->expect($loginResponse);

$stack = HandlerStack::create($handler);
$guzzle = new Client(['handler' => $stack]);

$response = $guzzle->post('/login'); // successfull

$response = $guzzle->post('/login'); // ResponseNotFound exception is thrown, "No response set for post => /login"
```

### Testing

``` bash
composer test
```


## Credits

- [Tom Harper](https://github.com/tomb1n0)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).