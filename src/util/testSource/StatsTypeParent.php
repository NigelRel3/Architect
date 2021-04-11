<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsTypeParent extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatsTypeParent";
	protected static $fields = ["StatsTypeID", "ParentTypeID"];
	protected static $pk = ["StatsTypeID", "ParentTypeID"];
	protected static $autoIndexField = null;

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		// FormatToDBEnd
        return $output;
    }

    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["StatsTypeID"]) )	{
			$row["StatsTypeID"] = intval($row["StatsTypeID"]);
		}
		if ( !is_null($row["ParentTypeID"]) )	{
			$row["ParentTypeID"] = intval($row["ParentTypeID"]);
		}
		// FormatFromDBEnd
    }

    public function findForParentID ( int $id )	{
    	return $this->fetchWhere ( "ParentTypeID = :ParentTypeID",
    			["ParentTypeID" => $id], "StatsTypeID");
    }
}
