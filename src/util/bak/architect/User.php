<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class User extends MySQL	{
	protected static $fields = ["id", "Name", "Notes", "CreatedOn", "EMail", "Password", "Access", "DefaultWorkspaceID", "uuid"];
	protected static $dateFields = ["CreatedOn"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
	
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
