<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class DefaultStatTypeDisplay extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "DefaultStatTypeDisplay";
	protected static $fields = ["id", "Name", "StatsType_id", "Config"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( !empty($output["Config"]) )	{
			$output["Config"] = json_encode($output["Config"]);
		}
		// FormatToDBEnd
        return $output;
    }
    
    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		if ( !is_null($row["StatsType_id"]) )	{
			$row["StatsType_id"] = intval($row["StatsType_id"]);
		}
		if ( !empty($row["Config"]) )	{
			$row["Config"] = json_decode($row["Config"], self::$jsonArrayMode);
		}
		// FormatFromDBEnd
    }
}
