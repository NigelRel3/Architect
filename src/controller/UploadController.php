<?php
namespace Architect\controller;

use Architect\data\architect\StatsLoad;
use Architect\data\architect\User;
use Architect\data\architect\Workspace;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UploadController    {
	use UtilTrait;

	private $user = null;
	private $workspace = null;
	private $statsLoad = null;
	private $uploadDirectory = null;
	private $container = null;

    public function __construct( User $user,
    		Workspace $workspace,
    		StatsLoad $statsLoad,
    		ContainerInterface $contaner,
    		string $uploadDirectory )    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->statsLoad = $statsLoad;
        $this->container = $contaner;
        $this->uploadDirectory = $uploadDirectory;
    }

    public function upload ( Request $request, Response $response  )   {
    	$responseCode = 200;

    	$params = $request->getParsedBody();
    	$this->statsLoad->Name = $params['name'];
    	$this->statsLoad->CreatedOn = new \DateTime();
    	$this->statsLoad->Notes = $params['notes'] ?? null;
    	$this->statsLoad->OwnerID = $params['userID'];
    	$this->statsLoad->config = $params['config'] ?? null;
    	$this->statsLoad->ImportType = $params['importType'];
    	$this->statsLoad->Group = $params['group'] ?? null;
    	$this->statsLoad->GroupKey = $params['groupKey'] ?? 0;

    	$uploadedFiles = $request->getUploadedFiles();
    	if ( isset($uploadedFiles['fileAttachment']) )	{
    		$importFile = $uploadedFiles['fileAttachment'];
    		if ( $importFile->getError() === UPLOAD_ERR_OK )	{
    			$extension = pathinfo($importFile->getClientFilename());
    			$fileName =  $this->uploadDirectory .
    				$params['userID'] . "_" . $params['workspace'] . "_" .
    				uniqid(rand(), true) . "." .
    				$extension['extension'] ?? 'dat';
      			$importFile->moveTo( $fileName );
      			chmod($fileName, 0666);
      			$this->statsLoad->DataSource = $fileName;
    		}
    	}
    	$this->statsLoad->insert();
    	$data = [ "loadID" => $this->statsLoad->id ];

    	$importClassName = "Architect\\services\\" . $params['importType'];
    	if ( class_exists($importClassName, true)) {
    		$importer = $this->container->get($importClassName);
    		$data['LoadInfo'] = $importer($this->statsLoad);
    	}
    	else	{
    		$responseCode = 500;
    		$data['Message'] = "Import class not found: " .
    			$importClassName;
    	}

        return $this->buildResponse($response, $data, $responseCode);
    }

}