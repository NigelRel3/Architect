<?php

require_once __DIR__ . '/../ui/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Architect\services\JMeterImport;
use Architect\data\architect\StatsLoad;
use Architect\data\architect\StatsType;
use Architect\data\architect\DataPoint;

class JMeterImportTest extends TestCase
{
	use AppTestTrait;
	use MySQLTestUtils;

	private \PDO $db;

	protected $setUp = [
			"SET FOREIGN_KEY_CHECKS=0",
			"delete from User",
			"delete from StatsLoad",
			"delete from StatsTypeParent",
			"delete from DataPoint",
			"delete from StatsType",
			"delete from StatsLoadRange",
			"delete from StatSummary",
			"SET FOREIGN_KEY_CHECKS=1",
	];
	protected $insert = ["StatsLoad" => "insert into StatsLoad
					(id, Name, OwnerID, DataSource, ImportType, config)
				values ( ?, ?, ?, ?, ?, ?)",
			"User" => "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)
				on duplicate key update DefaultWorkspaceID = ?"
	];

	public function testLoad1CSV()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
			]
		], "User");
		$this->insert([[1, 'testload', 1,
				'/controller/data/JMeterMinTestFile.csv'
// 				'/home/nigel/eclipse-workspace/Architect/tests/controller/data/JMeterMinTestFile.csv'
				, 'JMeterImport', null
		]
		], "StatsLoad");

		$db = $this->getConnection();
		$db->query("INSERT INTO `StatsType` (`id`, `Name`, `Description`) VALUES
			(1, 'jmeterimport', 'Fields common in JMeter performance files'),
			(2, 'timeStamp', 'timeStamp'),
			(3, 'elapsed', 'elapsed'),
			(4, 'label', 'label'),
			(5, 'responseCode', 'responseCode'),
			(6, 'responseMessage', 'responseMessage'),
			(7, 'threadName', 'threadName'),
			(8, 'dataType', 'dataType'),
			(9, 'success', 'success'),
			(10, 'bytes', 'bytes'),
			(11, 'grpThreads', 'grpThreads'),
			(12, 'allThreads', 'allThreads'),
			(13, 'URL', 'URL'),
			(14, 'Latency', 'Latency'),
			(15, 'Connect', 'Connect'),
			(16, 'PerformanceID', 'PerformanceID');"
		);
		$db->query("INSERT INTO `StatsTypeParent` (`StatsTypeID`, `ParentTypeID`) VALUES
				(2, 1),(3, 1),(4, 1),(5, 1),(6, 1),(7, 1),(8, 1),(9, 1),(10, 1),
				(11, 1),(12, 1),(13, 1),(14, 1),(15, 1),(16, 1);"
		);
		$sl = new StatsLoad($db);

		$sl->fetch([1]);

		$load = new JMeterImport(new StatsType($db), new DataPoint($db));

		$res = $load($sl);
		$this->assertEquals(['Type' => 'Importing CSV'], $res);

		$this->assertEquals([[ "StatsTypeID" =>3, "Value" => 14, 'SubSet' => null],
			[ "StatsTypeID" =>5, "Value" => 200, 'SubSet' => null],
			[ "StatsTypeID" =>6, "Value" => 'OK', 'SubSet' => null],
			[ "StatsTypeID" =>10, "Value" => 283, 'SubSet' => null],
			[ "StatsTypeID" =>14, "Value" => 12, 'SubSet' => null],
			[ "StatsTypeID" =>15, "Value" => 7, 'SubSet' => null],
			[ "StatsTypeID" =>16, "Value" => null, 'SubSet' => null],
			[ "StatsTypeID" =>3, "Value" => 41, 'SubSet' => null],
			[ "StatsTypeID" =>5, "Value" => 200, 'SubSet' => null],
			[ "StatsTypeID" =>6, "Value" => 'OK', 'SubSet' => null],
			[ "StatsTypeID" =>10, "Value" => 302, 'SubSet' => null],
			[ "StatsTypeID" =>14, "Value" => 40, 'SubSet' => null],
			[ "StatsTypeID" =>15, "Value" => 0, 'SubSet' => null],
			[ "StatsTypeID" =>16, "Value" => 229913953, 'SubSet' => null]

		],
				$this->select ("select StatsTypeID, Value, SubSet
								from `DataPoint`
								where StatsLoadID = 1
								order by id"
						));
	}
}