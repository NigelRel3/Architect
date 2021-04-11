<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLoadRange extends MySQL	{
	protected static $table = "StatsLoadRange";
	protected static $fields = ["StatsLoadID", "StatsTypeID", "Min", "Max"];
	protected static $dateFields = [];
	protected static $pk = ["StatsLoadID", "StatsTypeID"];
	protected static $autoIndexField = null;
}
