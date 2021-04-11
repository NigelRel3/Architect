<?php
namespace Architect\conf\config;

use Architect\controller\DockerController;
use Architect\data\architect\DataPoint;
use Architect\data\architect\StatsLoad;
use Architect\util\DockerManager;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

$containerBuilder->addDefinitions([
	DockerController::class => function (ContainerInterface $c) {
		$uc = new DockerController($c->get(DockerManager::class),
				$c->get(Client::class),
				$c->get(DataPoint::class),
				$c->get(StatsLoad::class),
				$c->get('settings')['baseDir'] . "/" . $c->get('settings')['tmpDir']);
		return $uc;
	}
]);

$containerBuilder->addDefinitions([
	DockerManager::class => function (ContainerInterface $c) {
		$settings = $c->get('settings');

		return new DockerManager($settings['dockerhost'],
				$c->get(LoggerInterface::class));
	}
]);