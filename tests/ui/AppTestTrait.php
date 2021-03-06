<?php

use DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Container Trait.
 * Based on https://github.com/odan/slim4-skeleton/blob/master/tests/TestCase/AppTestTrait.php
 *
 */
trait AppTestTrait
{
	/** @var Container */
	protected $container;

	/** @var App */
	protected $app;

	/**
	 * Bootstrap app.
	 *
	 * @before
	 *
	 * @throws UnexpectedValueException
	 *
	 * @return void
	 */
	protected function setupContainer(): void
	{
		require __DIR__ . '/../../src/config.php';
		require __DIR__ . '/../../src/routes.php';

		$this->app = $app;

		$container = $this->app->getContainer();
		if ($container === null) {
			throw new UnexpectedValueException('Container must be initialized');
		}

		$this->container = $container;
	}

	/**
	 * Add mock to container.
	 *
	 * @param string $class The class or interface
	 *
	 * @return MockObject The mock
	 */
	protected function mock(string $class): MockObject
	{
		if (!class_exists($class)) {
			throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
		}

		$mock = $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->getMock();

		$this->container->set($class, $mock);

		return $mock;
	}

	/**
	 * Create a server request.
	 *
	 * @param string $method The HTTP method
	 * @param string|UriInterface $uri The URI
	 * @param array $serverParams The server parameters
	 *
	 * @return ServerRequestInterface
	 */
	protected function createRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
	{
		return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
	}

	/**
	 * Create a JSON request.
	 *
	 * @param string $method The HTTP method
	 * @param string|UriInterface $uri The URI
	 * @param array|null $data The json data
	 *
	 * @return ServerRequestInterface
	 */
	protected function createJsonRequest(string $method, $uri, array $data = null): ServerRequestInterface
	{
		$request = $this->createRequest($method, $uri);

		if ($data !== null) {
			$request = $request->withParsedBody($data);
		}
		$request = $request->withHeader('Accept', 'application/json');
		return $request->withHeader('Content-Type', 'application/json');
	}

	/**
	 * Create a form request.
	 *
	 * @param string $method The HTTP method
	 * @param string|UriInterface $uri The URI
	 * @param array|null $data The form data
	 *
	 * @return ServerRequestInterface
	 */
	protected function createFormRequest(string $method, $uri, array $data = null): ServerRequestInterface
	{
		$request = $this->createRequest($method, $uri);

		if ($data !== null) {
			$request = $request->withParsedBody($data);
		}

		return $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
	}

	/**
	 * Verify that the specified array is an exact match for the returned JSON.
	 *
	 * @param ResponseInterface $response The response
	 * @param array $expected The expected array
	 *
	 * @return void
	 */
	protected function assertJsonData(ResponseInterface $response, array $expected): void
	{
		$actual = (string)$response->getBody();
		$this->assertJson($actual);
		$this->assertSame($expected, (array)json_decode($actual, true));
	}

	/**
	 * Return array from JSON in response.
	 *
	 * @param ResponseInterface $response The response
	 *
	 * @return array
	 */
	protected function getJsonArrayResponse(ResponseInterface $response) : array	{
		$actual = (string)$response->getBody();
		$this->assertJson($actual);

		return json_decode($actual, true);
	}
}