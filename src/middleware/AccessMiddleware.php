<?php
namespace Architect\middleware;

use Slim\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AccessMiddleware
{
	private $access = 0;
	
	public function __construct( int $access )	{
		$this->access = $access;
	}
	/**
	 * @param  Request  $request PSR-7 request
	 * @param  RequestHandler $handler PSR-15 request handler
	 *
	 * @return Response
	 */
	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$payload = $request->getAttribute("token");
		if ( $this->access > $payload['rel'] )	{
			throw new HttpUnauthorizedException($request, null);
		}
		$response = $handler->handle($request);
		return $response;
	}
}
