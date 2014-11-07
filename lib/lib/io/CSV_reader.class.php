<?php
/**
 * @package io
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//require_once LIB.'lib/error/class_FileNotFoundException.php';
//require_once LIB.'lib/error/class_IOException.php';
PackageManager::requireClassOnce('lib.error.FileNotFoundException');
/**
 * Reads a comma seperated value file.
 * Default value for empty cells is null.
 * Default value for row caching is true.
 * @author Kenneth Pierce
 *
 */
class CSVReader {
	private $FILE = null;
	private $HEADERS = array();
	private $DATA = array();
	private $ISOPEN = false;
	private $ROW_COUNT=0;
	private $STATE=0;
	private $default_value = null;
	private $cache_rows = true;
	private $delimeter, $enclosure, $escape;
	const STATE_OK = 0;
	const STATE_EOF = 1;

	/**
	 * Creates a new CSV reader.
	 * @param string $delim [optional] single character field delimiter. defaults to ','
	 * @param string $enclosure [optional] single character string enclosure. defaults to '"'
	 * @param string $escape [optional] single character escape string. defaults to backslash
	 */
	function __construct($delim = ',', $enclosure = '"', $escape = '\\') {
		$this->delimeter = $delim;
		$this->enclosure = $enclosure;
		$this->escape = $escape;
	}
	/**
	 * Closes the file and unsets the data.
	 */
	function __destruct() {
		@fclose($this->FILE);
		unset($this->FILE, $this->HEADERS, $this->DATA);
	}
	/**
	 * Sets any empty or null values on this row to the default.
	 * @param array $row
	 * @return array The row
	 * @access private
	 */
	private function clear_empty_values($row) {
		foreach( $row as $key => $datum)
			if (($datum==='' || $datum===null)) $row[$key] = $this->default_value;
		return $row;
	}
	/**
	 * Opens the file for reading.
	 * @param string $file Path to the file
	 * @throws FileNotFoundException if the file is not found
	 * @throws IOException if the file could not be opened
	 * @return boolean true if the file was found and opened.
	 * @access public
	 */
	public function open($file) {
		if (!is_file($file)) {throw new FileNotFoundException($file);}
			//echo "$file does not exist.";return false;}
		$this->FILE = fopen($file, 'r');
		if (!$this->FILE) {throw new IOException("fopen failed for $file.");}//echo "fopen failed for $file.";return false;}
		$this->ISOPEN=true;
		$this->HEADERS=fgetcsv($this->FILE,0, $this->delimeter, $this->enclosure, $this->escape);
		if ($this->HEADERS===false) {$this->STATE=CSVReader::STATE_EOF; throw new IOException("Error reading the headers.");}
		$this->DEFAULTS = array_fill(0, count($this->HEADERS), $this->default_value);
		return true;
	}
	/**
	 * Resets the reader for reuse.
	 * @access private
	 */
	private function reset() {
		if ($this->is_open()) @fclose($this->FILE);
		unset($this->FILE, $this->HEADERS, $this->DATA);
		$this->HEADERS = array();
		$this->DATA = array();
		$this->ISOPEN = false;
		$this->ROW_COUNT=0;
		$this->STATE=CSVReader::STATE_OK;
		$this->default_value = null;
		$this->cache_rows = true;
	}
	/**
	 * returns the headers as an array.
	 * @return boolean|array: false if the file is not opened
	 * @access public
	 */
	public function get_headers() {
		if (!$this->is_open()) return false;
		return $this->HEADERS;
	}
	/**
	 * Gets the specified row from the file. If the row number
	 * is not specified then the next row is retrieved. The returned array is associative.
	 * @param int $row[optional] The row number.
	 * @return boolean|multitype: false if the file is not open or the end is reached.
	 * @access public
	 */
	public function get_row(int $row = null) {
		if (!$this->is_open()) return false;
		if ($row==null) {
			$row = fgetcsv($this->FILE, 0, $this->delimeter, $this->enclosure, $this->escape);
			if ($row==false) {$this->STATE=CSVReader::STATE_EOF; return false;}
			if ($row==null) {$row=$this->DEFAULTS;} else {$row=$this->clear_empty_values($row);}
			if (count($this->HEADERS) != count($row)) throw new Exception('Row count and header count do not match. Header:'.count($this->HEADERS).' Row:'.count($row).var_export($this->HEADERS, true).var_export($row, true), 0);
			if ($this->cache_rows)
				$this->DATA[$this->ROW_COUNT] = $row;
			$this->ROW_COUNT++;
			return array_combine($this->HEADERS, $row);
		} else {
			if (!$this->cache_rows) return false;
			if ($row < $this->ROW_COUNT && $row>0)
				return array_combine($this->HEADERS, $this->DATA[$row-1]);
			while ($ROW_COUNT < $row) {
				if (!get_row()) return false;
			}
			return array_combine($this->HEADERS, $this->DATA[$row-1]);
		}
	}
	/**
	 * gets the number of rows read.
	 * @return number The number of rows read.
	 * @access public
	 */
	public function getRowCount() {
		return $this->ROW_COUNT;
	}
	/**
	 * Gets the current state of the object.
	 * @return number
	 * @access public
	 */
	public function getState() {
		return $this->STATE;
	}
	/**
	 * Gets the file open state.
	 * @return boolean true if open
	 * @access public
	 */
	public function is_open() {
		return $this->ISOPEN;
	}
	/**
	 * Turns row caching on or off.
	 * @param boolean $value true or false
	 * @access public
	 */
	public function set_cache_rows($value) {
		$this->cache_rows = ($value===true);
	}
	/**
	 * Sets the default value for empty or null cells.
	 * @param mixed $value
	 * @access public
	 */
	public function set_default_value($value) {
		$this->default_value = $value;
		if ($this->ISOPEN)
			$this->DEFAULTS = array_fill(0, count($this->HEADERS), $this->default_value);
	}
}
?>