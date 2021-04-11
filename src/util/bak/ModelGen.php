<?php
declare(strict_types=1);

namespace Architect\util;

error_reporting(E_ALL);
ini_set('display_errors', "1");

use Dotenv\Dotenv;
use \PDO;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$dbName = $_ENV["DB_DBNAME"];
$db = new PDO("mysql:host=".$_ENV["DB_HOST"].";dbname=".$dbName,
		$_ENV["DB_USER"], $_ENV["DB_PASSWD"]);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$sourceDir = __DIR__ . "/testSource/";
$template = __DIR__ . "/Template.tmp";
$tableColumnName = "Tables_in_{$dbName}";
$res = $db->query("SHOW TABLES");
while ( $row = $res->fetch())	{
	$tableName = ucfirst($row[$tableColumnName]);
	$res2 = $db->query("SHOW COLUMNS FROM `{$tableName}`");
	$fields = [];
	$dateFields = [];
	$jsonFields = [];
	$primaryKey = [];
	$autoInc = null;
	while ( $row2 = $res2->fetch())	{
		$fields [] = $row2['Field'];
		if ( $row2['Type'] === 'datetime' )	{
			$dateFields[] = $row2['Field'];
		}
		if ( $row2['Type'] === 'json' )	{
			$jsonFields[] = $row2['Field'];
		}
		if ( $row2['Key'] === 'PRI' )	{
			$primaryKey[] = $row2['Field'];
		}
		if ( $row2['Extra'] === 'auto_increment' )	{
			$autoInc = $row2['Field'];
		}
	}
	$fieldText = '["' . implode('", "', $fields) . '"]';
	if ( count($dateFields)  == 0 )	{
		$dateFieldText = '[]';
	}
	else	{
		$dateFieldText = '["' . implode('", "', $dateFields) . '"]';
	}
	if ( count($jsonFields)  == 0 )	{
		$jsonFieldText = '[]';
	}
	else	{
		$jsonFieldText = '["' . implode('", "', $jsonFields) . '"]';
	}
	$primaryKeyText = '["' . implode('", "', $primaryKey) . '"]';
	$autoIncText = !empty($autoInc) ? '"' . $autoInc . '"' : 'null';
	
	$fileName = $sourceDir . $tableName.".php";
	if ( file_exists($fileName) )	{
		$source = file_get_contents($fileName);
		$source = preg_replace('/(\$fields = )(.*?);/m', 
				'$1'.$fieldText.';', $source);
		$source = preg_replace('/(\$dateFields = )(.*?);/m',
				'$1'.$dateFieldText.';', $source);
		$source = preg_replace('/(\$jsonFields = )(.*?);/m',
				'$1'.$jsonFieldText.';', $source);
		$source = preg_replace('/(\$pk = )(.*?);/m',
				'$1'.$primaryKeyText.';', $source);
		$source = preg_replace('/(\$autoIndexField = )(.*?);/m',
				'$1'.$autoIncText.';', $source);
	}
	else	{
		$source = file_get_contents($template);
		$replace = [ "{Table}" => $tableName,
				"{Fields}" => $fieldText,
				"{DateFields}" => $dateFieldText,
				"{PrimaryKey}" => $primaryKeyText,
				"{AutoInc}" => $autoIncText,
				"{jsonFields}" => $jsonFieldText
		];
		$source = strtr($source, $replace);
	}
	
	file_put_contents($fileName, $source);
}
