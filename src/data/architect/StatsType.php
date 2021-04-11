<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsType extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "StatsType";
	protected static $fields = ["id", "Name", "Description"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		// FormatToDBEnd
        return $output;
    }

    protected function formatFromDB(array &$row): void  {
        // FormatFromDB
		if ( !is_null($row["id"]) )	{
			$row["id"] = intval($row["id"]);
		}
		// FormatFromDBEnd
    }

    public function findForParentID ( int $id )	{
    	$st = $this->fetchRAW("SELECT id, Name, Description
					FROM `StatsType` st
    				LEFT JOIN StatsTypeParent stp
						ON stp.StatsTypeID = st.id
    				WHERE stp.ParentTypeID = :ParentTypeID",
    			["ParentTypeID" => $id]);
    	$data = [];
    	foreach ( $st as $sType )	{
    		$new = clone $this;
    		$new->set($sType);
    		$data[] = $new;
    	}
    	return $data;
    }

    public function findForParentName ( string $name )	{
    	$typeBase = $this->fetchWhere("Name = :name",
    			["name" => $name]);
    	return $this->findForParentID($typeBase[0]->id);
    }

    public function fetchUsedTypes ()	{
    	$sql = "SELECT * FROM `StatsType` st
			WHERE EXISTS ( SELECT 1 FROM `DataPoint` dp
							WHERE dp.StatsTypeID = st.id )";
    	return $this->fetchRAW($sql);
    }
}
