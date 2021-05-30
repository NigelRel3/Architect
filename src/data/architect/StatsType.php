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
    	return $this->fetchSQL("FROM `StatsType`
					LEFT JOIN StatsTypeParent stp
						ON stp.StatsTypeID = `StatsType`.id
    				WHERE stp.ParentTypeID = :ParentTypeID",
    			["ParentTypeID" => $id]);

    }

    public function findForParentName ( string $name )	{
    	$typeBase = $this->fetchWhere("Name = :name",
    			["name" => $name]);
    	return $this->fetchTypesFor($typeBase[0]->id);
    }

    public function fetchUsedTypes ()	{
    	return $this->fetchWhere("EXISTS ( SELECT 1 FROM `DataPoint` dp
							WHERE dp.StatsTypeID = `StatsType`.id )", []);
    }

    public function fetchTypesFor ( int $id )	{
    	$sql = "with recursive cte (id, Name, Description, ParentTypeID) as (
		    SELECT st.id, st.Name, st.Description, stp.ParentTypeID FROM `StatsType` st
			    	JOIN `StatsTypeParent` stp ON stp.StatsTypeID = st.id
			    	WHERE stp.ParentTypeID = :id
			    UNION ALL
			    SELECT st1.id, st1.Name, st1.Description, stp1.ParentTypeID FROM `StatsType` st1
			    	JOIN `StatsTypeParent` stp1 ON stp1.StatsTypeID = st1.id
			    	JOIN cte on stp1.ParentTypeID = cte.id
			)
			SELECT * FROM cte";

		$typeList = $this->buildTypeHier($sql, ["id" => $id]);
		// Extract base type hieararchy
    	return $typeList[$id]['sub'];
    }

    public function fetchAllTypes ()	{
    	$sql = "with recursive cte (id, Name, Description, ParentTypeID) as (
		    SELECT st.id, st.Name, st.Description, stp.ParentTypeID FROM `StatsType` st
			    	LEFT JOIN `StatsTypeParent` stp ON stp.StatsTypeID = st.id
			    	WHERE stp.ParentTypeID is null
			    UNION ALL
			    SELECT st1.id, st1.Name, st1.Description, stp1.ParentTypeID FROM `StatsType` st1
			    	JOIN `StatsTypeParent` stp1 ON stp1.StatsTypeID = st1.id
			    	JOIN cte on stp1.ParentTypeID = cte.id
			)
			SELECT * FROM cte
				ORDER BY ParentTypeID, id";

		$typeList = $this->buildTypeHier($sql);
		// Extract base type hieararchy
    	return $typeList[null]['sub'] ?? [];
    }

    protected function buildTypeHier ( string $sql, $keys = [])	{
    	$typeList = $this->fetchRAW($sql, $keys);
    	// Transform arrays to make processing easier
		$typeList = array_column($typeList, null, "id");
		$typeIDs = array_column($typeList, "id");
		for ( $i = count($typeIDs)-1; $i >= 0; $i-- )	{
			$type = $typeList[$typeIDs[$i]];
			$parent = $type['ParentTypeID'];
			if ( !isset($typeList[$parent]['sub']) )	{
				$typeList[$parent]['sub'] = [];
			}
			// move to parent ID
			array_unshift($typeList[$parent]['sub'], $type);
		}

    	return $typeList;

    }
}
