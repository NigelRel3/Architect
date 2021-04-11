<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatSummary extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatSummary";
	protected static $fields = ["id", "StatsLoadID", "StatsTypeID", "TimeStamp", "TimeRange", "Value"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( !empty($output["TimeStamp"]) && $output["TimeStamp"] instanceof \DateTime )	{
			$output["TimeStamp"] = $output["TimeStamp"]->format("Y-m-d H:i:s");
		}
		// FormatToDBEnd
        return $output;
    }

    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		if ( !is_null($row["StatsLoadID"]) )	{
			$row["StatsLoadID"] = intval($row["StatsLoadID"]);
		}
		if ( !is_null($row["StatsTypeID"]) )	{
			$row["StatsTypeID"] = intval($row["StatsTypeID"]);
		}
		if ( !empty($row["TimeStamp"]) )	{
			$row["TimeStamp"] = new \DateTime($row["TimeStamp"]);
		}
		if ( !is_null($row["TimeRange"]) )	{
			$row["TimeRange"] = intval($row["TimeRange"]);
		}
		// FormatFromDBEnd
    }
}
