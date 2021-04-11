<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class DataPoint extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "DataPoint";
	protected static $fields = ["id", "StatsLoadID", "StatsTypeID", "Value", "Timestamp", "SubSet", "Caption"];
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
		if ( !is_null($row["StatsLoadID"]) )	{
			$row["StatsLoadID"] = intval($row["StatsLoadID"]);
		}
		if ( !is_null($row["StatsTypeID"]) )	{
			$row["StatsTypeID"] = intval($row["StatsTypeID"]);
		}
		// FormatFromDBEnd
    }

    public function fetchForLoadID ( int $id )	{
    	return $this->fetchWhere ( "StatsLoadID = :StatsLoadID",
    			["StatsLoadID" => $id], "id");
    }

    public function fetchForLoadIDTypes ( int $id, array $types )	{
    	$sql = self::getSelectSQL();
    	$sql .= " WHERE StatsLoadID = ? AND StatsTypeID IN "
    			. "(".str_repeat("?,", count($types) - 1)."?)
			order by SubSet, id";
    			$select = $this->db->prepare($sql);
    			array_unshift($types, $id);
    			$select->execute($types);
    			return $this->fetchRetrieve($select);
    }

    public function fetchTypesForLoadID ( int $id )	{
    	$sql = "SELECT DISTINCT StatsTypeID
				FROM `DataPoint`
				WHERE StatsLoadID = ?";
    	return $this->fetchRAW($sql, [$id]);
    }

    public function timeRangeForLoadID ( int $id )	{
    	$sql = "SELECT MIN(`Timestamp`) as `start`, MAX(`Timestamp`) as `end`
				FROM `DataPoint`
				WHERE StatsLoadID = ?";
    	return $this->fetchRAW($sql, [$id]);
    }
}
