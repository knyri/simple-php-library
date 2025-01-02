<?php

PackageManager::requireClassOnce('io.file');

class CsvWriter {
	private $file = null;
	private $ROW_COUNT=1;
	private $delimeter, $enclosure, $escape;
	private $eol;
	private $onNewLine= true;

	/**
	 * Creates a new CSV writer.
	 * @param string $delim [optional] single character field delimiter. defaults to ','
	 * @param string $enclosure [optional] single character string enclosure. defaults to '"'
	 * @param string $escape [optional] single character escape string. defaults to backslash
	 */
	function __construct($file, $delim = ',', $enclosure = '"', $escape = '\\', $eol= false) {
		$this->file= new File($file);
		$this->delimeter = $delim;
		$this->enclosure = $enclosure;
		$this->escape = $escape . $enclosure;
		if(!is_string($eol)){
			$this->eol= PHP_EOL;
		}else{
			$this->eol= $eol;
		}
	}
	public function open(){
		if(strpos($this->file->getFile(), '://') === false && !$this->file->exists()){
			$this->file->ensureDir();
		}
		$this->ROW_CCOUNT= 0;
		return $this->file->open('w+');
	}
	public function close(){
		if($this->file->isOpen()){
			if($this->onNewLine === false){
				$this->file->write($this->eol);
			}
			return $this->file->close();
		}
		return true;
	}

	public function getRowCount(){
		return $this->ROW_COUNT;
	}

	public function writeLine($v= false){
		if($v !== false){
			if(is_array($v) || func_num_args() == 1){
				$this->write($v);
			}else{
				$this->write(func_get_args());
			}
		}
		$this->file->write($this->eol);
		$this->ROW_COUNT++;
		$this->onNewLine= true;
		return $this;
	}
	public function write($v){
		if(func_num_args() > 1){
			$args= func_get_args();
			foreach($args as $val){
				$this->writeValue($val);
			}
		}else if(is_array($v)){
			foreach($v as $val){
				$this->writeValue($val);
			}
		}else{
			$this->writeValue($v);
		}
		return $this;
	}
	private function writeValue($v){
		if($this->onNewLine){
			$this->onNewLine= false;
		}else{
			$this->file->write($this->delimeter);
		}
		$v= (string) $v;
		$this->file->write($this->enclosure . str_replace($this->enclosure, $this->escape, $v) . $this->enclosure);
	}

}