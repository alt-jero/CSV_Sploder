#!/usr/bin/env php
<?php
const BASE_DIR = __DIR__ . '/';
const INPUT_FILE = BASE_DIR . 'Open_Data_RDW__Gekentekende_voertuigen.csv';
const OUTPUT_DIR = BASE_DIR . 'Biglist_Outputs/';
const INDEX_FILE = OUTPUT_DIR . 'index.csv';


const DEFAULT_BLOCK_SIZE = 8192;
const BLOCK_SIZE = 16 * DEFAULT_BLOCK_SIZE;

const CLN = "\033[2K\r";

class IO_FILE {
	private $fp;
	private $filename;

	public function getFilename() { return $this->filename; }
	public function getFileHandle() { return $this->fp; }

	protected function _open($filename, $mode) {
		$this->filename = $filename;
		$this->fp = fopen($filename, $mode);

		$this->_confirm_open();
	}

	protected function _filesize() {
		return filesize($this->filename);
	}

	protected function _tell() {
		return ftell($this->fp);
	}

	protected function _confirm_open() {
		if(!$this->getFileHandle()) throw new Exception('File not open... '.$this->getFilename());
	}

	protected function _write($data) {
		return fwrite($this->fp, $data);
	}

	protected function _gets() {
		return fgets($this->fp);
	}

	protected function _eof() {
		return feof($this->fp);
	}

	protected function _read($length) {
		return fread($this->fp, $length);
	}

	protected function _close() {
		fclose($this->fp);
	}

	public function __destruct() {
		$this->_close();
	}
}

class OutputFile extends IO_FILE{
	private $buffer = '';

	public function __construct($filename) {
		$this->_open($filename, 'w');
	}

	private function flush() {
		$result = $this->_write($this->buffer);
		if($result === false) throw new Exception('File Write Error:'.$this->getFilename());
		$this->buffer = substr($this->buffer, $result);
	}

	protected function writeLine($data) {
		if(strlen($this->buffer) > BLOCK_SIZE) $this->flush();
		$this->buffer .= $data.PHP_EOL;
	}

	public function __destruct() {
		while(strlen($this->buffer) > 0) {
			$this->flush();
		}
		parent::__destruct();
	}
}

class indexFile extends OutputFile {
	private $counter = 0;

	public function add($header) {
		$this->writeLine($this->counter.':'.$header);
		$this->counter ++;
	}

}
class columnFile extends OutputFile {
	public function addRow($key, $value) {
		$this->writeLine($key.','.$value);
	}
}

class InputFile extends IO_FILE {
	private $headers = [];

	public function __construct($filename) {
		$this->_open($filename, 'r');
	}

	public function getHeaders() {
		if($this->headers !== []) return $this->headers;

		$headerLine = $this->_gets();

		$this->headers = str_getcsv($headerLine);
		return $this->headers;
	}

	public function fractionalPosition() {
		return $this->_tell() / $this->_filesize();
	}

	public function __invoke($block_size = BLOCK_SIZE) {
		$this->getHeaders();

		$left='';
		while (!$this->_eof()) {// read the file

			$temp = $this->_read($block_size);
			$fgetslines = explode(PHP_EOL, $temp);
			$fgetslines[0] = $left.$fgetslines[0];

			if(!$this->_eof()) $left = array_pop($fgetslines);

			foreach($fgetslines as $k => $line){
				yield $line;
			}
		}
	}
}

class TimeKeeper {
	private $start;
	private $lastProjection = 0;

	public function __construct() {
		$this->start = microtime(true);
	}

	public function elapsed() {
		return microtime(true) - $this->start;
	}

	public function projection($percentage) {
		return $this->lastProjection = $this->elapsed() / $percentage * (100 - $percentage);
	}

	public function totalTime() {
		return $this->lastProjection + $this->elapsed();
	}
}

class ProgressDisplay extends TimeKeeper{
	private $percentage = 0.0001;

	public function __invoke($percentage) {
		if($percentage > 0)
			$this->percentage = $percentage;
	}

	public function timeElapsed() {
		return $this->secondsToTime($this->elapsed());
	}

	public function timeRemaining() {
		return $this->secondsToTime($this->projection($this->percentage));
	}

	public function timeTotal() {
		return $this->secondsToTime($this->totalTime());
	}

	public function outputStatusLine($percentage = 0) {
		$this($percentage);
		echo CLN
		, 'Processing... ('
		, $this->decimalPercentage($percentage)
		, '%) Elapsed:'
		, $this->timeElapsed()
		, ' Remaining:'
		, $this->timeRemaining()
		, ' Total:'
		, $this->timeTotal();
	}

	private function secondsToTime($s) {
		$h = floor($s / 3600);
		$s -= $h * 3600;
		$m = floor($s / 60);
		$s -= $m * 60;
		return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
	}

	private function decimalPercentage($p) {
		return sprintf('%.02f', $p);
	}
}

class ColumnFiler {
	private $files = [];
	private $outputDir;

	private $numColumns = 0;

	public function __construct($outputDir = OUTPUT_DIR) {
		if(!file_exists($outputDir)) throw new Exception('Output Directory Does Not Exist:'.$outputDir);
		$this->outputDir = $outputDir;
	}

	public function registerOutputFiles($headers) {
		$index = new indexFile($this->outputDir.'index.csv');

		foreach($headers as $n => $type) {
			$index->add($type);
			$this->files[$n] = new columnFile($this->outputDir.$n.'.csv');
			$this->files[$n]->addRow('Kenteken', $type); // FIXME: Un-Hardcode This!!!
		}

		$this->numColumns = $n;
	}

	public function process($fields) {
		foreach($fields as $index => $field) {
			if($field === '') continue;
			// Re-escape quotes and commas
			if(strpos($field, ',') > -1) {
				$field = str_replace('"', '\\"', $field);
				$field = '"'.$field.'"';
			}
			$this->files[$index]->addRow($fields[0], $field);
		}
	}
}

class Orchestrator {
	public function __construct() {
		$progressDisplay = new ProgressDisplay();
		$columnFiler = new ColumnFiler(OUTPUT_DIR);
		$in = new InputFile(INPUT_FILE);

		$columnFiler->registerOutputFiles($in->getHeaders());

		foreach($in() as $n => $line) {	
			$columnFiler->process(str_getcsv($line));
			if($n % 1024 === 0) $progressDisplay->outputStatusLine($in->fractionalPosition() * 100);
			if($n > 100000) {
				$progressDisplay->outputStatusLine($in->fractionalPosition() * 100);
				echo "\n";
				exit();
			}
		}

		echo "Finished Processing File. Outputs Saved. \n";
	}
}


new Orchestrator();















