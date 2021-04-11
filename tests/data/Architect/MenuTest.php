<?php

use PHPUnit\Framework\TestCase;
use Architect\data\architect\Workspace;
use Architect\data\architect\Menu;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__. "/../MySQLTestUtils.php";

class MenuTest extends TestCase   {
	use MySQLTestUtils;

    private $db;

    protected $setUp = [
    		"SET FOREIGN_KEY_CHECKS=0",
    		"delete from Menu",
    		"delete from User",
    		"delete from Workspace",
    		"SET FOREIGN_KEY_CHECKS=1",
    ];
    protected $insert = ["User" => "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)
				on duplicate key update DefaultWorkspaceID = ?",
    		"Workspace" => "insert into Workspace
					(id, Name, UserID)
				values ( ?, ?, ?)",
    		"MenuConf" => "insert into Menu
					(id, `Key`, title, WorkspaceID, folder, parentMenuID,
						componentID, config)
				values ( ?, ?, ?, ?, ?, ?, 1, ?)",
    		"MenuTree" => "insert into Menu
					(id, `Key`, title, WorkspaceID, folder, parentMenuID, componentID)
				values ( ?, ?, ?, ?, ?, ?, 1)",
    ];

    public function testGet () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
    	]
    	], "User");
    	$this->insert([[1, 'ws1', 1]
    	], "Workspace");
    	$this->insert([[1, 1, "t1", 1, 1, null],
    			[2, 2, "t2", 1, 1, 1],
    			[3, 3, "t3", 1, 0, null],
    			[4, 4, "t4", 1, 0, 2],
    	], "MenuTree");

    	$db = $this->getConnection();
    	$m = new Menu($db);
		$menus = $m->getWorkspaceMenu(1);
		$this->assertNotNull($menus);
		$this->assertCount(2, $menus);
		$this->assertEquals(1, $menus[0]['key']);
		$this->assertEquals(3, $menus[1]['key']);
		$this->assertEquals(2, $menus[0]['children'][0]['key']);
		$this->assertEquals(4, $menus[0]['children'][0]['children'][0]['key']);
    }

    public function testGetMenu () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
    	]
    	], "User");
    	$this->insert([[1, 'ws1', 1]
    	], "Workspace");
    	$this->insert([[1, 1, "t1", 1, 1, null, '{"testid": 1}'],
    			[2, 2, "t2", 1, 1, 1, '{"testid": 2}'],
    			[3, 3, "t3", 1, 0, null, '{"testid": 3}'],
    			[4, 4, "t4", 1, 0, 2, '{"testid": 4}'],
    	], "MenuConf");

    	$db = $this->getConnection();
    	$m = new Menu($db);
    	$menus = $m->getWorkspaceMenu(1);
    	$this->assertNotNull($menus);

    	$menu = $m->extractMenu(3, $menus);
    	$this->assertEquals("3", $menu['data']['ComponentData']['testid']);
    }

}