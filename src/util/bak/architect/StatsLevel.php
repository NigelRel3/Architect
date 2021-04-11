<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLevel extends MySQL	{
	protected static $table = "StatsLevel";
	protected static $fields = ["id", "StatsTypeID", "Lower", "Higher", "Indicator"];
	protected static $dateFields = [];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
}
