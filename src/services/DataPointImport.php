<?php
namespace Architect\services;

use Architect\data\architect\DataPoint;
use Psr\Http\Message\StreamInterface;
use DateTime;

class DataPointImport implements StreamInterface	{
	private $dataPoint = null;
	private $statsLoadID = null;
	private $subSet = null;

	private $prevDate = null;
	private $prevData = [];
	private $lastID = [];

	public function __construct(int $statsLoadID,
			DataPoint $dataPoint,
			string $subSet)	{
		$this->dataPoint = $dataPoint;
		$this->statsLoadID = $statsLoadID;
		$this->subSet = $subSet;
	}

	public function write($string) {
		$statsData = json_decode($string);

		$newDate = DateTime::createFromFormat("Y-m-d\TH:i:s.u",
					substr($statsData->read, 0, -4))->format("U.u");

		$blkIO = array_column($statsData->blkio_stats->io_service_bytes_recursive,
					'value', 'op');
		$rx = 0;
		$tx = 0;
		foreach ( $statsData->networks as $network )	{
			$rx += $network->rx_bytes;
			$tx += $network->tx_bytes;
		}

		if ( $this->prevDate !== null )	{
			$lapsed = $newDate - $this->prevDate;
			$this->dataPoint->beginTransaction();
			$this->storeDataPoint(22, intval($lapsed), $newDate, "lapsed");
			$this->storeDataPoint(23, $statsData->cpu_stats->cpu_usage->total_usage
					, $newDate, "cputotal", true);
			$this->storeDataPoint(24, $statsData->cpu_stats->cpu_usage->usage_in_kernelmode
					, $newDate, "cpukernel", true);
			$this->storeDataPoint(25, $statsData->cpu_stats->cpu_usage->usage_in_usermode
					, $newDate, "cpuuser", true);
			$this->storeDataPoint(26, $statsData->memory_stats->usage, $newDate, "memoryusage");
			$this->storeDataPoint(27, $statsData->memory_stats->max_usage, $newDate, "memorymax");

			$this->storeDataPoint(28, $blkIO['Read'], $newDate, "blkioRead", true);
			$this->storeDataPoint(29, $blkIO['Write'], $newDate, "blkioWrite", true);
			$this->storeDataPoint(30, $blkIO['Total'], $newDate, "blkioTotal", true);
			$this->storeDataPoint(31, $rx, $newDate, "networkRx_bytes", true);
			$this->storeDataPoint(32, $tx, $newDate, "networkTx_bytes", true);
			$this->dataPoint->commit();
		}

		$this->prevDate = $newDate;
		$this->prevCPUStats = $statsData->cpu_stats;
		$this->prevBlkIO = $blkIO;
		$this->prevNet = ["rx" => $rx, "tx" => $tx];

		return strlen($string);
	}

	protected function storeDataPoint ( int $statID, string $value,
				int $timestamp, string $caption,
				bool $offset = false): void	{
		$newValue = $value;
		if ( $offset ){
			$value -= ($this->prevData[$statID] ?? 0);
		}
		$this->dataPoint->set([ "id" => null, "StatsLoadID" => $this->statsLoadID,
			"StatsTypeID" => $statID, "Value" => $value,
			"Timestamp" => $timestamp, "SubSet" => $this->subSet,
			"Caption" => $caption ]
		);
		$this->dataPoint->insert();
		$this->lastID[$statID] = $this->dataPoint->id;
		$this->prevData[$statID] = $newValue;
	}

	public function getIDs(): array	{
		return $this->lastID;
	}

	public function loadPrevStats( array $dataPointID )	{
		foreach ( $dataPointID as $id )	{
			$this->dataPoint->fetch([$id]);
			$this->prevData[$this->dataPoint->StatsTypeID] = $this->dataPoint->Value;
		}
	}

	public function getMetadata($key = null) {
		return null;
	}

	public function isSeekable() {
		return false;
	}

	public function read($length) {
		return '';
	}

	public function tell() {
		return 0;
	}

	public function isWritable() {
		return true;
	}

	public function seek($offset, $whence = SEEK_SET) {
	}

	public function __toString() {
		return null;
	}

	public function getSize() {
		return null;
	}

	public function rewind() {
	}

	public function detach() {
		return null;
	}

	public function getContents() {
		return '';
	}

	public function close() {
	}

	public function eof() {
		return false;
	}

	public function isReadable() {
		return false;
	}


}