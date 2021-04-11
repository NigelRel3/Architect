<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class Workspace extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "Workspace";
	protected static $fields = ["id", "Name", "UserID", "CreatedOn", "Config"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";

    protected function formatToDB(): array  {
        $output = $this->data;
        // FormatToDB
		if ( empty($output["id"]) )	{
			$output["id"] = null;
		}
		if ( is_null($output["CreatedOn"]) )	{
			$output["CreatedOn"] = new \DateTime();
			$this->data["CreatedOn"] = $output["CreatedOn"];
		}
		if ( !empty($output["CreatedOn"]) && $output["CreatedOn"] instanceof \DateTime )	{
			$output["CreatedOn"] = $output["CreatedOn"]->format("Y-m-d H:i:s");
		}
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
		if ( !is_null($row["UserID"]) )	{
			$row["UserID"] = intval($row["UserID"]);
		}
		if ( !empty($row["CreatedOn"]) )	{
			$row["CreatedOn"] = new \DateTime($row["CreatedOn"]);
		}
		if ( !empty($row["Config"]) )	{
			$row["Config"] = json_decode($row["Config"], self::$jsonArrayMode);
		}
		// FormatFromDBEnd
    }

    public function getUserWorkspaces ( User $user ) : array	{
    	return $this->fetchWhere ( "UserID = :UserID", ["UserID" => $user->id],
    			"id");
    }
}
