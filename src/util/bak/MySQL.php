<?php
namespace Architect\data;

class MySQL {
	/**
	 * Database connection
	 * @var \PDO
	 */
	protected $db = null;
	protected $data = null;
	protected $active = false;

	protected static $fields = [];
	protected static $dateFields = [];
	protected static $jsonFields = [];
	protected static $pk = [];
	protected static $autoIndexField = null;
	protected static $table = null;

	protected static $insertSQL = [];
	protected static $selectSQL = [];
	protected static $sqlWhere = [];
	protected static $updateSQL = [];
	protected static $deleteSQL = [];
	protected static $columnNames = [];
	protected static $pkFlip = [];

	public function __construct( \PDO $db )	{
		$this->active = false;
		if ( static::$table === null )	{
			$class = static::class;
			static::$table = substr($class, strrpos($class, "\\")+1);
		}
		$this->db = $db;
		$this->data = array_fill_keys(static::$fields, null);
	}

	protected static function getTableSet ( array &$set, callable $genData)	{
		if ( isset( $set[static::class]) )	{
			$data = $set[static::class];
		}
		else	{
			$data = $genData();
			$set[static::class]= $data;
		}
		return $data;
	}

	public function getTableName ()	: string {
		return static::$table;
	}

	public function getFields() : array	{
		return static::$fields;
	}

	public function getColumnNames() : array	{
		return self::getTableSet(self::$columnNames, function ()	{
			$columns = [];
			$table = static::$table;
			foreach ( static::$fields as $field )	{
				$columns[] = "`{$table}`.`{$field}`";
			}
			return $columns;
		});
	}

	public function validate()	{
		return [];
	}

	protected static function getInsertSQL() : string	{
		return self::getTableSet(self::$insertSQL,
				[get_called_class(), 'genInsertSQL']);
	}

	protected static function genInsertSQL( array $fields = null ) : string	{
		$fields = $fields ?? static::$fields;
		$sql = "INSERT INTO `".static::$table."`(`".
				implode("`, `", $fields)."`)".
				" VALUES (:".implode(", :", $fields).")
			ON DUPLICATE KEY UPDATE ";
		foreach ( $fields as $field )	{
			$sql .= "`{$field}` = :{$field}, ";
		}
		return substr($sql, 0, -2);
	}

	protected $prepInsert = null;

	public function insert (): bool  {
		$this->active = false;

		$modified = false;
		// Remove null columns to allow default values
		$filtered = [];
		foreach ( $this->data as $name => $value )	{
			if ( !is_null($value) )	{
				if ( in_array($name, static::$jsonFields) )	{
					$value = json_encode($value);
				}
				$filtered[$name] = $value;
			}
			else	{
				$modified = true;
			}
		}
		if ( $modified == false && count(static::$fields) == count($this->data))	{
			if ( $this->prepInsert === null )	{
				$sql = static::getInsertSQL();
				$this->prepInsert = $this->db->prepare($sql);
			}
			$insert = $this->prepInsert;
		}
		else	{
			$sql = static::genInsertSQL(array_keys($filtered));
			$insert = $this->db->prepare($sql);
		}
		$insert->execute($filtered);
		if ( static::$autoIndexField != null )    {
			$this->data[static::$autoIndexField] = $this->db->lastInsertId();
		}
		$this->active = true;

		return $this->active;
	}

	public function getPK() : array	{
		return array_intersect_key($this->data, $this->getPKFlip());
	}

	protected function getPKFlip() : array	{
		return self::getTableSet(static::$pkFlip, function () {
			return array_flip(static::$pk);
		});
	}

	protected $prepUpdate = null;

	protected static function getUpdateSQL() : string	{
		return self::getTableSet(self::$updateSQL, function() {
			$sql = "UPDATE `".static::$table."` SET ";
			foreach ( static::$fields as $field )	{
				$sql .= "`{$field}` = :{$field}, ";
			}
			$sql = substr($sql, 0, -2) . static::getSQLWhere();

			return $sql;
		});
	}

	public function update (): bool  {
		if ( $this->prepUpdate === null )	{
			$sql = self::getUpdateSQL();
			$this->prepUpdate = $this->db->prepare($sql);
		}
		if ( !empty(static::$jsonFields) )	{
			foreach ( static::$jsonFields as $field ){
				$this->data[$field] = json_encode($this->data[$field]);
			}
		}
		return $this->prepUpdate->execute($this->data);
	}

	protected $prepSelect = null;

	protected static function getSelectSQL() : string	{
		return self::getTableSet(self::$selectSQL, function() {
			return "SELECT `".implode("`, `", static::$fields).
			"` FROM `".static::$table."` ";
		});
	}

	protected static function getSQLWhere() : string	{
		return self::getTableSet(self::$sqlWhere, function() {
			$where = " WHERE ";
			foreach ( static::$pk as $keyField )	{
				$where .= "`{$keyField}` = :{$keyField} and ";
			}
			return substr($where, 0, -5);;
		});
	}

	public function fetch( array $keys ) : bool    {
		$keys = array_map('strval', $keys);
		if ( array_key_first($keys) === 0 )	{
			$keys = array_combine(static::$pk, $keys);
		}
		if ( $this->prepSelect === null )	{
			$sql = self::getSelectSQL() . static::getSQLWhere();
			$this->prepSelect = $this->db->prepare($sql);
		}
		$this->prepSelect->execute($keys);
		if ( $found = $this->fetchRetrieve($this->prepSelect) )	{
			$this->data = $found[0]->data;
			$this->active = true;
			return true;
		}
		$this->data = null;
		$this->active = false;
		return false;
	}

