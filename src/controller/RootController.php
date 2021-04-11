<?php
namespace Architect\controller;

use Architect\data\architect\AvailablePanelTypes;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RootController    {
	use UtilTrait;

	private $panelTypes = null;

    public function __construct( AvailablePanelTypes $panelTypes)    {
        $this->panelTypes = $panelTypes;
    }

    public function load ( Request $request, Response $response  )   {
    	$page = file_get_contents("ui/main.html");

    	// Add in panels script
    	$panels = $this->panelTypes->fetchWhere("1=1", [], "id");
    	$panelTags = '';
    	$additionalSources = '';

    	// TODO what if script included multiple times?

    	foreach ( $panels as $panel)	{
    		$panelTags .= '<script src="/js/panes/'.$panel->ComponentName.'.js"></script>'.PHP_EOL;
    		$additionalSources .= $panel->Config['additionalSource'].PHP_EOL;
    	}
    	$page = str_replace("{{panelTypes}}", $panelTags, $page);
    	$page = str_replace("{{additionalSources}}", $additionalSources, $page);
    	$uri = $request->getUri();
    	$server = $uri->getScheme() . "://" . $uri->getHost();
		$page = str_replace("{{URL}}", $server, $page);

    	$response->getBody()->write($page);
    	$response = $response->withAddedHeader('Set-Cookie', 'SameSite=Strict');
    	return $response;
    }

}