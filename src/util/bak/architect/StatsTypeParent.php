<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class StatsTypeParent extends MySQL	{
	protected static $table = "StatsTypeParent";
	protected static $fields = ["StatsTypeID", "ParentTypeID"];
	protected static $dateFields = [];
    protected static $jsonFields = [];
	protected static $pk = ["StatsTypeID", "ParentTypeID"];
	protected static $autoIndexField = null;
	
	public function findForParentID ( int $id )	{
		return $this->fetchWhere ( "ParentTypeID = :ParentTypeID",
				["ParentTypeID" => $id], "StatsTypeID");
	}
}
