<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class Menu extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "Menu";
	protected static $fields = ["id", "key", "title", "WorkspaceID", "icon", "folder", "editable", "addFolder", "addContents", "deleteable", "nextKey", "componentID", "parentMenuID", "addIcon", "config", "expanded", "addChildData"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( empty($output["id"]) )	{
			$output["id"] = null;
		}
		if ( is_null($output["folder"]) )	{
			$output["folder"] = "0";
			$this->data["folder"] = $output["folder"];
		}
		if ( !is_null($output["folder"]) && is_bool($output["folder"]) )	{
			$output["folder"] = (int)$output["folder"];
		}
		if ( is_null($output["editable"]) )	{
			$output["editable"] = "0";
			$this->data["editable"] = $output["editable"];
		}
		if ( !is_null($output["editable"]) && is_bool($output["editable"]) )	{
			$output["editable"] = (int)$output["editable"];
		}
		if ( is_null($output["addFolder"]) )	{
			$output["addFolder"] = "0";
			$this->data["addFolder"] = $output["addFolder"];
		}
		if ( !is_null($output["addFolder"]) && is_bool($output["addFolder"]) )	{
			$output["addFolder"] = (int)$output["addFolder"];
		}
		if ( is_null($output["addContents"]) )	{
			$output["addContents"] = "0";
			$this->data["addContents"] = $output["addContents"];
		}
		if ( !is_null($output["addContents"]) && is_bool($output["addContents"]) )	{
			$output["addContents"] = (int)$output["addContents"];
		}
		if ( is_null($output["deleteable"]) )	{
			$output["deleteable"] = "0";
			$this->data["deleteable"] = $output["deleteable"];
		}
		if ( !is_null($output["deleteable"]) && is_bool($output["deleteable"]) )	{
			$output["deleteable"] = (int)$output["deleteable"];
		}
		if ( is_null($output["nextKey"]) )	{
			$output["nextKey"] = "0";
			$this->data["nextKey"] = $output["nextKey"];
		}
		if ( !empty($output["config"]) )	{
			$output["config"] = json_encode($output["config"]);
		}
		if ( is_null($output["expanded"]) )	{
			$output["expanded"] = "0";
			$this->data["expanded"] = $output["expanded"];
		}
		if ( !is_null($output["expanded"]) && is_bool($output["expanded"]) )	{
			$output["expanded"] = (int)$output["expanded"];
		}
		if ( !empty($output["addChildData"]) )	{
			$output["addChildData"] = json_encode($output["addChildData"]);
		}
		// FormatToDBEnd
        return $output;
    }

    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		if ( !is_null($row["key"]) )	{
			$row["key"] = intval($row["key"]);
		}
		if ( !is_null($row["WorkspaceID"]) )	{
			$row["WorkspaceID"] = intval($row["WorkspaceID"]);
		}
		if ( !is_null($row["folder"]) )	{
			$row["folder"] = (bool)$row["folder"];
		}
		if ( !is_null($row["editable"]) )	{
			$row["editable"] = (bool)$row["editable"];
		}
		if ( !is_null($row["addFolder"]) )	{
			$row["addFolder"] = (bool)$row["addFolder"];
		}
		if ( !is_null($row["addContents"]) )	{
			$row["addContents"] = (bool)$row["addContents"];
		}
		if ( !is_null($row["deleteable"]) )	{
			$row["deleteable"] = (bool)$row["deleteable"];
		}
		if ( !is_null($row["nextKey"]) )	{
			$row["nextKey"] = intval($row["nextKey"]);
		}
		if ( !is_null($row["componentID"]) )	{
			$row["componentID"] = intval($row["componentID"]);
		}
		if ( !is_null($row["parentMenuID"]) )	{
			$row["parentMenuID"] = intval($row["parentMenuID"]);
		}
		if ( !empty($row["config"]) )	{
			$row["config"] = json_decode($row["config"], self::$jsonArrayMode);
		}
		if ( !is_null($row["expanded"]) )	{
			$row["expanded"] = (bool)$row["expanded"];
		}
		if ( !empty($row["addChildData"]) )	{
			$row["addChildData"] = json_decode($row["addChildData"], self::$jsonArrayMode);
		}
		// FormatFromDBEnd
    }

    protected function buildSubMenus ( $menuID, $menuItems )	{
    	$menu = [];
    	foreach ( $menuItems as $subItem )	{
    		if ( $subItem->parentMenuID == $menuID )	{
    			// Convert to JSON format
    			$jsonMenu = [
    				'key' => intval($subItem->key),
    				'title' => $subItem->title,
    				'folder' => boolval($subItem->folder),
    				'icon' => $subItem->icon,
    				'data' => [ 'MenuData' =>
    					[
    						'editable' => boolval($subItem->editable),
    						'addContents' => boolval($subItem->addContents),
    						'addFolder' => boolval($subItem->addFolder),
    						'addIcon' => $subItem->addIcon,
    						'nextKey' => $subItem->nextKey,
    						'deleteable' => boolval($subItem->deleteable),
    						'ComponentID' => intval($subItem->componentID)
    					],
    					'ComponentData' => $subItem->config
    				]
    			];

    			if ( isset($subItem->config['addChildData']) )	{
    				$jsonMenu['data']['MenuData']['addChildData'] = $subItem->config['addChildData'];
    				unset($jsonMenu['data']['ComponentData']['addChildData']);
    			}
    			if ( $jsonMenu['folder'] )	{
    				$subMenus = $this->buildSubMenus($subItem->id, $menuItems);
    				if ( !empty($subMenus) )	{
    					$jsonMenu['children'] = $subMenus;
    				}
    			}
    			$menu[] = $jsonMenu;
    		}
    	}

    	return $menu;
    }

    public function getWorkspaceMenu ( int $wsID ) : array	{
    	$menus = $this->fetchWhere ( "WorkspaceID = :WorkspaceID",
    			["WorkspaceID" => $wsID],
    			"id");
    			return $this->buildSubMenus(null, $menus);
    }

    public function extractMenu( int $key, $menus )	{
    	foreach ( $menus as $menu )	{
    		if ( $menu['key'] == $key )	{
    			return $menu;
    		}
    		if ( !empty($menu['children']))	{
    			$sub = $this->extractMenu($key, $menu['children']);
    			if ( $sub != null )	{
    				return $sub;
    			}
    		}
    	}
    	return null;
    }
}
