<?php

use Architect\data\architect\StatsType;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__. "/../MySQLTestUtils.php";

class StatsTypeTest extends TestCase   {
	use MySQLTestUtils;

    private $db;

    protected $setUp = [
    		"SET FOREIGN_KEY_CHECKS=0",
    		"delete from DataPoint",
    		"delete from StatsType",
    		"delete from StatsLoad",
    		"delete from StatsTypeParent",
    		"delete from User",
    		"SET FOREIGN_KEY_CHECKS=1",
    ];
    protected $insert = ["User" => "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)
				on duplicate key update DefaultWorkspaceID = ?",
    		"DataPoint" => "insert into DataPoint
					(id, StatsLoadID, StatsTypeID, TimeStamp, Value, Caption)
				values ( ?, ?, ?, ?, ?, '')",
    		"StatsLoad" => "insert into StatsLoad
					(id, Name, OwnerID, DataSource, ImportType)
				values ( ?, ?, ?, ?, ?)",
    		"StatsType" => "insert into StatsType
					(id, Name, Description)
				values ( ?, ?, ?)",
    		"StatsTypeParent" => "insert into StatsTypeParent
					(StatsTypeID, ParentTypeID)
				values ( ?, ? )",
    ];

    public function testForLoadID1 () {
    	$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
    			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
    			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
    	]
    	], "User");
    	$this->insert([[1, 't1', 't1 desc'],
    			[2, 't2', 't2 desc'],
    			[3, 't3', 't3 desc']
    	], "StatsType");
    	$this->insert([[1, "l1", 1, "SomeFile", "JTreeImport"],
    			[2, "l2", 1, "SomeFile2", "JTreeImport"]
    	], "StatsLoad");
    	$this->insert([[1, 1, 1, '1608648009', 't1 desc'],
    			[2, 1, 3, '1608648009', 't2 desc'],
    			[4, 2, 1, '1608648009', 't1 desc 2'],
    			[6, 2, 3, '1608648009', 't3 desc 2']
    	], "DataPoint");

    	$db = $this->getConnection();
    	$st = new StatsType($db);

    	$sts = $st->fetchUsedTypes();
    	$this->assertNotNull($sts);
    	$this->assertCount(2, $sts);
    	$this->assertEquals(1, $sts[0]->id);
    	$this->assertEquals('t1 desc', $sts[0]->Description);
    	$this->assertEquals('t3 desc', $sts[1]->Description);
    }

    public function testForParentID () {
    	$this->insert([[1, 't1', 't1 desc'],
    			[2, 't2', 't2 desc'],
    			[3, 't3', 't3 desc']
    	], "StatsType");
    	$this->insert([[2, 1],
    			[3, 1]
    	], "StatsTypeParent");

    	$db = $this->getConnection();
    	$st = new StatsType($db);

    	$sts = $st->findForParentID(1);
    	$this->assertNotNull($sts);
    	$this->assertCount(2, $sts);
    	$this->assertEquals('t2 desc', $sts[0]->Description);
    	$this->assertEquals('t3 desc', $sts[1]->Description);
    }

    public function testTypesFor () {
    	$this->insert([[1, 't1', 't1 desc'],
    			[2, 't2', 't2 desc'],
    			[3, 't3', 't3 desc'],
    			[4, 't4', 't4 desc'],
    			[5, 't5', 't5 desc']
    	], "StatsType");
    	$this->insert([[2, 1],
    			[3, 1],
    			[4, 3],
    			[5, 2]
    	], "StatsTypeParent");

    	$db = $this->getConnection();
    	$st = new StatsType($db);

    	$sts = $st->fetchTypesFor(1);
    	$this->assertNotNull($sts);
    	$this->assertCount(2, $sts, print_r($sts, true));
    	$this->assertEquals(2, $sts[0]['id']);
    	$this->assertEquals(1, $sts[0]['ParentTypeID']);
    	$this->assertEquals(3, $sts[1]['id']);
    	$this->assertEquals(1, $sts[1]['ParentTypeID']);
    	$this->assertEquals(5, $sts[0]['sub'][0]['id']);
    	$this->assertEquals(2, $sts[0]['sub'][0]['ParentTypeID']);
    	$this->assertEquals(4, $sts[1]['sub'][0]['id']);
    	$this->assertEquals(3, $sts[1]['sub'][0]['ParentTypeID']);
    }

    public function testTypesFor2 () {
    	$this->insert([[1, 't1', 't1 desc'],
    			[2, 't2', 't2 desc'],
    			[3, 't3', 't3 desc'],
    			[4, 't4', 't4 desc'],
    			[5, 't5', 't5 desc']
    	], "StatsType");
    	$this->insert([[2, 1],
    			[3, 1],
    			[4, 3],
    			[5, 2]
    	], "StatsTypeParent");

    	$db = $this->getConnection();
    	$st = new StatsType($db);

    	$sts = $st->fetchTypesFor(2);
    	$this->assertNotNull($sts);
    	$this->assertCount(1, $sts);
    	$this->assertEquals(5, $sts[0]['id']);
    	$this->assertEquals(2, $sts[0]['ParentTypeID']);
    }

    public function testAllTypes () {
    	$this->insert([[1, 't1', 't1 desc'],
    			[2, 't2', 't2 desc'],
    			[3, 't3', 't3 desc'],
    			[4, 't4', 't4 desc'],
    			[5, 't5', 't5 desc']
    	], "StatsType");
    	$this->insert([[2, 1],
    			[4, 3],
    			[5, 2]
    	], "StatsTypeParent");

    	$db = $this->getConnection();
    	$st = new StatsType($db);

    	$sts = $st->fetchAllTypes();
    	$this->assertNotNull($sts);
    	$this->assertCount(2, $sts);
    	$this->assertEquals(1, $sts[0]['id']);
    	$this->assertEquals(2, $sts[0]['sub'][0]['id']);
    	$this->assertEquals(5, $sts[0]['sub'][0]['sub'][0]['id']);
    	$this->assertEquals(3, $sts[1]['id']);
    	$this->assertEquals(4, $sts[1]['sub'][0]['id']);
    }
}