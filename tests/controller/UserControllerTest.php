<?php

require_once __DIR__ . '/../ui/AppTestTrait.php';
require_once __DIR__ . '/../data/MySQLTestUtils.php';

use Architect\data\architect\Workspace;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class UserControllerTest extends TestCase
{
	use AppTestTrait;
	use MySQLTestUtils;

	private \PDO $db;

	protected $setUp = [
			"SET FOREIGN_KEY_CHECKS=0",
			"delete from Menu",
			"delete from User",
			"delete from Workspace",
			"delete from AvailablePanelTypes",
			"SET FOREIGN_KEY_CHECKS=1",
	];
	protected $insert = ["User" => "insert into User
					(id, Name, EMail, Password, Access, DefaultWorkspaceID, uuid)
				values ( ?, ?, ?, ?, ?, ?, ?)
				on duplicate key update DefaultWorkspaceID = ?",
			"Workspace" => "insert into Workspace
					(id, Name, UserID, Config, Windows)
				values ( ?, ?, ?, ?, ?)",
			"MenuConf" => "insert into Menu
					(id, `Key`, title, WorkspaceID, folder, parentMenuID,
						componentID, config, nextKey )
				values ( ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			"AvailablePanelTypes" => "insert into AvailablePanelTypes
					(id, Name, ComponentName, Config, ParentMenuKey)
				values ( ?, ?, '', ?, ?)"
	];

	public function testLoginNoWS()
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);

		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertArrayHasKey("DefaultWorkspace", $body);
		$this->assertArrayHasKey("Panels", $body);
	}

	public function testLoginWS()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$wsConfig = json_encode([ "name" => "default",
				"horizontal" => true,
				"sizeable" => true
		]);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
							"type"=> "pane",
							"size" => 20,
							"children" => [
									[
										"type" => "tab",
										"key" => 1
									]
							]
						]
				]]
		]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig], [2, 'ws1', 2, $wsConfig, $windowConfig],
				[3, 'ws1a', 1, $wsConfig, $windowConfig], [4, 'ws1b', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 1, '{"testid": 1}', 0],
				[2, 2, "t2", 1, 0, 2, 1, '{"testid": 2}', 0],
				[3, 1, "t1", 2, 3, null, 1, '{"testid": 1}', 0],
				[4, 1, "t1", 3, 4, null, 1, '{"testid": 1}', 0],
				[5, 1, "t1", 4, 5, null, 1, '{"testid": 1}', 0],
		], "MenuConf");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);

		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(3,$body['Workspaces']);
		$this->assertEquals('ws1', $body['Workspaces'][1]['Name']);
		$this->assertEquals('ws1a', $body['Workspaces'][3]['Name']);
		$this->assertEquals('ws1b', $body['Workspaces'][4]['Name']);
	}

	public function testLoginWSDefault()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$wsConfig = json_encode([ "name" => "default",
				"horizontal" => true,
				"sizeable" => true
		]);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 1
										]
								]
						]
				]]
		]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig], [2, 'ws1', 2, $wsConfig, $windowConfig],
				[3, 'ws1a', 1, $wsConfig, $windowConfig], [4, 'ws1b', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 1, '{"testid": 1}', 0],
				[2, 2, "t2", 1, 0, 2, 1, '{"testid": 2}', 0],
				[3, 1, "t1", 2, 3, null, 1, '{"testid": 1}', 0],
				[4, 1, "t1", 3, 4, null, 1, '{"testid": 1}', 0],
				[5, 1, "t1", 4, 5, null, 1, '{"testid": 1}', 0],
		], "MenuConf");
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', 3
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(3,$body['Workspaces']);
		$this->assertArrayHasKey("DefaultWorkspace", $body);
		$this->assertEquals(3, $body['DefaultWorkspace']);
	}

	public function testLoginPanels()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$this->insert([[1, 'ws1', null, 0], [2, 'ws1', null, 0],
				[3, 'ws1a', null, 0], [4, 'ws1b', null, 0]
		], "AvailablePanelTypes");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(0,$body['Workspaces']);
		$this->assertArrayHasKey("DefaultWorkspace", $body);
		$this->assertEquals(0, $body['DefaultWorkspace']);
		$this->assertArrayHasKey("Panels", $body);
		$this->assertCount(4, $body['Panels']);
		$this->assertEquals('ws1', $body['Panels'][1]['Name']);
		$this->assertEquals('ws1b', $body['Panels'][4]['Name']);

	}

	public function testLoginWSJSON()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$wsConfig = json_encode([ "name" => "default",
				"horizontal" => true,
				"sizeable" => true
		]);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 1,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}', 0]
		], "MenuConf");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body, print_r($body, true));
		$this->assertCount(1, $body['Workspaces']);
		$this->assertEquals ( 'ws1', $body ['Workspaces'] [1] ['Name'] );
		$this->assertIsArray ( $body ['Workspaces'] [1] ['Config'] );
		$config = [
			"name" => "default",
			"horizontal" => true,
			"sizeable" => true,
			'nextPaneID' => 2
		];
		$this->assertEquals($config, $body ['Workspaces'] [1] ['Config'], print_r ( $body ['Workspaces'] [1] ['Windows'], true ) );
		$windows = [[
						"type" => 'window',
						'children' => [
								[
										"type" => 'pane',
										"size" => 20,
										'children' => [
												[
														'ComponentID' => 1,
														'paneID' => 0,
														'title' => 'Main Menu',
														"key" => 0,
														"type" => 'tab',
												]
										],
										'openToThisPanel' => false
								],
								[
										"type" => 'pane',
										"children" => [
												[
														'ComponentData' => [
																'testid' => 1
														],
														'ComponentID' => 2,
														"key" => 1,
														'paneID' => 1,
														'title' => 't1',
														'openToThisPanel' => true,
														"type" => 'tab'
												]
										],
										'openToThisPanel' => true
								]
						],
						'openToThisPanel' => true
				]];
		$this->assertEquals($windows, $body ['Workspaces'] [1] ['Windows'],
				print_r ( $body ['Workspaces'] [1] ['Windows'], true ) );
	}

	public function testLoginMenuPanels()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 1,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, null, $windowConfig], [2, 'ws12', 2, null, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}', 6],
				[2, 2, "t2", 1, 0, 1, 3, '{"testid": 2}', 0],
				[3, 2, "t1", 2, 1, null, 4, '{"testid": 1}', 0],
				[4, 5, "tool", 1, 1, null, 5, '{"testid": 3}', 0]
		], "MenuConf");
		$this->insert([[1, 'ws1', null, 0],
				[2, 'ws1',
					'{"menu": {
						"title": "TestTool",
						"icon": "/ui/icons/gear-fill.svg"
						}
					}', 5],
				[3, 'ws1a', null, 0],
				[4, 'ws1b',
					'{"menu": {
						"title": "TestTool2",
						"icon": "/ui/icons/pencil.svg"
						}
					}', 5]
		], "AvailablePanelTypes");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(1,$body['Workspaces']);
		$this->assertEquals('ws1', $body['Workspaces'][1]['Name']);
		$this->assertIsArray($body['Workspaces'][1]['Config']);
		$key5Menu = $body['Workspaces'][1]["Menu"][1];
		$this->assertEquals("5", $key5Menu["key"]);
		$this->assertArrayHasKey("children", $key5Menu, print_r($key5Menu, true));
		$this->assertCount(2, $key5Menu["children"]);
		$this->assertEquals("6", $key5Menu["children"][0]['key']);
		$this->assertEquals("TestTool", $key5Menu["children"][0]['title']);
		$this->assertEquals("7", $key5Menu["children"][1]['key']);
		$this->assertEquals("TestTool2", $key5Menu["children"][1]['title']);
		$this->assertEquals("/ui/icons/pencil.svg", $key5Menu["children"][1]['icon']);
		$this->assertEquals("8", $body['Workspaces'][1]['Menu'][0]['data']['MenuData']['nextKey'],
				print_r($body['Workspaces'][1], true));
	}

	public function testLoginMenuPanelsSubMenus()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 1,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, null, $windowConfig], [2, 'ws12', 2, null, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}',8],
				[2, 2, "t2", 1, 1, null, 3, '{"testid": 2}', 0],
				[6, 3, "t23", 1, 0, 2, 2, '{"testid": 2}', 0],
				[7, 4, "t234", 1, 1, 2, 2, '{"testid": 2}', 0],
				[3, 2, "t1", 2, 1, null, 4, '{"testid": 1}', 0],
				[4, 5, "tool", 1, 1, null, 5, '{"testid": 3}', 0]
		], "MenuConf");
		$this->insert([[1, 'ws1', null, 0],
				[2, 'ws1',
						'{"menu": {
						"title": "TestTool",
						"icon": "/ui/icons/gear-fill.svg"
						}
					}', 4],
				[3, 'ws1a', null, 0],
				[4, 'ws1b',
						'{"menu": {
						"title": "TestTool2",
						"icon": "/ui/icons/pencil.svg"
						}
					}', 2]
		], "AvailablePanelTypes");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(1,$body['Workspaces']);
		$this->assertEquals('ws1', $body['Workspaces'][1]['Name']);
		$this->assertIsArray($body['Workspaces'][1]['Config']);
		$key2Menu = $body['Workspaces'][1]["Menu"][1];
		$this->assertEquals("2", $key2Menu["key"]);
		$this->assertArrayHasKey("children", $key2Menu);
		$this->assertCount(3, $key2Menu["children"]);
		$this->assertEquals("9", $key2Menu["children"][2]['key']);
		$this->assertEquals("TestTool2", $key2Menu["children"][2]['title']);

		$key4Menu = $body['Workspaces'][1]["Menu"][1]['children'][1];
		$this->assertEquals("4", $key4Menu["key"]);
		$this->assertCount(1, $key4Menu["children"]);
		$this->assertEquals("8", $key4Menu["children"][0]['key']);
		$this->assertEquals("TestTool", $key4Menu["children"][0]['title']);
		$this->assertEquals("/ui/icons/gear-fill.svg", $key4Menu["children"][0]['icon']);
		$this->assertEquals("10", $body['Workspaces'][1]["Menu"][0]['data']['MenuData']['nextKey']);
	}

	public function testLoginMenuPane2()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 1,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, null, $windowConfig], [2, 'ws12', 2, null, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}',8],
				[2, 2, "t2", 1, 1, null, 3, '{"testid": 2}', 0],
				[3, 2, "t1", 2, 1, null, 4, '{"testid": 1}', 0],
				[4, 5, "tool", 1, 1, null, 5, '{"testid": 3}', 0]
		], "MenuConf");

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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(1,$body['Workspaces']);
		$this->assertEquals('ws1', $body['Workspaces'][1]['Name']);
		$windows = $body['Workspaces'][1]['Windows'][0];
		$this->assertEquals('window',  $windows["type"]);
		$this->assertEquals('pane',  $windows['children'][1]["type"]);
		$this->assertArrayHasKey('ComponentData', $windows['children'][1]['children'][0]);
		$this->assertEquals(["testid" => 1],
				$windows['children'][1]['children'][0]['ComponentData']);
	}

	public function testLoginSubPanes()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$wsConfig = json_encode(
			[
				"name" => "default",
				"horizontal" => true,
				"sizeable" => true,

			]
		);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 2,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig], [2, 'ws12', 2, $wsConfig, $windowConfig],
				[3, 'ws1a', 1, $wsConfig, $windowConfig], [4, 'ws1b', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}', 0],
				[2, 2, "t2", 1, 0, 1, 13, '{"testid": 2}', 0],
				[3, 2, "t1", 2, 3, null, 4, '{"testid": 1}', 0],
				[4, 2, "t1", 3, 4, null, 4, '{"testid": 1}', 0],
				[5, 2, "t1", 4, 5, null, 4, '{"testid": 1}', 0],
		], "MenuConf");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(3,$body['Workspaces']);
		$this->assertEquals ( 'ws1', $body ['Workspaces'] [1] ['Name'] );
		$this->assertIsArray ( $body ['Workspaces'] [1] ['Config'] );
		$config = [
			"name" => "default",
			"horizontal" => true,
			"sizeable" => true,
			'nextPaneID' => 2
		];

		$windowConfig =[[
				"type"=> "window",
				'openToThisPanel' => true,
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												'ComponentID' => 1,
												'title' => 'Main Menu',
												'paneID' => 0,
												"key" => 0,
												"type" => "tab",
										]
								],
								'openToThisPanel' => false
						],
						[
								"type"=> "pane",
								"children" => [
										[
												'ComponentData' => [
														"testid" => 2
												],
												'ComponentID' => 13,
												"key" => 2,
												'paneID' => 1,
												'title' => 't2',
												"type" => "tab",
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]];
		$this->assertEquals($config, $body['Workspaces'][1]['Config'],
				print_r($body['Workspaces'][1]['Config'], true));
		$this->assertEquals($windowConfig, $body['Workspaces'][1]['Windows'],
				print_r($body['Workspaces'][1]['Windows'], true));
		$this->assertEquals('ws1a', $body['Workspaces'][3]['Name']);
		$this->assertEquals('ws1b', $body['Workspaces'][4]['Name']);
	}

	public function testLoginMultiWindows()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, null, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");
		$wsConfig = json_encode(
			[
				"name" => "default",
				"horizontal" => true,
				"sizeable" => true,

			]
		);
		$windowConfig = json_encode([
		    [
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 2,
												'openToThisPanel' => true
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		  ],
		  [
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 100,
								"children" => [
										[
												"type" => "tab",
												"key" => 1
										]
								]
						]
				    ]
		  ],
		]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
				'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
				255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	],
				[2, 'admin1', 'nigelrel3@yahoo.co.uk',
						'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
						255, 1, '55f910de-7405-11ea-ac31-0242ac110004', null	]
		], "User");

		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}', 0],
				[2, 2, "t2", 1, 0, 1, 13, '{"testid": 2}', 0]
		], "MenuConf");
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

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);
		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertCount(1,$body['Workspaces']);
		$this->assertEquals ( 'ws1', $body ['Workspaces'] [1] ['Name'] );
		$this->assertIsArray ( $body ['Workspaces'] [1] ['Config'] );
		$config = [
			"name" => "default",
			"horizontal" => true,
			"sizeable" => true,
			'nextPaneID' => 3
		];

		$windowConfig =[[
			"type"=> "window",
			'openToThisPanel' => true,
			"children" => [
				[
					"type"=> "pane",
					"size" => 20,
					"children" => [
						[
							'ComponentID' => 1,
							'title' => 'Main Menu',
							'paneID' => 0,
							"key" => 0,
							"type" => "tab",
						]
					],
					'openToThisPanel' => false
				],
				[
					"type"=> "pane",
					"children" => [
						[
							'ComponentData' => [
								"testid" => 2
							],
							'ComponentID' => 13,
								"key" => 2,
								'paneID' => 1,
								'title' => 't2',
								"type" => "tab",
								'openToThisPanel' => true
							]
						],
						'openToThisPanel' => true
					]
				],
				'openToThisPanel' => true
			],
		 	[
				"type"=> "window",
				"children" => [
					[
						"type"=> "pane",
						"size" => 100,
						"children" => [
							[
								'ComponentData' => [
									"testid" => 1
								],
								'ComponentID' => 2,
								"key" => 1,
								'paneID' => 2,
								'title' => 't1',
								"type" => "tab",
								'openToThisPanel' => false
							]
						],
						'openToThisPanel' => false
					]
			   ],
				'openToThisPanel' => false
		  	],
		];
		$this->assertEquals($config, $body['Workspaces'][1]['Config'],
				print_r($body['Workspaces'][1]['Config'], true));
		$this->assertEquals($windowConfig, $body['Workspaces'][1]['Windows'],
				print_r($body['Workspaces'][1]['Windows'], true));
	}

	public function testSaveWS()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$wsConfig = json_encode(
				[
					"name" => "default",
					"horizontal" => true,
					"sizeable" => true
				]
			);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						],
						[
								"type"=> "pane",
								"children" => [
										[
												"type" => "tab",
												"key" => 2
										],
										[
												"type" => "tab",
												"key" => 3
										]
								],
								'openToThisPanel' => true
						]
				],
				'openToThisPanel' => true
		]]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig]
		], "Workspace");
		$this->insert([[1, 1, "t1", 1, 1, null, 2, '{"testid": 1}', 0],
				[2, 2, "t2", 1, 0, 1, 13, '{"testid": 2}', 0],
				[3, 3, "t1", 1, 3, null, 4, '{"testid": 3}', 0]
		], "MenuConf");

		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login',
				['username' => 'admin', 'password' => 'ab34#lka4]']);

		$response = $this->app->handle($request);

		$this->assertSame(200, $response->getStatusCode());

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);

		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertArrayHasKey("DefaultWorkspace", $body);
		$this->assertArrayHasKey("Panels", $body);

		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/save',
				json_decode(file_get_contents(__DIR__ . "/data/SaveConfig.json"), true));
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$this->assertSame(200, $response->getStatusCode());

		$db = $this->getConnection();
		$ws = new Workspace($db);
		$this->assertTrue($ws->fetch([1]));
		$this->assertEquals("Defaulta", $ws->Name);
		$this->assertTrue($ws->Config['horizontal']);
		$this->assertEquals(['type' => 'tab', 'key' => 0], $ws->Windows[0]['children'][0]['children'][0]);
		$this->assertEquals(25, $ws->Windows[0]['children'][0]['size']);
		$this->assertEquals([
				['type' => 'tab', 'key' => 1],
				['type' => 'tab', 'key' => 3]
			], $ws->Windows[0]['children'][1]['children']);
		$this->assertEquals(75, $ws->Windows[0]['children'][1]['size']);

	}

	public function testSaveMenu()
	{
		$this->insert([[1, 'admin', 'nigelrel3@yahoo.co.uk',
			'$2y$10$sfB.BALY6QxU.oSyhXut1exsYdfWDL.gCKzJ4SKSfuzsKXBMFMnn2',
			255, null, '55f910de-7405-11ea-ac31-0242ac110004', null
		]
		], "User");
		$wsConfig = json_encode(
				[
					"name" => "default",
					"horizontal" => true,
					"sizeable" => true,
					'window' =>
					[[
						["size" => 20, "tabs" => [0]],
						[
							"panes" => 	[
								[
									"openToThisPanel" => true,
									"tabs" => [	2, 3 ]
								]
							]
						]
					]]
				]
		);
		$windowConfig = json_encode([[
				"type"=> "window",
				"children" => [
						[
								"type"=> "pane",
								"size" => 20,
								"children" => [
										[
												"type" => "tab",
												"key" => 0
										]
								]
						]
				]
		]]);
		$this->insert([[1, 'ws1', 1, $wsConfig, $windowConfig]
		], "Workspace");

		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/login',
				['username' => 'admin', 'password' => 'ab34#lka4]']);

		$response = $this->app->handle($request);

		$this->assertSame(200, $response->getStatusCode());

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Name", $body);

		$header = $response->getHeader('Set-Cookie');
		$this->assertNotNull($header);
		$parts = explode(";", $header[0]);
		$token = substr($parts[0], 4);
		$this->assertNotNull($token);
		$request = $this->createJsonRequest('GET', 'http://172.17.0.1/loginData');
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$body = $this->getJsonArrayResponse($response);
		$this->assertArrayHasKey("Workspaces", $body);
		$this->assertArrayHasKey("DefaultWorkspace", $body);
		$this->assertArrayHasKey("Panels", $body);

		$request = $this->createJsonRequest('POST', 'http://172.17.0.1/save',
				json_decode(file_get_contents(__DIR__ . "/data/SaveConfig.json"), true));
		$request = $request->withHeader("Authorization", "Bearer ".$token);

		$response = $this->app->handle($request);

		$this->assertSame(200, $response->getStatusCode());

		$db = $this->getConnection();
		$ws = new Workspace($db);
		$this->assertTrue($ws->fetch([1]));
		$this->assertEquals("Defaulta", $ws->Name);
		$this->assertTrue($ws->Config['horizontal']);
		$this->assertEquals(['type' => 'tab', 'key' => 0], $ws->Windows[0]['children'][0]['children'][0]);
		$this->assertEquals(25, $ws->Windows[0]['children'][0]['size']);
		$this->assertEquals([
				['type' => 'tab', 'key' => 1],
				['type' => 'tab', 'key' => 3]
			], $ws->Windows[0]['children'][1]['children']);
		$this->assertEquals(75, $ws->Windows[0]['children'][1]['size']);

		$this->assertEquals([[
				"title" => 'Defaulta', "WorkspaceID" => '1',
			"folder" => '1', "icon" => "/ui/icons/archive.svg",
			"editable" => '1', "addFolder" => '1', "addContents" => '1',
			"deleteable" => '0', "nextKey" => '11', "componentID" => '2',
			"parentMenuID" => null, "addIcon" => "book",
			"config" => '{"componentID": 2, "description": "Default workspace"}',
			"expanded" => '1',
			"addChildData" => '{"editable": true, "addFolder": true, "deleteable": true, "addContents": true, "componentID": 8}'
			]],
			$this->select('select title, WorkspaceID, folder, icon, editable,
						addFolder, addContents, deleteable, nextKey, componentID,
						parentMenuID, addIcon, config, expanded, addChildData
				from Menu
				where `key` = 1')
		);

		$this->assertEquals([[
			"title" => 'JMeter 9/Dec/2020 20:45', "WorkspaceID" => '1',
			"folder" => '0', "icon" => "/ui/icons/receipt-cutoff.svg",
			"editable" => '1', "addFolder" => '0', "addContents" => '0',
			"deleteable" => '1', "nextKey" => '0', "componentID" => '4',
			"addIcon" => null,
			"config" => '{"loadID": 2, "importType": 6}',
			"expanded" => '0',
			"addChildData" => null
		]],
				$this->select('select title, WorkspaceID, folder, icon, editable,
						addFolder, addContents, deleteable, nextKey, componentID,
						addIcon, config, expanded, addChildData
				from Menu
				where `key` = 4')
				);

		$this->assertEquals([[
			"parentKey" => '2'
		]],
				$this->select('select p.`key` as parentKey
				from Menu m
				join Menu p on p.id = m.parentMenuID
				where m.`key` = 4')
				);
	}
}