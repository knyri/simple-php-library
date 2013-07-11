<?php
PackageManager::requireClassOnce('error.FileNotFoundException');
/**
 * Clas to parse a CSV file. Delimiter, enclosure, and escape characters can be set. The first line is automatically read when
 * the file is opened and used as the column headers. This version does not require the rows to have the same number of columns
 * as the header row.
 * Read lines may be cached for later access. If you do not need this feature or are reading a very large file I strongly suggest not turning it on.
 */
class CsvReader {
	private $FILE = null;
	private $HEADERS = array();
	private $DATA = array();
	private $ISOPEN = false;
	private $ROW_COUNT=1;
	private $header_count = 0;
	private $STATE=0;
	private $default_value = null;
	private $cache_rows = false;
	private $delimeter, $enclosure, $escape;
	private $line_type = null;
	private $fetch_type=0;
	const LINE_UNIX = 1, LINE_WIN = 2, LINE_MAC = 3;
	const STATE_OK = 0;
	const STATE_EOF = 1;
	const FETCH_NUM=0,FETCH_ASSOC=1;
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
	 * Parses a line of CSV. Empty fields are null.
	 * @param string $line The line.
	 * @param string $delim Field delimiter
	 * @param string $enclosure Text quote.
	 * @param string $escape Quote escape and escape escape.
	 * @return array An array containing the fields on this line or null if the line is empty.
	 */
	public function getline() {
		if ($this->STATE==CsvReader::STATE_EOF) return false;
		$delim=$this->delimeter;
		$enclosure=$this->enclosure;
		$escape=$this->escape;
		$return=array();
		$buf='';
		$quoted=false;
		$double=($enclosure==$escape);
		$checkdouble=false;
		$escaped=false;
		$c='';
		while(($c = fgetc($this->FILE))!==false) {
			if($quoted) {
				if($escaped) {
					if($double) {
						if($checkdouble) {//3xenclosure
							if($c==$enclosure) {//4xenclosure
								$buf .= $enclosure.$enclosure;
							}elseif($c==$delim) {//3xenclosure then delim
								$buf .= $enclosure;
								$return[] = $buf;
								$buf = '';
								$quoted=false;
							}else {
								$buf .= $enclosure.$c;
							}
							$escaped = false;
							$checkdouble = false;
						} elseif ($c==$enclosure) {
							$checkdouble=true;
						} elseif ($c==$delim) {//2xenclosure followed by delim
							$return[] = $buf;
							$buf = '';
							$quoted=false;
							$escaped=false;
						} elseif (in_array($c, array("\n","\r"))) {
							$this->setFileType($c);
							$return[] = $buf;
							$buf = '';
							break;
						} else {//2xenclosure followed by another char
							$buf .= $enclosure.$c;
							$quoted = false;
							$escaped = false;
						}
					} else {//escaped no double
						if ($c==$enclosure) {
							$buf .= $enclosure;
						} elseif ($c==$escape) {
							$buf .= $escape;
						} else {
							$buf .= $escape.$c;
						}
						$escaped = false;
					}
				} elseif ($c==$escape) {
					$escaped = true;
				} elseif ($c==$enclosure) {
					$quoted = false;
				} else {
					$buf .= $c;
				}
			} else {//not quoted
				if ($c==$enclosure) {
					$quoted=true;
				} else if ($c==$delim) {
					$return[] = $buf;
					$buf = '';
				} else if (in_array($c,array("\n","\r"))){
					$this->setFileType($c);
					if (count($return)==0) return array(null);
					$return[] = $buf;
					$buf = '';
					break;
				} else {
					$buf .= $c;
				}
			}//end quote
		}//end for
		if (feof($this->FILE)) {
			$this->STATE=CsvReader::STATE_EOF;
			if (count($return)===0) {
				return false;
			}
			$return[] = $buf;
		}
		if($this->cache_rows)
			$this->DATA[$this->ROW_COUNT]=$return;
		$this->ROW_COUNT++;
		return $return;
	}
	private function setFileType($c) {
		if (empty($this->line_type)) {
			if ($c=="\n") {
				$this->line_type = CsvReader::LINE_UNIX;
			} elseif ($c=="\r") {
				if (fgetc($this->FILE)=="\n") {
					$this->line_type = CsvReader::LINE_WIN;
				} else {
					$this->line_type = CsvReader::LINE_MAC;
					fseek($this->FILE, -1, SEEK_CUR);
				}
			}
		} elseif ($this->line_type==CsvReader::LINE_WIN) {
			fgetc($this->FILE);
		}
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
		if ($this->is_open())$this->reset();
		if (!is_file($file)) {throw new FileNotFoundException($file);}
			//echo "$file does not exist.";return false;}
		//include $file;
		$this->FILE = fopen($file, 'r');
		if (!$this->FILE) {throw new IOException("fopen failed for $file.");}//echo "fopen failed for $file.";return false;}
		$this->ISOPEN=true;
		$this->HEADERS=$this->getline();
		if ($this->HEADERS===false) {$this->STATE=CSVReader::STATE_EOF; throw new IOException("Error reading the headers.");}
		$this->header_count = count($this->HEADERS);
		$this->DEFAULTS = array_fill(0, $this->header_count, $this->default_value);
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
	public function get_row($row = null) {
		if (!$this->is_open()) return false;
		if ($row==null) {
			if ($this->STATE===CsvReader::STATE_EOF) return false;
			$row = $this->getline();
			if ($row===false) return false;
			if ($row==null) {$row=$this->DEFAULTS;} else {$row=$this->clear_empty_values($row);}
			$count = count($row);
			if ($this->header_count > $count) array_fill($count, $this->header_count-$count, $this->default_value);
			return $this->fetch_type===CsvReader::FETCH_ASSOC?array_combine($this->HEADERS, $row):$row;
		} else {
			if (!$this->cache_rows) return false;
			if ($row < $this->ROW_COUNT && $row>0)
				return $this->fetch_type===CsvReader::FETCH_ASSOC?array_combine($this->HEADERS, array_combine($this->HEADERS, $this->DATA[$row-1])):$this->DATA[$row-1];
			while ($ROW_COUNT < $row) {
				if (!get_row()) return false;
			}
			return $this->fetch_type===CsvReader::FETCH_ASSOC?array_combine($this->HEADERS, array_combine($this->HEADERS, $this->DATA[$row-1])):$this->DATA[$row-1];
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
