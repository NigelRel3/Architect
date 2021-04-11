<?php
namespace Architect\util;

putenv("BASEDIR=../src");
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/config.php';

$dc = $container->get(DockerManager::class);

//echo $dc->startContainer("portainer");
// echo $dc->startContainer("whPHP");
echo $dc->startContainer("ArchPHP");
echo $dc->startContainer("ArchMySQL");
//echo $dc->startContainer("ArchPHP");
echo $dc->startContainer("Archphpmyadmin");

// $stats = $dc->statsContainer("ArchMySQL");
// print_r( $stats );

/*
 * Stats
$sttiveats = $container->statsContainerNamed("ArchMySQL");

print_r( $stats );
echo $container->startContainerNamed("phpmyadmin");
*/
/*
 * Restart haproxy to reload config.

$client = new DockerClient([
        'remote_socket' => 'tcp://127.0.0.1:2375',
        'ssl' => false,
]);
$docker = new Docker($client);
$containerManager = $docker->getContainerManager();
$response = $containerManager->kill("haproxy", ["signal" => "HUP"]);
echo $response->getBody();
*/