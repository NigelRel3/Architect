<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class DataPoint extends MySQL	{
	protected static $table = "DataPoint";
	protected static $fields = ["id", "SourceID", "StatsTypeID", "Value", "Timestamp", "SubSet", "Caption"];
	protected static $dateFields = [];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

	public function fetchForLoadID ( int $id )	{
		return $this->fetchWhere ( "SourceID = :SourceID",
				["SourceID" => $id], "id");
	}

	public function fetchForLoadIDTypes ( int $id, array $types )	{
		$sql = self::getSelectSQL();
		$sql .= " WHERE SourceID = ? AND StatsTypeID IN "
				. "(".str_repeat("?,", count($types) - 1)."?)
			order by SubSet, id";
		$select = $this->db->prepare($sql);
		array_unshift($types, $id);
		$select->execute($types);
		return $this->fetchRetrieve($select);
	}

	public function fetchTypesForLoadID ( int $id )	{
		$sql = "SELECT DISTINCT StatsTypeID FROM `DataPoint` where SourceID = ?";
		return $this->fetchRAW($sql, [$id]);
	}
}
