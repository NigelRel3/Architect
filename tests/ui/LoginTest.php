<?php

require_once __DIR__ . '/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class LoginTest extends TestCase
{
	use AppTestTrait;
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
	
	public function testLogin(): void
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
		]);
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
		
		$headers = $response->getHeaders();
		$this->assertArrayHasKey('Set-Cookie', $headers);
		$this->assertEquals("JWT=", substr($headers['Set-Cookie'][0], 0, 4));
	}
	
	public function testInvalidUserLogin(): void
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
		]);
		/**
		 * @var Request $request
		 */
		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login',
				['username' => 'admin1', 'password' => 'ab34#lka4]']);
		
		/**
		 * @var Response $response
		 */
		$response = $this->app->handle($request);
		
		$this->assertSame(401, $response->getStatusCode());
	}
	
	public function testInvalidPasswordLogin(): void
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
		]);
		/**
		 * @var Request $request
		 */
		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login',
				['username' => 'admin', 'password' => 'ab34#lka4]1']);
		
		/**
		 * @var Response $response
		 */
		$response = $this->app->handle($request);
		
		$this->assertSame(401, $response->getStatusCode());
	}
	
	public function testNoDetailsLogin(): void
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004' ]
		]);
		/**
		 * @var Request $request
		 */
		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login');
		
		/**
		 * @var Response $response
		 */
		$response = $this->app->handle($request);
		
		$this->assertSame(401, $response->getStatusCode());
	}
}