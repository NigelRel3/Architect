<?php
namespace Architect;

require_once __DIR__ . '/../vendor/autoload.php';

use Architect\controller\UploadController;
use Architect\controller\UserController;
use Architect\data\architect\AvailablePanelTypes;
use Architect\data\architect\Menu;
use Architect\data\architect\StatsLoad;
use Architect\data\architect\StatsType;
use Architect\data\architect\User;
use Architect\data\architect\Workspace;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Tuupola\Middleware\JwtAuthentication;
use PDO;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$containerBuilder = new ContainerBuilder();
// configure PHP-DI here
$containerBuilder->addDefinitions([
	'settings' => [
		'baseDir' => __DIR__,
		'addContentLengthHeader' => false,
		'determineRouteBeforeAppMiddleware' => true,
		'displayErrorDetails' => $_ENV['DISPLAY_ERRORS'],
		'logger' => [
				'name' => $_ENV['LOG_NAME'],
				'path' => $_ENV['LOG_PATH'],
				'level' => (int)$_ENV['LOG_LEVEL']
		],
		'database' => [
				'user' => $_ENV['DB_USER'],
				'password' => $_ENV['DB_PASSWD'],
				'host' => $_ENV['DB_HOST'],
				'database' => $_ENV['DB_DBNAME'],
		],
		'redis' => [
				'host' => $_ENV['CACHE_HOST'],
		],
		'jwt' => [
				"secret" => $_ENV['JWT_SECRET']
		],
		'webhost' => $_ENV["WEB_HOST"],
		'dockerhost' => $_ENV["DOCKER_HOST"],
		'uploadDirectory' => $_ENV["UPLOAD_DIRECTORY"],
		'tmpDir' => $_ENV["TMP_DIR"]
	],
]);

$containerBuilder->addDefinitions([
	UserController::class => function (ContainerInterface $c) {
		$uc = new UserController($c->get(User::class),
				$c->get(Workspace::class),
				$c->get(AvailablePanelTypes::class),
				$c->get(Menu::class),
				$c->get(StatsType::class),
				$c->get('settings')['jwt']['secret']);
		return $uc;
	}
]);

$containerBuilder->addDefinitions([
	UploadController::class => function (ContainerInterface $c) {
			$uc = new UploadController($c->get(User::class),
				$c->get(Workspace::class),
				$c->get(StatsLoad::class),
				$c,
				$c->get('settings')['baseDir'] . "/" .
					$c->get('settings')['uploadDirectory']);
		return $uc;
	}
]);

$containerBuilder->addDefinitions([
	LoggerInterface::class => function (ContainerInterface $c) {
		$settings = $c->get('settings')['logger'];

		$logger = new Logger($settings['name']);
		$handler = new StreamHandler($settings['path'], $settings['level']);
		$logger->pushHandler($handler);

		return $logger;
	}
]);

$containerBuilder->addDefinitions([
	PDO::class => function (ContainerInterface $c) {
		$settings = $c->get('settings')['database'];

		$db = new PDO("mysql:host={$settings['host']};dbname={$settings['database']}",
		$settings['user'], $settings['password']);
		$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $db;
	}
]);

$containerBuilder->addDefinitions([
	Client::class => function (ContainerInterface $c) {
		$settings = $c->get('settings');
		$host = str_replace("tcp://", "http://", $settings['dockerhost']);
		return new Client(['base_uri' => $host]);
	}
]);

// Include module specific config
$includeDir = __DIR__."/conf/config/";
foreach ( scandir($includeDir) as $include )	{
	if ( is_file($includeDir . $include) )	{
		require $includeDir.$include;
	}
}

AppFactory::setContainer($containerBuilder->build());

$app = AppFactory::create();

$container = $app->getContainer();

$app->addBodyParsingMiddleware();

$app->addErrorMiddleware(false, true, true, $container->get(LoggerInterface::class));

$app->add(
	new JwtAuthentication([
		"secure" => false,
		"relaxed" => ["172.17.0.1"],
		"algorithm" => ["HS256", "HS384"],
		"path" => "/",
		"ignore" => ["/home", "/login"],
		"secret" => $container->get('settings')['jwt']['secret'],
// 		"logger" => $container->get(LoggerInterface::class),
		"error" => function ($response, $arguments) {
			$data["status"] = "error";
			$data["message"] = $arguments["message"];
			return $response
				->withHeader("Content-Type", "application/json")
				->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		},
	])
);
