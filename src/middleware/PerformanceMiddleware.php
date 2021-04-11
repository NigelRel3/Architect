<?php
namespace Architect\middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class PerformanceMiddleware
{
	/**
	 * @param  Request  $request PSR-7 request
	 * @param  RequestHandler $handler PSR-15 request handler
	 *
	 * @return Response
	 */
	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		$response = $handler->handle($request);
		if ( isset($_ENV['PERFORMANCE_LOG']) )	{
			$id = mt_rand(1, 2147483648);
			$stats = getrusage();
			$stats["ID"] = $id;
			$stats["PeakMemUsage"] = memory_get_peak_usage();
			$stats["MemUsage"] = memory_get_usage();
			$response = $response->withAddedHeader("PERFORMANCEID", $id);
			
			file_put_contents($_ENV['PERFORMANCE_LOG'], json_encode($stats) . PHP_EOL, 
					FILE_APPEND | LOCK_EX);
		}
		return $response;
	}
}
