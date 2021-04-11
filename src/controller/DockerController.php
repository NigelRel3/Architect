<?php
namespace Architect\controller;

use Architect\data\architect\DataPoint;
use Architect\data\architect\StatsLoad;
use Architect\services\DataPointImport;
use Architect\util\DockerManager;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DockerController    {
	use UtilTrait;

	private $dm = null;
	private $panelTypes = null;
	private $statsLoad = null;
	private $dataPoint = null;
	private $client = null;
	private $tmpDir = null;

	public function __construct( DockerManager $dm,
			Client $client,
			DataPoint $dataPoint,
			StatsLoad $statsLoad,
			string $tmpDir)    {
		$this->dm = $dm;
		$this->client = $client;
		$this->dataPoint = $dataPoint;
		$this->statsLoad = $statsLoad;
		$this->tmpDir = $tmpDir;
    }

    public function listContainers ( Request $request, Response $response  )   {
        $responseCode = 200;
        $data = $this->dm->listContainers();

        return $this->buildResponse($response, $data, $responseCode);
    }

    public function saveStats ( Request $request, Response $response, $args  )   {
    	$responseCode = 200;
    	$data = [ "Completed" =>"OK" ];

    	/**
    	 * Params to deal with:
    	 * 		id - id of dataload
    	 * 		interval - length to poll
    	 * 		list - json list of containers
    	 * 		lastID's - optional json list of previous ID's
    	 *
    	 * Some extra info
    	 * https://www.datadoghq.com/blog/the-docker-monitoring-problem/
    	 */
    	$interval = min(max($args['interval'], 2), 20);
    	$containerList = json_decode($args['list'], true);

    	$importProc = [];
    	foreach ( $containerList as $container )	{
    		$importProc[$container] = new DataPointImport($args['id'],
    				$this->dataPoint, $container);
    	}
    	if ( isset($args['lastids']) )	{
    		$lastIDs = json_decode($args['lastids'], true);
    		foreach ( $containerList as $container)	{
    			$importProc[$container]->loadPrevStats($lastIDs[$container]);
    		}
    	}
    	$promises = [];
    	foreach ( $containerList as $container )	{
    		$promises [$container] = $this->client->getAsync(
    			"/containers/{$container}/stats?stream=true",
	    		[
	    			'timeout' => $interval,
	    			'sink' => $importProc[$container],
	    			'http_errors' => false
	    		]
    		);
    	}

    	$returnStatus = Promise\Utils::settle($promises)->wait();
    	foreach ( $returnStatus as $status)	{
    		if ( $status['state'] == "rejected" )	{
    			if ( $status['reason'] instanceof \PDOException ){
    				throw $status['reason'];
    			}
    		}
    	}

    	foreach ( $importProc as $container => $proc )	{
    		$data['ids'][$container] = $proc->getIDs();
    	}
    	return $this->buildResponse($response, $data, $responseCode);
    }

    public function saveStatsComplete ( Request $request, Response $response, $args  )   {
    	$responseCode = 200;
    	$data = [ "Completed" =>"OK" ];

    	$id = $args['id'];
		$range = $this->dataPoint->timeRangeForLoadID($id);
		if ( $this->statsLoad->fetch([$id]) )	{
			$this->statsLoad->DataStartPoint = new \DateTime($range['start']);
			$this->statsLoad->DataEndPoint = new \DateTime($range['end']);
			$this->statsLoad->update();
		}
		else	{
			$responseCode = 404;
		}

    	return $this->buildResponse($response, $data, $responseCode);
    }

}