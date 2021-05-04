<?php
namespace Architect\services;

use Architect\data\architect\DataPoint;
use Architect\data\architect\StatsLoad;
use Architect\data\architect\StatsType;
use Psr\Log\LoggerInterface;
use DateTime;
use Exception;

/**
 * To collect...
 * /usr/lib/sysstat/sa1 interval #times
 * To extract data from file...
 * sadf -d /var/log/sysstat/sa26 -- -r -u -b -n DEV > sar26012021.csv
 *
 * ensure php docker image has sadf
 * apt update
 * apt install sysstat
 * @author nigel
 *
 */
class SARImport	{
	protected $data = [];
	protected $statsLoad = null;
	protected $types = null;
	protected $dp = null;
	protected $log = null;
	protected $subTypes = [];

	public function __construct( StatsType $types,
			DataPoint $dp,
			LoggerInterface $log)	{
		$this->types = $types;
		$this->dp = $dp;
		$this->log = $log;
	}

	public function __invoke( StatsLoad $statsLoad )	{
		$this->statsLoad = $statsLoad;

		$base = getenv("BASEDIR");
		if ( file_exists($base . $this->statsLoad->DataSource) )	{
			$outputFileName = $base . $this->statsLoad->DataSource . ".csv";
			$cmd = "sadf -d {$base}{$this->statsLoad->DataSource} -- -u -r -b -n DEV > {$outputFileName}";
			$output = [];
			$success = true;
			try {
				$ret = 0;
				exec($cmd, $output, $ret);
				// Return code of 0 means success
				if ( $ret != 0 )	{
					$message = implode("/", array_slice($output, 0, 5));
					$this->log->error("exec failed for import: {$message}");
					$success = false;
				}
			} catch (Exception $e) {
					$message = implode("/", array_slice($output, 0, 5));
					$this->log->error($message);
					$success = false;
			}

			if ( $success === true )	{
				$this->importCSV($outputFileName);
			}
			else	{
				$this->data['errors'] = "Failed to run import";
			}
		}
		else	{
			$this->data['errors'] = "Import file not found ({$base}{$this->statsLoad->DataSource})";
		}

		return $this->data;
	}

	protected function importCSV( $fileName )	{
		$this->data['Type'] = 'Importing CSV';

		$input = fopen($fileName, "r");
		$row = fgetcsv($input, null, ';');
		$this->dp->beginTransaction();
		$dateStart = PHP_INT_MAX;
		$dateEnd = PHP_INT_MIN;

		$dataset = 0;
		$fieldsToImport = [
			[4 => 42, 6 => 43, 7 => 55, 9 => 44 ],
			[7 => 50, 8 => 51],
			[3 => 46, 4 => 47, 5 => 48]
		];
		// Process up to dataset 3 which is network stats
		while ( $dataset < 3 &&
				($row = fgetcsv($input, null, ';')) !== false )	{
			if ( $row[0] === '# hostname' )	{
				$dataset++;
				continue;
			}
			$timeStamp = (new DateTime($row[2]))->getTimestamp();
			foreach ( $fieldsToImport[$dataset] as $fieldNo => $id )	{
				$this->dp->set( [ "id" => null,
					"StatsLoadID" => $this->statsLoad->id,
					"StatsTypeID" => $id,
					"Value" => $row[$fieldNo],
					"Timestamp" => $timeStamp,
					"Caption" => $row[0]
				]);
				$this->dp->insert();
				$dateStart = min($dateStart, $timeStamp);
				$dateEnd = max($dateEnd, $timeStamp);
			}
		}

		// Network stats are accumulated across all NIC's for time
		$prevTimeStamp = null;
		$rx = 0;
		$tx = 0;
		while (($row = fgetcsv($input, null, ';')) !== false )	{
			$timeStamp = (new DateTime($row[2]))->getTimestamp();
			if ( $timeStamp != $prevTimeStamp)	{
				if ( $prevTimeStamp )	{
					$this->dp->set( [ "id" => null,
						"StatsLoadID" => $this->statsLoad->id,
						"StatsTypeID" => 53,
						"Value" => $rx,
						"Timestamp" => $prevTimeStamp
					]);
					$this->dp->insert();
					$this->dp->set( [ "id" => null,
						"StatsTypeID" => 54,
						"Value" => $tx,
					]);
					$this->dp->insert();
				}
				$rx = 0;
				$tx = 0;
				$prevTimeStamp = $timeStamp;
			}
			$rx += $row[6];
			$tx += $row[7];
		}
		$this->dp->set( [ "id" => null,
			"StatsLoadID" => $this->statsLoad->id,
			"StatsTypeID" => 53,
			"Value" => $rx,
			"Timestamp" => $timeStamp
		]);
		$this->dp->insert();
		$this->dp->set( [ "id" => null,
			"StatsTypeID" => 54,
			"Value" => $tx,
		]);
		$this->dp->insert();
		$this->statsLoad->DataStartPoint = DateTime::createFromFormat( 'U', $dateStart);
		$this->statsLoad->DataEndPoint = DateTime::createFromFormat( 'U', $dateEnd);
		$this->statsLoad->update();
		$this->dp->commit();
		fclose($input);
	}
}