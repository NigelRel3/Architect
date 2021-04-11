<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsLoad extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatsLoad";
	protected static $fields = ["id", "Name", "CreatedOn", "Notes", "OwnerID",
		"DataSource", "DataStartPoint", "DataEndPoint", "config", "ImportType",
		"Group", "GroupKey"
	];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( is_null($output["CreatedOn"]) )	{
			$output["CreatedOn"] = new \DateTime();
			$this->data["CreatedOn"] = $output["CreatedOn"];
		}
		if ( !empty($output["CreatedOn"]) && $output["CreatedOn"] instanceof \DateTime )	{
			$output["CreatedOn"] = $output["CreatedOn"]->format("Y-m-d H:i:s");
		}
		if ( !empty($output["DataStartPoint"]) && $output["DataStartPoint"] instanceof \DateTime )	{
			$output["DataStartPoint"] = $output["DataStartPoint"]->format("Y-m-d H:i:s");
		}
		if ( !empty($output["DataEndPoint"]) && $output["DataEndPoint"] instanceof \DateTime )	{
			$output["DataEndPoint"] = $output["DataEndPoint"]->format("Y-m-d H:i:s");
		}
		if ( !empty($output["config"]) )	{
			$output["config"] = json_encode($output["config"]);
		}
		if ( is_null($output["GroupKey"]) )	{
			$output["GroupKey"] = 0;
			$this->data["GroupKey"] = $output["GroupKey"];
		}
		// FormatToDBEnd
        return $output;
    }

    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		if ( !empty($row["CreatedOn"]) )	{
			$row["CreatedOn"] = new \DateTime($row["CreatedOn"]);
		}
		if ( !is_null($row["OwnerID"]) )	{
			$row["OwnerID"] = intval($row["OwnerID"]);
		}
		if ( !empty($row["DataStartPoint"]) )	{
			$row["DataStartPoint"] = new \DateTime($row["DataStartPoint"]);
		}
		if ( !empty($row["DataEndPoint"]) )	{
			$row["DataEndPoint"] = new \DateTime($row["DataEndPoint"]);
		}
		if ( !empty($row["config"]) )	{
			$row["config"] = json_decode($row["config"], self::$jsonArrayMode);
		}
		// FormatFromDBEnd
    }
}
