<?php
namespace Architect\conf\routes;

use Architect\controller\DockerController;
use Slim\Routing\RouteCollectorProxy;

$app->group( '/docker', function (RouteCollectorProxy $group) {
	$group->get('/', DockerController::class.":listContainers");
	$group->post('/stats/complete/{id}', DockerController::class.":saveStatsComplete");
	$group->get('/stats/{id}/{interval}/{list}[/{lastids}]', DockerController::class.":saveStats");
});
