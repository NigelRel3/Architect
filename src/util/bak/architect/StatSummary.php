<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatSummary extends MySQL	{
	protected static $table = "StatSummary";
	protected static $fields = ["id", "SourceID", "StatsTypeID", "TimeStamp", "TimeRange", "Value"];
	protected static $dateFields = ["TimeStamp"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
}