	public function fetchWhere( string $where, array $keys, string $orderBy = null )
			: array    {
		$sql = self::getSelectSQL() . " WHERE ".$where;
		if ( $orderBy != null ){
			$sql .= " ORDER BY ".$orderBy;
		}
		$select = $this->db->prepare($sql);
		$select->execute($keys);
		return $this->fetchRetrieve($select);
	}

	public function fetchAll( string $orderBy = null ): array    {
		$sql = self::getSelectSQL();
		if ( $orderBy != null ){
			$sql .= " ORDER BY ".$orderBy;
		}
		$select = $this->db->prepare($sql);
		$select->execute();
		return $this->fetchRetrieve($select);
	}

	public function fetchIn( array $keys ) : array    {
		$sql = self::getSelectSQL();
		$sql .= " WHERE (".implode(",", static::$pk).") IN (";
		$part = "(".str_repeat("?,", count(static::$pk) - 1)."?)";
		$sqlParams = [];
		$data = [];
		foreach ( $keys as $key )	{
			$sql .= $part.",";
			$sqlParams = array_merge($sqlParams, $key);
		}
		if ( count($sqlParams) > 0 )	{
			$sql = substr($sql, 0, -1). ")";
			$select = $this->db->prepare($sql);
			$select->execute($sqlParams);
			$dbData = $this->fetchRetrieve($select);
			$data = array_merge($data, $dbData);
		}
		return $data;
	}

	/**
	 * @param string - SQL segment
	 * @param array - key values
	 * @return array
	 */
	public function fetchSQL( string $sql, array $keys = null) : array    {
		// $sql = "SELECT `" . implode("`, `", static::$fields) . "` " . $sql;
		$sql = "SELECT " . implode(", ", $this->getColumnNames()) .
			" " . $sql;
		$this->prepSelect = $this->db->prepare($sql);
		$this->prepSelect->execute($keys);
		return $this->fetchRetrieve($this->prepSelect);
	}

	/**
	 * @param string - SQL segment
	 * @param array - key values
	 * @return array
	 */
	public function fetchRAW( string $sql, array $keys = null) : array    {
		$prepSelect = $this->db->prepare($sql);
		$prepSelect->execute($keys);
		return $prepSelect->fetchAll();
	}

	public function executeRAW( string $sql, array $keys = null) : bool    {
		$prepSelect = $this->db->prepare($sql);
		return $prepSelect->execute($keys);
	}

	protected function fetchRetrieve ( \PDOStatement $select ) : array	{
		$selected = [];
		$class = static::class;
		while ( $row = $select->fetch(\PDO::FETCH_ASSOC) ) {
			$newObject = new $class($this->db);
			if ( !empty(static::$jsonFields) )	{
				foreach ( static::$jsonFields as $field ){
					$row[$field] = json_decode($row[$field], true);
				}
			}
			$newObject->data = $row;
			$newObject->active = true;
			$selected[] = $newObject;
		}
		return $selected;
	}

	protected $prepDelete = null;

	protected static function getDeleteSQL() : string	{
		return self::getTableSet(self::$deleteSQL, function() {
			return "DELETE FROM `" . static::$table . "`"
					. static::getSQLWhere();
		});
	}

	public function delete (): bool  {
		if ( $this->prepDelete === null )	{
			$sql = self::getDeleteSQL();
			$this->prepDelete = $this->db->prepare($sql);
		}
		$key = array_intersect_key($this->data, $this->getPKFlip());
		$this->prepDelete->execute($key);
		$this->active = false;

		return !$this->active;
	}

	public function __get( string $name )	{
		if ( array_search($name, static::$fields) === false )	{
			throw new \InvalidArgumentException("Unknown variable: {$name}");
		}
		//if ( in_array($name, $this->getTableSet(static::$dateFields)) )	{
		if ( in_array($name, static::$dateFields) )	{
			return new \DateTime($this->data[$name]);
		}
		return $this->data[$name];
	}

	public function __set( string $name, $value ) : void	{
		if ( array_search($name, static::$fields) === false )	{
			throw new \InvalidArgumentException("Unknown variable: {$name}");
		}
		if ( $value instanceof \DateTime ){
			$value = $value->format("Y-m-d H:i:s");
		}
		$this->data[$name] =  $value;
	}

	public function __isset( string $name )	{
		return isset($this->data[$name]);
	}

	public function set ( array $data ) : void	{
		// Check only valid fields passed in
		$extraKeys = array_diff_key($data, $this->data);
		if ( count($extraKeys) != 0 )	{
			throw new \InvalidArgumentException("Unknown variable: "
					. implode(",", array_keys($extraKeys)));
		}
		foreach ( static::$dateFields as $dateField )	{
			if ( isset ($data[$dateField]) && $data[$dateField] instanceof \DateTime )	{
				$data[$dateField] = $data[$dateField]->format("Y-m-d H:i:s");
			}
		}
		$this->data = array_replace($this->data, $data);
	}

	public function get () : array	{
		$data = $this->data;
		foreach ( static::$dateFields as $dateField )	{
			$data[$dateField] = new \DateTime($data[$dateField]);
		}
		return $data;
	}


	public function beginTransaction(): bool	{
		return $this->db->beginTransaction();
	}

	public function rollback(): bool	{
		return $this->db->rollBack();
	}

	public function commit(): bool	{
		return $this->db->commit();
	}
}