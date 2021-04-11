<?php
namespace Architect\controller;

use Psr\Http\Message\ResponseInterface as Response;

trait UtilTrait	{
	protected function extractData ( array $entityList ): array	{
		$data = [];
		foreach ( $entityList as $entity )	{
			$data[] = $entity->get();
		}
		return $data;
	}
	
	protected function buildResponse ( Response $response,
			$data, int $status = 200 ): Response	{
		$response = $response->withHeader('Content-type', 'application/json')
			->withStatus($status);
		$body = $response->getBody();
		$body->write( json_encode($data, JSON_UNESCAPED_SLASHES));
		return $response;
	}
}