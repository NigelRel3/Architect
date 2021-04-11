<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class Menu extends MySQL	{
	protected static $table = "Menu";
	protected static $fields = ["id", "key", "title", "WorkspaceID", "icon",
			"folder", "editable", "addFolder", "addContents", "deleteable",
			"nextKey", "componentID", "parentMenuID", "addIcon", "config"];
	protected static $dateFields = [];
    protected static $jsonFields = ["config"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

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
