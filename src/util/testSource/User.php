<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class User extends MySQL	{
    protected static $jsonArrayMode = true;
	protected static $table = "User";
	protected static $fields = ["id", "Name", "Notes", "CreatedOn", "EMail", "Password", "Access", "DefaultWorkspaceID", "uuid"];
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
		if ( is_null($output["Access"]) )	{
			$output["Access"] = "0";
			$this->data["Access"] = $output["Access"];
		}
		if ( is_null($output["DefaultWorkspaceID"]) )	{
			$output["DefaultWorkspaceID"] = "0";
			$this->data["DefaultWorkspaceID"] = $output["DefaultWorkspaceID"];
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
		if ( !is_null($row["Access"]) )	{
			$row["Access"] = intval($row["Access"]);
		}
		if ( !is_null($row["DefaultWorkspaceID"]) )	{
			$row["DefaultWorkspaceID"] = intval($row["DefaultWorkspaceID"]);
		}
		// FormatFromDBEnd
    }

    public function login ( string $name, string $password ) : bool	{
    	$users = $this->fetchWhere ( "name = :name", ["name" => $name]);
    	if ( count($users) === 1 && password_verify($password, $users[0]->Password) )	{
    		$this->set($users[0]->data);
    		return true;
    	}
    	return false;
    }

    public function findUUID ( string $uuid )	{
    	$users = $this->fetchWhere ( "uuid = :uuid", ["uuid" => $uuid]);
    	return count($users) == 1 ? $users[0] : false;
    }
}
