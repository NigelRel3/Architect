<?php
namespace Architect;

use Architect\controller\DataController;
use Architect\controller\RootController;
use Architect\controller\UploadController;
use Architect\controller\UserController;

$app->post('/upload', UploadController::class.":upload");
$app->get('/listLoads/{list}', DataController::class.":listStatsLoads");
$app->get('/statsData/{id}/[{types}]', DataController::class.":getStatsData");


$app->post('/login', UserController::class.":login");
$app->get('/loginData', UserController::class.":loginData");
$app->post('/save', UserController::class.":save");

$app->get('/home', RootController::class.":load");

//$container->get(LoggerInterface::class)->info("Logging enabled");

// Include module specific routes
$includeDir = __DIR__."/conf/routes/";
foreach ( scandir($includeDir) as $include )	{
	if ( is_file($includeDir . $include) )	{
		require $includeDir.$include;
	}
}