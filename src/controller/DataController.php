<?php
namespace Architect\controller;

use Architect\data\architect\DataPoint;
use Architect\data\architect\StatsLoad;
use Architect\data\architect\User;
use Architect\data\architect\Workspace;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DataController    {
	use UtilTrait;

	private $user = null;
	private $workspace = null;
	private $panelTypes = null;
	private $statsLoad = null;
	private $dataPoint = null;

    public function __construct( User $user,
    		Workspace $workspace,
    		StatsLoad $statsLoad,
    		DataPoint $dataPoint )    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->statsLoad = $statsLoad;
        $this->dataPoint = $dataPoint;
    }

    public function listStatsLoads ( Request $request, Response $response, $args  )   {
        $responseCode = 200;
        $data = [];

        foreach ( json_decode($args['list'], true) as $id ){
        	if ( $this->statsLoad->fetch([$id]) )	{
        		$data[$id] = $this->statsLoad->get();
        		$types = $this->dataPoint->fetchTypesForLoadID($id);
        		$data[$id]['typesAvailable'] = array_column($types, "StatsTypeID");
        	}
        }
        return $this->buildResponse($response, $data, $responseCode);
    }

    public function getStatsData ( Request $request, Response $response, $args  )   {
    	$responseCode = 200;

    	if ( isset($args['types']) )	{
    		$data = $this->dataPoint->fetchForLoadIDTypes($args['id'],
    				json_decode($args['types'], true));
    	}
    	else	{
    		$data = $this->dataPoint->fetchForLoadID($args['id']);
    	}
    	$dataPoints = [];
    	foreach ( $data as $point )	{
    			$dataPoints[$point->SubSet ?? ''][$point->StatsTypeID][] = $point->get();
    	}
    	return $this->buildResponse($response, $dataPoints, $responseCode);
    }
}