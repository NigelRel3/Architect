<?php
namespace Architect\data\architect;

use Architect\data\MySQL;

require_once __DIR__ . '/../../../vendor/autoload.php';

class AvailablePanelTypes extends MySQL	{
	protected static $table = "AvailablePanelTypes";
	protected static $fields = ["id", "Name", "ComponentName", "Description", "Config", "ParentPanelTypeID", "ParentPanelContext", "ParentMenuKey"];
	protected static $dateFields = [];
	protected static $jsonFields = ["Config"];
	protected static $pk = ["id"];
	protected static $autoIndexField = "id";
}
