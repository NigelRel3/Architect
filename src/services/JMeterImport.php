<?php
namespace Architect\services;

use Architect\data\architect\StatsLoad;
use Architect\data\architect\StatsType;
use Architect\data\architect\DataPoint;
use DateTime;

class JMeterImport	{
	protected $data = [];
	protected $statsLoad = null;
	protected $types = null;
	protected $dp = null;
	protected $subTypes = [];

	public function __construct( StatsType $types,
			DataPoint $dp)	{
		$this->types = $types;
		$this->dp = $dp;
	}

	public function __invoke( StatsLoad $statsLoad )	{
		$this->statsLoad = $statsLoad;

		$base = getenv("BASEDIR");
		if ( file_exists($base . $this->statsLoad->DataSource) )	{
			$this->subTypes = $this->types->findForParentName('jmeterimport');
			$extension = strtolower(pathinfo($statsLoad->DataSource)['extension']);

			if ( $extension === "csv" )	{
				$this->importCSV();
			}
			elseif ( $extension === "xml" )	{

			}
		}
		else	{
			$this->data['errors'] = "Import file not found";
		}

		return $this->data;
	}

	protected function importCSV()	{
		$this->data['Type'] = 'Importing CSV';

		$base = getenv("BASEDIR");
		$input = fopen($base . $this->statsLoad->DataSource, "r");
		$row = fgetcsv($input);
		$this->dp->beginTransaction();
		$dateStart = PHP_INT_MAX;
		$dateEnd = PHP_INT_MIN;

		$fieldsToImport = [1, 3, 4, 8, 12, 13, 14];
		$subSets = [];
		while (($row = fgetcsv($input)) !== false )	{
			foreach ( $fieldsToImport as $index )	{
				$fieldValue = $row[$index];
				$this->dp->set( [ "id" => null,
					"StatsLoadID" => $this->statsLoad->id,
					"StatsTypeID" => $this->subTypes[$index]->id,
					"Value" => ($fieldValue === 'null') ? null : $fieldValue,
					"Timestamp" => $row[0],
					"SubSet" => $row[5],
					"Caption" => $row[2]
				]);
				$this->dp->insert();
				$subSets[$row[5]] = true;
				$dateStart = min($dateStart, $row[0] / 1000);
				$dateEnd = max($dateEnd, $row[0] / 1000);
			}
		}
		// If only 1 subset, blank out to simplify reporting
		//
		if ( count($subSets) == 1 )	{
			$this->statsLoad->executeRAW("UPDATE `DataPoint`
									SET `SubSet` = null
									WHERE StatsLoadID = ?",
					[$this->statsLoad->id]);
		}
		$this->statsLoad->DataStartPoint = DateTime::createFromFormat( 'U.u', $dateStart);
		$this->statsLoad->DataEndPoint = DateTime::createFromFormat( 'U.u', $dateEnd);
		$this->statsLoad->update();
		$this->dp->commit();
		fclose($input);
	}
}