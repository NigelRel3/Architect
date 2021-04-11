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
$res = $db->query("SHOW TABLES"); //  WHERE Tables_in_wh = 'TypeTest'
while ( $row = $res->fetch())	{
	$tableName = ucfirst($row[$tableColumnName]);
	$res2 = $db->query("SHOW COLUMNS FROM `{$tableName}`");
	$fields = [];
	$primaryKey = [];
	$autoInc = null;
	$formatToDB = '';
	$formatFromDB = '';

	echo $tableName,"=";
	while ( $row2 = $res2->fetch())	{
		$fields [] = $row2['Field'];
		echo $row2['Type']."/";
		if ( !is_null($row2['Default']) )	{
			// 			$defaultValues[$row2['Field']] = $row2['Default'];
			addDefaultValue( $row2['Field'], $formatToDB, $row2['Default']);
		}
		if ( $row2['Type'] === 'datetime' )	{
			addDateField( $row2['Field'], $formatFromDB, $formatToDB);
		}
		if ( $row2['Type'] === 'json' )	{
			addJsonField( $row2['Field'], $formatFromDB, $formatToDB);
		}
		if ( $row2['Type'] === 'tinyint(1)' )	{
			addBoolField( $row2['Field'], $formatFromDB, $formatToDB);
		}
		if ( substr($row2['Type'], 0, 3) === 'int' )	{
			addIntField( $row2['Field'], $formatFromDB, $formatToDB);
		}
		if ( substr($row2['Type'], 0, 7) === 'decimal' )	{
			addDecField( $row2['Field'], $formatFromDB, $formatToDB);
		}
		if ( $row2['Key'] === 'PRI' )	{
			$primaryKey[] = $row2['Field'];
		}
		if ( $row2['Extra'] == 'auto_increment')	{
			addAIField( $row2['Field'], $formatFromDB, $formatToDB);
			$autoInc = $row2['Field'];
		}
	}
	$fieldText = '["' . implode('", "', $fields) . '"]';
	$primaryKeyText = '["' . implode('", "', $primaryKey) . '"]';
	$autoIncText = !empty($autoInc) ? '"' . $autoInc . '"' : 'null';

	$fileName = $sourceDir . $tableName.".php";
	if ( file_exists($fileName) )	{
		$source = file_get_contents($fileName);
		$source = preg_replace('/(\$fields = )(.*?);/m',
				'$1'.$fieldText.';', $source);
		$source = preg_replace('/(\$pk = )(.*?);/m',
				'$1'.$primaryKeyText.';', $source);
		$source = preg_replace('/(\$autoIndexField = )(.*?);/m',
				'$1'.$autoIncText.';', $source);
	}
	else	{
		$source = file_get_contents($template);
		$replace = [ "{Table}" => $tableName,
			"{Fields}" => $fieldText,
			"{PrimaryKey}" => $primaryKeyText,
			"{AutoInc}" => $autoIncText,
		];
		$source = strtr($source, $replace);

	}
	$re = '#// FormatToDB\n(.+)// FormatToDBEnd#s';
	$source = preg_replace($re, '// FormatToDB' . PHP_EOL . $formatToDB
			. '		// FormatToDBEnd',
			$source, 1);
	$re = '#// FormatFromDB\n(.+)// FormatFromDBEnd#s';
	$source = preg_replace($re, '// FormatFromDB' . PHP_EOL . $formatFromDB
			. '		// FormatFromDBEnd'
			, $source, 1);

	file_put_contents($fileName, $source);

	echo PHP_EOL;
}

function addAIField ( $name, &$from, &$to )	{
	$to .= '		if ( empty($output["' . $name . '"]) )	{' . PHP_EOL;
	$to .= '			$output["' . $name . '"] = null;' . PHP_EOL;
	$to .= '		}' . PHP_EOL;
}

function addJsonField ( $name, &$from, &$to )	{
	$to .= '		if ( !is_null($output["' . $name . '"]) )	{' . PHP_EOL;
	$to .= '			$output["' . $name . '"] = json_encode($output["' . $name . '"]);' . PHP_EOL;
	$to .= '		}' . PHP_EOL;

	$from .= '		if ( !empty($row["' . $name . '"]) )	{' . PHP_EOL;
	$from .= '			$row["' . $name . '"] = json_decode($row["' . $name . '"], self::$jsonArrayMode);' . PHP_EOL;
	$from .= '		}' . PHP_EOL;
}

function addDateField ( $name, &$from, &$to )	{
	$to .= '		if ( !empty($output["' . $name . '"]) && $output["' . $name . '"] instanceof \DateTime )	{' . PHP_EOL;
	$to .= '			$output["' . $name . '"] = $output["' . $name . '"]->format("Y-m-d H:i:s");' . PHP_EOL;
	$to .= '		}' . PHP_EOL;

	$from .= '		if ( !empty($row["' . $name . '"]) )	{' . PHP_EOL;
	$from .= '			$row["' . $name . '"] = new \DateTime($row["' . $name . '"]);' . PHP_EOL;
	$from .= '		}' . PHP_EOL;
}

function addBoolField ( $name, &$from, &$to )	{
	$to .= '		if ( !is_null($output["' . $name . '"]) && is_bool($output["' . $name . '"]) )	{' . PHP_EOL;
	$to .= '			$output["' . $name . '"] = (int)$output["' . $name . '"];' . PHP_EOL;
	$to .= '		}' . PHP_EOL;

	$from .= '		if ( !is_null($row["' . $name . '"]) )	{' . PHP_EOL;
	$from .= '			$row["' . $name . '"] = (bool)$row["' . $name . '"];' . PHP_EOL;
	$from .= '		}' . PHP_EOL;
}

function addIntField ( $name, &$from, &$to )	{
	$from .= '		if ( !is_null($row["' . $name . '"]) )	{' . PHP_EOL;
	$from .= '			$row["' . $name . '"] = intval($row["' . $name . '"]);' . PHP_EOL;
	$from .= '		}' . PHP_EOL;
}

function AddDecField ( $name, &$from, &$to )	{
	$from .= '		if ( !is_null($row["' . $name . '"]) )	{' . PHP_EOL;
	$from .= '			$row["' . $name . '"] = floatval($row["' . $name . '"]);' . PHP_EOL;
	$from .= '		}' . PHP_EOL;
}

function AddDefaultValue ( $name, &$to, $value )	{
	$to .= '		if ( is_null($output["' . $name . '"]) )	{' . PHP_EOL;
	$to .= '			$output["' . $name . '"] = ';
	if ( $value == 'CURRENT_TIMESTAMP')	{
		$to .= 'new \DateTime();';
	}
	else	{
		$to .= '"' . $value . '";';
	}
	$to .= PHP_EOL;
	$to .= '			$this->data["' . $name . '"] = $output["' . $name . '"];';
	$to .= PHP_EOL . '		}' . PHP_EOL;
}

