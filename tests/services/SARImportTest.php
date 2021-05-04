<?php

require_once __DIR__ . '/../ui/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use Architect\data\architect\DataPoint;
use Architect\data\architect\StatsLoad;
use Architect\data\architect\StatsType;
use Architect\services\SARImport;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class SARImportTest extends TestCase
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
			"delete from StatsTypeParent",
			"delete from StatsType",
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

	public function testLoad()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
			]
		], "User");
		$this->insert([[1, 'testload', 1,
				'/controller/data/sa26'
				, 'SARImport', null
		]
		], "StatsLoad");

		$this->loadTypes();
		$base = getenv("BASEDIR");
		if ( file_exists($base."/controller/data/sa26.csv")){
			unlink($base."/controller/data/sa26.csv");
		}
		$db = $this->getConnection();
		$sl = new StatsLoad($db);

		$sl->fetch([1]);

		$load = new SARImport(new StatsType($db), new DataPoint($db), new Logger('test'));

		$res = $load($sl);
		$this->assertEquals(['Type' => 'Importing CSV'], $res);

		$this->assertEquals([[ "StatsTypeID" =>42, "Value" => 48.01],
			[ "StatsTypeID" =>43, "Value" => 21.43],
			[ "StatsTypeID" =>44, "Value" => 5825.45],
			[ "StatsTypeID" =>46, "Value" => 20285240],
			[ "StatsTypeID" =>47, "Value" => 210746916.00],
			[ "StatsTypeID" =>48, "Value" => 201305508.00],
			[ "StatsTypeID" =>50, "Value" => 0],
			[ "StatsTypeID" =>51, "Value" => 8808.00],
			[ "StatsTypeID" =>53, "Value" => 264.27],
			[ "StatsTypeID" =>54, "Value" => 45.73],
			[ "StatsTypeID" =>55, "Value" => 3.74]

		],
				$this->select ("SELECT StatsTypeID, ROUND(sum(Value),2) as Value
									FROM `DataPoint`
									GROUP BY StatsTypeID"
						));
	}

	protected function loadTypes()	{
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
				(16, 'PerformanceID', 'PerformanceID'),
				(21, 'dockerimport', 'Fields common in Docker'),
				(22, 'lapsed', 'lapsed'),
				(23, 'cputotal', 'cputotal'),
				(24, 'cpukernel', 'cpukernel'),
				(25, 'cpuuser', 'cpuuser'),
				(26, 'memoryusage', 'memoryusage'),
				(27, 'memorymax', 'memorymax'),
				(28, 'blkioRead', 'blkioRead'),
				(29, 'blkioWrite', 'blkioWrite'),
				(30, 'blkioTotal', 'blkioTotal'),
				(31, 'networkRx_bytes', 'networkRx_bytes'),
				(32, 'networkTx_bytes', 'networkTx_bytes'),
				(40, 'SARimport', 'Fields in SAR import'),
				(41, 'CPU', 'CPU'),
				(42, 'CPU%user', 'CPU%user'),
				(43, 'CPU%system', 'CPU%system'),
				(44, 'CPU%idle', 'CPU%idle'),
				(45, 'memory', 'memory'),
				(46, 'kbmemfree', 'kbmemfree'),
				(47, 'kbavail', 'kbavail'),
				(48, 'kbmemused', 'kbmemused'),
				(49, 'blkio', 'blkio'),
				(50, 'bread/s', 'bread/s'),
				(51, 'bwrtn/s', 'bwrtn/s'),
				(52, 'network', 'network'),
				(53, 'rxkB/s', 'rxkB/s'),
				(54, 'txkB/s', 'txkB/s'),
				(55, 'CPU%iowait', 'CPU%iowait');");

		$db->query("INSERT INTO `StatsTypeParent` (`StatsTypeID`, `ParentTypeID`) VALUES
				(2, 1),
				(3, 1),
				(4, 1),
				(5, 1),
				(6, 1),
				(7, 1),
				(8, 1),
				(9, 1),
				(10, 1),
				(11, 1),
				(12, 1),
				(13, 1),
				(14, 1),
				(15, 1),
				(16, 1),
				(22, 21),
				(23, 21),
				(24, 21),
				(25, 21),
				(26, 21),
				(27, 21),
				(28, 21),
				(29, 21),
				(30, 21),
				(31, 21),
				(32, 21),
				(41, 40),
				(45, 40),
				(49, 40),
				(52, 40),
				(42, 41),
				(43, 41),
				(44, 41),
				(46, 45),
				(47, 45),
				(48, 45),
				(50, 49),
				(51, 49),
				(53, 52),
				(54, 52),
				(55, 41)");

	}
}