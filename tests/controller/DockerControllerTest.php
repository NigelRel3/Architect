<?php

require_once __DIR__ . '/../ui/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DockerControllerTest extends TestCase
{
	use AppTestTrait;
	use MySQLTestUtils;

	protected $setUp = [
		"SET FOREIGN_KEY_CHECKS=0",
		"delete from User",
		"delete from StatsLoad",
		"delete from DataPoint",
		"delete from StatsTypeParent",
		"delete from StatsType",
		"SET FOREIGN_KEY_CHECKS=1",
	];
	protected $insert = ["User" => "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)
				on duplicate key update DefaultWorkspaceID = ?",
		"StatsLoad" => "insert into StatsLoad
					(id, Name, OwnerID, DataSource, DataStartPoint, DataEndPoint, ImportType)
				values ( ?, ?, ?, ?, ?, ?, ?)"
	];

	protected $containers = ['869bbacc57327f65cd41d8bc225a234fc8afbbf2a496151d2bdf563d6deb4211',
		'06adeb6c351fea6cc84292f9d2c5fb507b00a14202e495564195f475d5cd0937',
		'df39960432b3ae37576e0119dda7b47c610b9e882e60e00c8935015499237b62',
		'4a8287f8a5b93f15213edac77230b35007dcbaa915bfe029ab6543ee01649a48',
		'78015d4a5e31d05ab0e2a3f9a6205724835feca3d15dce717f778f48edaa3d68',
		'326c7d6393b6374dfcb402e1b50825594d5fde9e0ba24b46d7a6bc8b60ab0c58',
		'68cd03f31ed4f15e95f153113f64087f6bcd4a240f4ec35f29ff912a6ea2081b',
	];

	public function testLogin()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
			]
		], "User");
		/**
		 * @var Request $request
		 */
		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login',
				['username' => 'admin', 'password' => 'ab34#lka4]']);

		/**
		 * @var Response $response
		 */
		$response = $this->app->handle($request);

		$this->assertSame(200, $response->getStatusCode());

		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$jwt = substr($parts[0], 4);
		$this->assertNotNull($jwt);
		return $jwt;
	}

	/**
	 * @depends testLogin
	 */
	public function testGetList(string $token): void	{
		// Get list of items to stow
		$request = $this->createRequest('GET', 'http://172.17.0.1/docker/');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$body = $this->getJsonArrayResponse($response);
		$this->assertCount(7, $body);

		$this->assertEquals($this->containers, array_keys($body));
	}

	/**
	 * @depends testLogin
	 */
	public function testGetStats(string $token): void	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$this->insert([[1, 'Docker', 1,	'',
			'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'DockerImport']
		], "StatsLoad");
		$this->loadTypes();

		$request = $this->createRequest('GET',
				'http://172.17.0.1/docker/stats/1/3/["ArchPHP"]');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$count = $this->select ("select count(id) as tot
							from DataPoint")[0];
		$this->assertGreaterThan(0, $count['tot']);
		$this->assertEquals(0, $count['tot'] % 11);

		$body = $this->getJsonArrayResponse($response);
		$this->assertEquals(['Completed', 'ids'], array_keys($body));
		$this->assertEquals(['ArchPHP'], array_keys($body['ids']));
		$this->assertEquals(11,  count($body['ids']['ArchPHP']));
	}

	/**
	 * @depends testLogin
	 */
	public function testGetMultiStats(string $token): void	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$this->insert([[1, 'Docker', 1,	'',
			'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'DockerImport']
		], "StatsLoad");
		$this->loadTypes();

		$request = $this->createRequest('GET',
				'http://172.17.0.1/docker/stats/1/5/["ArchPHP", "ArchMySQL", "Archphpmyadmin"]');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$start = microtime(true);
		$response = $this->app->handle($request);
		$lapsed = microtime(true) - $start;

		$this->assertSame(200, $response->getStatusCode());
		$count = $this->select ("select count(id) as tot
							from DataPoint")[0];
		$this->assertGreaterThan(0, $count['tot']);
		$this->assertEquals(0, $count['tot'] % 11);

		$body = $this->getJsonArrayResponse($response);
		$this->assertEquals(['Completed', 'ids'], array_keys($body));
		$this->assertEqualsCanonicalizing(["ArchPHP", 'ArchMySQL', "Archphpmyadmin"], array_keys($body['ids']));
		$this->assertEquals(11,  count($body['ids']['ArchMySQL']));
		$this->assertEquals(11,  count($body['ids']['ArchPHP']));
		$this->assertEquals(11,  count($body['ids']['Archphpmyadmin']));
	}

	/**
	 * @depends testLogin
	 */
	public function testGetStatsRepeat(string $token): void	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$this->insert([[1, 'Docker', 1,	'',
			'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'DockerImport']
		], "StatsLoad");
		$this->loadTypes();

		$request = $this->createRequest('GET',
				'http://172.17.0.1/docker/stats/1/3/["ArchMySQL"]');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$count = $this->select ("select count(id) as tot
							from DataPoint")[0];
		$this->assertGreaterThan(0, $count['tot']);
		$this->assertEquals(0, $count['tot'] % 11);

		$body = $this->getJsonArrayResponse($response);
		$this->assertEquals(['Completed', 'ids'], array_keys($body));
		$this->assertEquals(['ArchMySQL'], array_keys($body['ids']));
		$this->assertEquals(11,  count($body['ids']['ArchMySQL']));

		$request = $this->createRequest('GET',
				'http://172.17.0.1/docker/stats/1/3/["ArchMySQL"]/'
				.json_encode($body['ids']));
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$count2 = $this->select ("select count(id) as tot
							from DataPoint")[0];
		$this->assertGreaterThan(0, $count2['tot']);
		$this->assertGreaterThan($count['tot'], $count2['tot']);
		$this->assertEquals(0, $count2['tot'] % 11);

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