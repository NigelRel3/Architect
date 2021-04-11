<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class Workspace extends MySQL	{
	protected static $table = "Workspace";
	protected static $fields = ["id", "Name", "UserID", "CreatedOn", "Config"];
	protected static $dateFields = ["CreatedOn"];
	protected static $jsonFields = ["Config"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
	
	public function getUserWorkspaces ( User $user ) : array	{
		return $this->fetchWhere ( "UserID = :UserID", ["UserID" => $user->id],
				"id");
	}
}
