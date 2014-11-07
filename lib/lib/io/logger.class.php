<?php
/**
 * @package io
 * @subpackage logging
 */
PackageManager::requireClassOnce('io.file');
/**
 * Simple File Logger
 * @author Ken
 */
class Logger extends File{
	const ERR=1,WARN=2,INFO=4,DEBUG=8,ALL=-1;
	private $logLevel=self::ALL;
	public function log($str,$level=0){
		if(($level&$this->logLevel)==$level){
			switch($level){
				case self::ERR:
					$this->write('[ERR]  '.date('[Ymd HisO]').$str.EOL);
				break;
				case self::WARN:
					$this->write('[WARN] '.date('[Ymd HisO]').$str.EOL);
				break;
				case self::INFO:
					$this->write('[INFO] '.date('[Ymd HisO]').$str.EOL);
				break;
				case self::DEBUG:
					$this->write('[DEBUG]'.date('[Ymd HisO]').$str.EOL);
				break;
				default:
					$this->write('[UNK]  '.date('[Ymd HisO]').$str.EOL);
			}
		}
	}
}