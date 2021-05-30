<?php
namespace Architect\controller;

use Architect\data\architect\AvailablePanelTypes;
use Architect\data\architect\Menu;
use Architect\data\architect\StatsType;
use Architect\data\architect\User;
use Architect\data\architect\Workspace;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController    {
	use UtilTrait;

	private $user = null;
	private $workspace = null;
	private $panelTypes = null;
	private $menu = null;
	private $statsType = null;
	private $sec = null;

    public function __construct( User $user,
    		Workspace $workspace,
    		AvailablePanelTypes $panelTypes,
    		Menu $menu,
    		StatsType $statsType,
    		string $sec )    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->panelTypes = $panelTypes;
        $this->menu = $menu;
        $this->statsType = $statsType;
        $this->sec = $sec;
    }

    public function login ( Request $request, Response $response  )   {
        $responseCode = 200;
        $args = $request->getParsedBody();
        $data = [];

        if ( $this->user->login($args['username'] ?? '', $args['password'] ?? '') ) {
            // Generate JWT token
            $future = new \DateTime("now +2 hours");
            $payload = [
            	"iat" => (new \DateTime())->getTimeStamp(),
            	"exp" => $future->getTimeStamp(),
            	"id" => $this->user->id,
            	"rel" => $this->user->Access
            ];
            $token = JWT::encode($payload, $this->sec, "HS256");

            $data = [ "Name" => $this->user->Name ];
            $expirationMinutes = 60;
            $expiry = new \DateTimeImmutable('now + '.$expirationMinutes.'minutes');
            $response = $response->withAddedHeader('Set-Cookie', "JWT=" .
            	urlencode($token) . '; expires=' . $expiry->format(\DateTime::COOKIE) .
            	'; Max-Age=' . $expirationMinutes * 60 . '; path=/; SameSite=Strict');
        }
        else    {
            $responseCode = 401;
        }

        return $this->buildResponse($response, $data, $responseCode);
    }

    public function loginData ( Request $request, Response $response  )   {
    	$responseCode = 200;
    	$data = [];
    	$id = $request->getAttribute("token")['id'];

    	if ( $this->user->fetch([$id]) )	{
    		$data["Name"] = $this->user->Name;
	    	$wsData = $this->extractData($this->workspace->getUserWorkspaces($this->user));
	    	$paneID = 0;
	    	foreach ( $wsData as &$ws )	{
	    		$menus = $this->menu->getWorkspaceMenu($ws['id']);
	    		$ws['Menu'] = $menus;
	    		$newWindows = [];
	    		foreach ( $ws['Windows'] as $element )	{
	    			$newWindows[] = $this->processPanes($element, $menus, $paneID);
	    		}
	    		$ws['Windows'] = $newWindows;
	    		$ws['Config']['nextPaneID'] = $paneID;
	    	}
	    	$data['Workspaces'] = array_column($wsData, null, "id");
	    	$data['DefaultWorkspace'] = $this->user->DefaultWorkspaceID ?? 0;
	    	$panels = $this->extractData($this->panelTypes->fetchAll("id"));
	    	$data['Panels'] = array_column($panels, null, "id");
	    	$statTypes = $this->statsType->fetchAllTypes();
			$data['StatsType'] = array_column($statTypes, null, 'Name');
// 	    	$statsTypes = $this->statsType->fetchUsedTypes();
// 	    	$data['StatsType'] = array_column($statsTypes, null, "id");
	    	// Add in dynamic menu panels
	    	$this->processMenuPanels($data['Workspaces'], $data['Panels']);
    	}
    	else	{
    		$responseCode = 404;
    	}

    	return $this->buildResponse($response, $data, $responseCode);
    }

    protected function processPanes ( $pane, $menus, int &$paneID )	{
    	$newTab = $pane;
    	$openToThisPanel = $pane['openToThisPanel'] ?? false;

    	if ( $pane['type'] == "tab" )	{
    		if ( $pane['key'] === 0 )	{
    			$newTab = ['ComponentID' => 1,
    				'title' => 'Main Menu',
    				'paneID' => $paneID++,
    				"key" => 0,
    				'type' => 'tab'
    				];
    		}
    		else	{
    			$keyNode = $this->menu->extractMenu($pane['key'], $menus);
    			if ( !$keyNode )	{
    				throw new \Exception("Menu item not found: {$pane['key']}");
    			}
    			$newTab = [
		    		'ComponentData' => $keyNode['data']['ComponentData'],
		    		'ComponentID' => $keyNode['data']['MenuData']['ComponentID'],
		    		'title' => $keyNode['title'],
		    		'key' => $keyNode['key'],
		    		'paneID' => $paneID++,
    				'openToThisPanel' => $openToThisPanel,
    				'type' => 'tab'
	    		];
	    	}
    	}
    	elseif ( $pane['type'] == "window"  )	{
    		$out = [
    			'type' => 'window',
    			'openToThisPanel' => $openToThisPanel,
    		];
    		foreach ( $pane['children'] as $childPane )	{
    			$out['children'][] = $this->processPanes($childPane, $menus, $paneID);
    		}
    		$newTab = $out;
    	}
    	elseif ( $pane['type'] == "pane" )	{
    		$out = [];
    		foreach ( $pane['children'] as $childPane )	{
    			$out[] = $this->processPanes($childPane, $menus, $paneID);
    		}
    		$newTab['children'] = $out;
    		$newTab['openToThisPanel'] = $openToThisPanel;
    	}

    	return $newTab;
    }

    protected function processMenuPanels ( array &$workspaces, array $panels)	{
    	$menuPanels = [];
    	foreach ( $panels as $panel )	{
    		if ($panel['ParentMenuKey'])	{
    			$menuPanels[$panel['ParentMenuKey']][] = $panel;
    		}
    	}
    	// If no menu panels exit
    	if ( empty($menuPanels) )	{
    		return;
    	}
    	foreach ( $workspaces as &$workspace )	{
    		$nextKey = $workspace['Menu'][0]['data']['MenuData']['nextKey'];
    		$this->addMenuPanels ( $workspace['Menu'],
    				array_keys($menuPanels), $menuPanels, $nextKey );
    		$workspace['Menu'][0]['data']['MenuData']['nextKey'] = $nextKey;
    	}
    }

    protected function addMenuPanels ( &$menu, array $menuKeys, array $panels, int &$nextKey)	{
    	foreach ( $menu as &$menuNode )	{
    		if ( isset($menuNode['children']))	{
    			$this->addMenuPanels ( $menuNode['children'], $menuKeys, $panels, $nextKey );
    		}
    		if ( in_array($menuNode['key'], $menuKeys) )	{
    			foreach ( $panels[$menuNode['key']] as $menuItem )	{
    				$newMenuItems = $menuItem['Config']['menu'];
    				if (!isset($newMenuItems['key']))	{
    					$newMenuItems['key'] = $nextKey++;
    					$newMenuItems['SystemAdded'] = true;
    				}
    				$menuNode['children'][] = $newMenuItems;
    			}
    		}
    	}
    }

    public function save (Request $request, Response $response  )   {
    	$responseCode = 200;
    	$args = $request->getParsedBody();
    	$data = [];
    	$this->workspace->fetch([$args['id']]);
    	$this->workspace->Name = $args['Name'];
    	$this->workspace->Config =  $args['Config'];
		$this->workspace->Windows = $this->saveWindows($args['Windows']);
    	$configSaved = $this->workspace->update();
    	$configSaved = true;
    	$menuSaved = $this->saveMenu($args['id'], null, $args['Menu']);

    	$data = [ 'configSaved' => $configSaved, 'menuSaved' => $menuSaved ];
    	return $this->buildResponse($response, $data, $responseCode);
    }

    protected function saveWindows(array $panes) : array	{
    	$out = [];
    	foreach ( $panes as $pane )		{
    		if ( $pane['type'] == 'window'  || $pane['type'] == 'pane' )		{
    			$newPane  = $pane;
    			if ( isset($pane['children']))	{
    				$newPane['children'] = $this->saveWindows($pane['children']);
    			}
    			$out[] = $newPane;
    		}
    		elseif ($pane['type'] == 'tab' )     {
    			$out[] = [
    				'type' => 'tab',
    			    'key' => $pane['key']
    			];
    		}
    	}
    	return $out;
    }

    protected function saveMenu ( int $workspaceID, $parentMenu, array $menu ): bool	{

    	// TODO removing menu items no longer referenced

		$saved = true;
		foreach ( $menu as $menuItem )	{
			$existing = $this->menu->fetchByKey($workspaceID, $menuItem['key']);
			$this->menu->key = $menuItem['key'];
			$this->menu->title = $menuItem['title'];
			$this->menu->WorkspaceID = $workspaceID;
			$this->menu->icon = $menuItem['icon'] ?? null;
			$this->menu->folder = $menuItem['folder'] ?? false;
			$this->menu->editable = $menuItem['data']['MenuData']['editable'];
			$this->menu->addFolder = $menuItem['data']['MenuData']['addFolder'];
			$this->menu->addContents = $menuItem['data']['MenuData']['addContents'];
			$this->menu->deleteable = $menuItem['data']['MenuData']['deleteable'];
			$this->menu->nextKey = $menuItem['data']['MenuData']['nextKey'];
			$this->menu->componentID = $menuItem['data']['MenuData']['ComponentID'] ?? null;
			$this->menu->parentMenuID = $parentMenu;
			$this->menu->addIcon = $menuItem['data']['MenuData']['addIcon'];
			// Remove any temporary data from the config
			unset($menuItem['data']['ComponentData']['temp']);
			$this->menu->config = $menuItem['data']['ComponentData'];
			$this->menu->expanded = $menuItem['expanded'] ?? false;
			$this->menu->addChildData = $menuItem['data']["MenuData"]['addChildData'] ?? null;
			$this->menu->addIcon = $menuItem['data']['MenuData']['addIcon'];
			if ( $existing )	{
				$saved = $this->menu->update();
			}
			else	{
				$saved = $this->menu->insert();
			}
			if ( $saved  && isset ($menuItem['children']) )	{
				$this->saveMenu ( $workspaceID, $this->menu->id, $menuItem['children']);
			}

		}
		return $saved;
    }
}