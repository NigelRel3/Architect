<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class DefaultStatTypeDisplay extends MySQL	{
	protected static $table = "DefaultStatTypeDisplay";
	protected static $fields = ["id", "Name", "StatsType_id", "Config"];
	protected static $dateFields = [];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
}
