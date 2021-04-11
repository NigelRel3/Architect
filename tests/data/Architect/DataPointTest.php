<?php

use PHPUnit\Framework\TestCase;
use Architect\data\architect\DataPoint;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__. "/../MySQLTestUtils.php";

class DataPointTest extends TestCase   {
	use MySQLTestUtils;

    private $db;

    protected $setUp = [
    		"SET FOREIGN_KEY_CHECKS=0",
    		"delete from DataPoint",
    		"delete from StatsType",
    		"delete from StatsLoad",
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
    ];

    public function testForLoadID () {
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
    			[2, 1, 2, '1608648009', 't2 desc'],
    			[3, 1, 3, '1608648009', 't3 desc'],
    			[4, 2, 1, '1608648009', 't1 desc 2'],
    			[5, 2, 2, '1608648009', 't2 desc 2'],
    			[6, 2, 3, '1608648009', 't3 desc 2']
    	], "DataPoint");

    	$db = $this->getConnection();
    	$dp = new DataPoint($db);

    	$dps = $dp->fetchForLoadID(2);
    	$this->assertNotNull($dps);
    	$this->assertCount(3, $dps);
    	$this->assertEquals(5, $dps[1]->id);
    	$this->assertEquals('t1 desc 2', $dps[0]->Value);
    	$this->assertEquals('t2 desc 2', $dps[1]->Value);
    	$this->assertEquals('t3 desc 2', $dps[2]->Value);
    }

    public function testForLoadID1Type () {
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
    			[2, 1, 2, '1608648009', 't2 desc'],
    			[3, 1, 3, '1608648009', 't3 desc'],
    			[4, 2, 1, '1608648009', 't1 desc 2'],
    			[5, 2, 2, '1608648009', 't2 desc 2'],
    			[6, 2, 3, '1608648009', 't3 desc 2'],
    			[7, 2, 3, '1608648010', 't4 desc 2']
    	], "DataPoint");

    	$db = $this->getConnection();
    	$dp = new DataPoint($db);

    	$dps = $dp->fetchForLoadIDTypes(2, [3]);
    	$this->assertNotNull($dps);
    	$this->assertCount(2, $dps);
    	$this->assertEquals(6, $dps[0]->id);
    	$this->assertEquals(7, $dps[1]->id);
    	$this->assertEquals('t3 desc 2', $dps[0]->Value);
    	$this->assertEquals('t4 desc 2', $dps[1]->Value);
    }

    public function testForLoadIDMultiType () {
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
    			[2, 1, 2, '1608648009', 't2 desc'],
    			[3, 1, 3, '1608648009', 't3 desc'],
    			[4, 2, 1, '1608648009', 't1 desc 2'],
    			[5, 2, 2, '1608648009', 't2 desc 2'],
    			[6, 2, 3, '1608648009', 't3 desc 2'],
    			[7, 2, 3, '1608648010', 't4 desc 2']
    	], "DataPoint");

    	$db = $this->getConnection();
    	$dp = new DataPoint($db);

    	$dps = $dp->fetchForLoadIDTypes(2, [1,3]);
    	$this->assertNotNull($dps);
    	$this->assertCount(3, $dps);
    	$this->assertEquals(4, $dps[0]->id);
    	$this->assertEquals(6, $dps[1]->id);
    	$this->assertEquals(7, $dps[2]->id);
    	$this->assertEquals('t1 desc 2', $dps[0]->Value);
    	$this->assertEquals('t3 desc 2', $dps[1]->Value);
    	$this->assertEquals('t4 desc 2', $dps[2]->Value);
    }

}