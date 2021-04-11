<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class AvailablePanelTypes extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "AvailablePanelTypes";
	protected static $fields = ["id", "Name", "ComponentName", "Description", "Config", "ParentPanelTypeID", "ParentPanelContext", "ParentMenuKey"];
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
		if ( !empty($row["Config"]) )	{
			$row["Config"] = json_decode($row["Config"], self::$jsonArrayMode);
		}
		if ( !is_null($row["ParentPanelTypeID"]) )	{
			$row["ParentPanelTypeID"] = intval($row["ParentPanelTypeID"]);
		}
		if ( !is_null($row["ParentMenuKey"]) )	{
			$row["ParentMenuKey"] = intval($row["ParentMenuKey"]);
		}
		// FormatFromDBEnd
    }
}
