<?php

use PHPUnit\Framework\TestCase;
use Architect\data\architect\Workspace;
use Architect\data\architect\User;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__. "/../MySQLTestUtils.php";

class WorkspaceTest extends TestCase   {
	use MySQLTestUtils;
	
    private $db;
    
    protected $setUp = [
    		"SET FOREIGN_KEY_CHECKS=0",
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
				values ( ?, ?, ?)"
    ];
    
    public function testGet () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
    	]
    	], "User");
    	$this->insert([[1, 'ws1', 1]
    	], "Workspace");
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', 1
    	]
    	], "User");
    	$db = $this->getConnection();
		$u = new User($db);
		$this->assertTrue($u->fetch([1]));
		$w = new Workspace($db);
		$ws = $w->getUserWorkspaces($u);
		$this->assertCount(1,$ws);
		$this->assertEquals('ws1', $ws[0]->Name);
    }

    public function testGetMulti () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
    			[2, 'admin1', 'nigelrel3@yahoo.co.uk',
    					'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    					255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
    	], "User");
    	$this->insert([[1, 'ws1', 1], [2, 'ws1', 2], [3, 'ws1a', 1], [4, 'ws1b', 1]
    	], "Workspace");
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', 1
    	]
    	], "User");
    	$db = $this->getConnection();
    	$u = new User($db);
    	$this->assertTrue($u->fetch([1]));
    	$w = new Workspace($db);
    	$ws = $w->getUserWorkspaces($u);
    	$this->assertCount(3,$ws);
    	$this->assertEquals('ws1', $ws[0]->Name);
    }
}