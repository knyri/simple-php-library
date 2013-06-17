<?php
PackageManager::requireClassOnce('io.file');
/**
 * Simple File Logger
 * @author Ken
 *
 */
class Logger extends File{
	const ERR=1,WARN=2,INFO=4,DEBUG=8,ALL=-1;
	private $logLevel=self::ALL;
	public function log($str,$level=0){
		if(($level&$this->logLevel)==$level){
			switch($level){
				case self::ERR:
					$this->write('[ERR]'.date('[Y-m-d H:i:sO]').$str.EOL);
				break;
				case self::WARN:
					$this->write('[WARN]'.date('[Y-m-d H:i:sO]').$str.EOL);
				break;
				case self::INFO:
					$this->write('[INFO]'.date('[Y-m-d H:i:sO]').$str.EOL);
				break;
				case self::DEBUG:
					$this->write('[DEBUG]'.date('[Y-m-d H:i:sO]').$str.EOL);
				break;
				default:
					$this->write('[UNK]'.date('[Y-m-d H:i:sO]').$str.EOL);
			}
		}
	}
}