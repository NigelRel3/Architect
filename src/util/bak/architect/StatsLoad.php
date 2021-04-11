<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLoad extends MySQL	{
	protected static $table = "StatsLoad";
	protected static $fields = ["id", "Name", "CreatedOn", "Notes", 
			"OwnerID", "DataSource", "DataStartPoint", "DataEndPoint", 
			"config", "ImportType"];
	protected static $dateFields = ["CreatedOn", "DataStartPoint", "DataEndPoint"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
}
