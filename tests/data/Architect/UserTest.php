<?php

use PHPUnit\Framework\TestCase;
use Architect\data\architect\User;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__. "/../MySQLTestUtils.php";

class UserTest extends TestCase   {
	use MySQLTestUtils;
	
    private $db;
    
    protected $setUp = [
    		"SET FOREIGN_KEY_CHECKS=0",
    		"delete from User",
    		"delete from Workspace",
    		"SET FOREIGN_KEY_CHECKS=1",
    ];
    protected $insert = "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)";
    
    public function testGet () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
		$u = new User($db);
		
		$this->assertTrue($u->fetch([1]));
		$this->assertEquals("55f910de-7405-11ea-ac31-0242ac110004", $u->uuid);
    }

    public function testLogin () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$this->assertTrue($u->login("admin", "ab34#lka4]"));
    }    
 
    public function testLoginInvalidUser () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$this->assertFalse($u->login("admin1", "ab34#lka4]"));
    }
    
    public function testLoginInvalidPassword () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$this->assertFalse($u->login("admin", "ab34#lka4]1"));
    }
    
    public function testFindUUID () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$user = $u->findUUID('55f910de-7405-11ea-ac31-0242ac110004');
    	$this->assertInstanceOf( User::class, $user);
    	$this->assertEquals(1, $user->id);
    }
    
    public function testFindInvalidUUID () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$user = $u->findUUID('55f910de7405-11ea-ac31-0242ac110004');
    	$this->assertFalse($user);
    }
    
    public function testValidate () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
    	]);
    	$db = $this->getConnection();
    	$u = new User($db);
    	
    	$user = $u->findUUID('55f910de-7405-11ea-ac31-0242ac110004');
    	$this->assertEquals([], $user->validate());
    }
}