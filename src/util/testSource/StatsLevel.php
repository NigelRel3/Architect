<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLevel extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatsLevel";
	protected static $fields = ["id", "StatsTypeID", "Lower", "Higher", "Indicator"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( empty($output["id"]) )	{
			$output["id"] = null;
		}
		// FormatToDBEnd
        return $output;
    }
    
    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		if ( !is_null($row["StatsTypeID"]) )	{
			$row["StatsTypeID"] = intval($row["StatsTypeID"]);
		}
		// FormatFromDBEnd
    }
}
