<?php
namespace Architect\util;

require_once __DIR__ . '/../../vendor/autoload.php';

use Docker\Docker;
use Docker\DockerClientFactory;
use Monolog\Logger;

class DockerManager    {
    /**
     * @var Logger
     */
    private $log;
    /**
     * @var Docker
     */
    protected $docker;
    /**
     * @var Docker\API\Model\ContainerInfo[]
     */
    protected $containerList;
    /**
     * 
     * @var string
     */
    protected $lastMessage;

    public function __construct( string $host, Logger $log = null )   {
        $this->log = $log;
        $client = DockerClientFactory::create([
            'remote_socket' => $host,
            'ssl' => false,
        ]);
        $this->docker = Docker::create($client);
    }

    public function listContainers( array $idList = [])    {
        try {
            $this->containerList = $this->docker->containerList(["all" => true]);
            $data = [];
            foreach ( $this->containerList as $container )  {
                $id = $container->getId();
                if ( !empty($idList) && !in_array($id, $idList) )  {
                    continue;
                }
                $data [$id]['names'] = $container->getNames();
                $data [$id]['status'] = $container->getStatus();
                $data [$id]['state'] = $container->getState();
                $data [$id]['image'] = $container->getImage();
                $data [$id]['labels'] = $container->getLabels();
                $nc = $container->getNetworkSettings();
                $cn = $nc->getNetworks();
                foreach ( $cn as $network ) {
                    $data [$id]['ip'] = $network->getIPAddress();
                }
            }
        }
        catch ( \Exception $re )  {
            if ( $this->log != null )   {
                $this->log->error("List containers failed:".$re->getMessage());
            }
            $data = [];
        }
        return $data;
    }

    public function findContainer ( string $name )    {
        try {
            $container = $this->docker->containerList(["all" => true,
                "filters" => json_encode(["name" => [$name]])
            ]);
            $id = $container[0]->getId();
        }
        catch ( \Exception $re )  {
            if ( $this->log != null )   {
                $this->log->error("Find container {$name} failed:".$re->getMessage());
            }
            $id = false;
        }
        
        return $id;
    }
    
    public function startContainer( string $id ):bool    {
        $started = false;
        try {
            $this->docker->containerStart($id);
            $started = true;
        }
        catch ( \Exception $error ){
            if ( $this->log != null )   {
                $this->log->error("Start container $id failed:".$error->getMessage());
            }
        }
        return $started;
    }

    public function stopContainer( string $id ):bool    {
        $stopped = false;
        try {
            $this->docker->containerStop($id);
            $stopped = true;
        }
        catch ( \Exception $error ){
            if ( $this->log != null )   {
                $this->log->error("Stop container $id failed:".$error->getMessage());
            }
        }
        return $stopped;
    }
    
    public function statsContainer( string $id )    {
    	$stats = false;
    	try {
    		$stats = $this->docker->containerStats($id, [ "stream" => false]);
    	}
    	catch ( \Exception $error ){
    		if ( $this->log != null )   {
    			$this->log->error("Stats container $id failed:".$error->getMessage());
    		}
    	}
    	return $stats;
    }
    
    public function getContainerDetails ( string $id )  {
        return $this->listContainers([$id])[$id];
    }
    
    public function getLastMessage()    {
        return $this->lastMessage;
    }
}