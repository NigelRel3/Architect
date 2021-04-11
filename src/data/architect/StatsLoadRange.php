<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLoadRange extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatsLoadRange";
	protected static $fields = ["StatsLoadID", "StatsTypeID", "Min", "Max"];
	protected static $pk = ["StatsLoadID", "StatsTypeID"];
	protected static $autoIndexField = null;

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		// FormatToDBEnd
        return $output;
    }
    
    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["StatsLoadID"]) )	{
			$row["StatsLoadID"] = intval($row["StatsLoadID"]);
		}
		if ( !is_null($row["StatsTypeID"]) )	{
			$row["StatsTypeID"] = intval($row["StatsTypeID"]);
		}
		// FormatFromDBEnd
    }
}
