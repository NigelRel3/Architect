<?php

require_once __DIR__ . '/../ui/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DataControllerTest extends TestCase
{
	use AppTestTrait;
	use MySQLTestUtils;

	private \PDO $db;

	protected $setUp = [
			"SET FOREIGN_KEY_CHECKS=0",
			"delete from User",
			"delete from StatsLoad",
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
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$this->insert([[1, 'jmeter 1', 1,
				'/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv',
				'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'JMeterImport']
		], "StatsLoad");
		// Get list of items to stow
		$request = $this->createRequest('GET', 'http://172.17.0.1/listLoads/[1]');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$body = $this->getJsonArrayResponse($response);
		$this->assertCount(1, $body);
		$expected = [ "id" => '1', 'Name' => 'jmeter 1',
				'OwnerID' => '1', 'ImportType' => 'JMeterImport',
				'DataSource' => '/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv'
		];
		$this->assertEquals($expected, array_intersect_key($expected, $body[1]));
	}

	/**
	 * @depends testLogin
	 */
	public function testGetListMulti(string $token): void	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$this->insert([[1, 'jmeter 1', 1,
				'/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv',
				'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'JMeterImport'],
				[2, 'jmeter 2', 1,
						'/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv',
						'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'JMeterImport'],
				[3, 'jmeter 3', 1,
						'/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv',
						'2020-12-22 14:40:09', '2020-12-22 14:40:11', 'JMeterImport']
		], "StatsLoad");
		// Get list of items to stow
		$request = $this->createRequest('GET', 'http://172.17.0.1/listLoads/[1, 3]');
		$request = $request->withHeader("Authorization", "Bearer ".$token);
		$response = $this->app->handle($request);
		$this->assertSame(200, $response->getStatusCode());
		$body = $this->getJsonArrayResponse($response);
		$this->assertCount(2, $body);
		$expected = [ "id" => '1', 'Name' => 'jmeter 1',
				'OwnerID' => '1', 'ImportType' => 'JMeterImport',
				'DataSource' => '/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv'
		];
		$this->assertEquals($expected, array_intersect_key($expected, $body[1]));

		$expected = [ "id" => '3', 'Name' => 'jmeter 3',
				'OwnerID' => '1', 'ImportType' => 'JMeterImport',
				'DataSource' => '/var/www/html/src/../UserData/1_1_7353562075ffb4b99bf0be1.77508803.csv'
		];
		$this->assertEquals($expected, array_intersect_key($expected, $body[3]));
	}
}