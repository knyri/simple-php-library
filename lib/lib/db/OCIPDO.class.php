<?php

/*
 * Peices of code copied from http://github.com/taq/pdooci
 * The project works fine, but it doesn't keep the reusability of
 * PDOStatements when you call closeCursor(). This aims to fix that.
 */

class OCIPDO extends PDO{
	private
		$db,
		$lastError= array('00000','ORA-00000','No error'),
		$errHandler,
		$outOfTransaction= true,
		$autocommit= true,
		$columnCase= PDO::CASE_NATURAL,
		$rowPrefetch,
		$oracleNulls= PDO::NULL_NATURAL,
		$errorMode= PDO::ERRMODE_SILENT,
		$fetchMode= PDO::FETCH_BOTH
	;
	public function __construct($dsn, $username= null, $passwd= null, $options= null){
		$this->rowPrefetch= intval(ini_get('oci8.default_prefetch'));
		if(!$this->rowPrefetch){
			$this->rowPrefetch= 100;
		}
		// drop the oci:dbname=
		$dsn= substr($dsn, 11);

		$this->db= oci_new_connect($username, $passwd, $dsn);
		if(!$this->db){
			$err= oci_error();
			throw new PDOException("Connect failed. " . $err['code'] . ':' . $err['message']);
		}
		$this->errHandler= array($this, 'errHandler');
	}
	public function exec($statement){
		$this->listenForErrors();
		$stm= oci_parse($this->db, $statement);
		if(!$stm){
			$this->stopListeningForErrors(false);
			return false;
		}

		$affected= false;
		if(oci_execute($stm, $this->shouldCommit() ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT)){
			$affected= oci_num_rows($stm);
		}
		$this->stopListeningForErrors($affected !== false);
		return $affected;
	}
	public function beginTransaction(){
		if($this->inTransaction()){
			throw new PDOException("Attempted to start a transaction while in a transaction.");
		}
		$this->outOfTransaction= $this->autocommit ? false : $this->commit();
		return !$this->outOfTransaction;
	}
	/**
	 * Whether a statement should commit after running
	 * @return boolean
	 */
	public function shouldCommit(){
		return $this->autocommit && $this->outOfTransaction;
	}
	public function inTransaction(){
		return !$this->outOfTransaction;
	}
	public function rollBack(){
		if($this->shouldCommit()){
			// shouldn't be anything to roll back
			return true;
		}
		$this->listenForErrors();
		$success= oci_rollback($this->db);
		$this->stopListeningForErrors($success);
		if($this->inTransaction()){
			$this->outOfTransaction= $success;
		}
		return $success;
	}
	public function commit(){
		if($this->shouldCommit()){
			// shouldn't be anything to commit
			return true;
		}
		$this->listenForErrors();
		$success= oci_commit($this->db);
		$this->stopListeningForErrors($success);
		if($this->inTransaction()){
			$this->outOfTransaction= $success;
		}
		return $success;
	}
	public function errorCode(){
		return $this->lastError[0];
	}
	public function errorInfo(){
		return $this->lastError;
	}
	public function _errorInfo(){
		return OCIPDO::makeErrorInfo(oci_error($this->db));
	}

	public function prepare($statement, $driver_options= null){
		return new OCIPDOStatement($this, $statement);
	}

	public function query($statement){
		$ret= new OCIPDOStatement($this, $statement);
		if($ret->execute()){
			$argc= func_num_args();
			if($argc > 1){
				if($argc > 2){
					$args= func_get_args();
					call_user_func_array(array($ret,'setFetchMode'), array_slice($args, 1, $argc - 1, false));
				}else{
					$ret->setFetchMode(func_get_arg(1));
				}
		}
			return $ret;
		}
		return false;
	}
	public function getAttribute($attribute){
		switch($attribute){
			case PDO::ATTR_DRIVER_NAME:
				return 'oci';
			case PDO::ATTR_AUTOCOMMIT:
				return $this->autocommit;
			case PDO::ATTR_CASE:
				return $this->columnCase;
			case PDO::ATTR_CLIENT_VERSION:
				return oci_client_version();
			case PDO::ATTR_CONNECTION_STATUS:
				// valid values not defined
				return null;
			case PDO::ATTR_ERRMODE:
				return $this->errorMode;
			case PDO::ATTR_ORACLE_NULLS:
				return $this->oracleNulls;
			case PDO::ATTR_PERSISTENT:
				return false;
			case PDO::ATTR_PREFETCH:
				return $this->rowPrefetch;
			case PDO::ATTR_SERVER_INFO:
				return null;
			case PDO::ATTR_SERVER_VERSION:
				$this->listenForErrors();
				$version= oci_server_version($this->db);
				$this->stopListeningForErrors($version !== false);
				return $version ? $version : null;
			case PDO::ATTR_TIMEOUT:
				return -1;
			case PDO::ATTR_DEFAULT_FETCH_MODE:
				return $this->fetchMode;
			case PDO::ATTR_EMULATE_PREPARES:
				return false;
			default:
				return null;
		}
	}
	public function setAttribute($attribute, $value){
		/* TODO: Support these
		 * case PDO::ATTR_CASE:
		 */
		switch($attribute){
			case PDO::ATTR_PREFETCH:
				if(!is_int($value) || $value < 1){
					return false;
				}
				$this->rowPrefetch= $value;
				break;
			case PDO::ATTR_AUTOCOMMIT:
				$this->autocommit= $value == true;
				break;
			case PDO::ATTR_DEFAULT_FETCH_MODE:
				if($value != null){
				$this->fetchMode= $value;
				}
				break;
			case PDO::ATTR_ERRMODE:
				if($value > -1 && $value < 4){
					$this->errorMode= $value;
					return true;
				}
				return false;
			break;
			default:
				return false;
		}
		return true;
	}
	public function quote($string, $parameterType= null){
		return false;
	}
	public function lastInsertId($name= null){
		self::makeErrorAry('IM001', array('code'=>null, 'message'=>'Not supported'));
		return false;
	}

	/**
	 * Used by statements
	 * @param string $statement
	 * @throws PDOException if the parsing failed
	 */
	public function _parse($statement){
		$stm= oci_parse($this->db, $statement);
		if(!$stm){
			$err= oci_error($this->db);
			throw new PDOException($err['message'], $err['code']);
		}
		return $stm;
	}
	/**
	 * Used by OCIPDOStatement.
	 * @param array $err
	 */
	public function _setLastError($err){
// 		logit($err);
		$this->lastError= $err;
	}
	public function errHandler($number, $string, $file, $line, $context){
		// because oci_* emits freaking warnings and oci_error() insists everything is fine
		$error= explode(':', $string, 3);
		$this->caughtError= true;
		$this->lastError= OCIPDO::makeErrorInfo(array('code'=>trim($error[1]),'message'=>trim($error[2])));
		if(function_exists('logit')){
			logit("$string\n$file\n$line\n".print_r($context, true));
		}
		switch($this->getAttribute(PDO::ATTR_ERRMODE)){
			case PDO::ERRMODE_EXCEPTION:
				$exception= new PDOException('OCI error');
				$exception->errorInfo= $this->lastError;
				$this->stopListeningForErrors(false);
				throw $exception;
			case PDO::ERRMODE_WARNING:
				$this->originalErrHandler($number, $string, $file, $this->lastError, $context);
		}

	}
	private function listenForErrors(){
		$this->caughtError= false;
		$this->originalErrHandler= set_error_handler($this->errHandler);
	}
	private function stopListeningForErrors($operationSuccess){
		restore_error_handler();
		if($this->caughtError){
			$this->_setLastError($this->lastError);
			$this->caughtError= false;
		}else if(!$operationSuccess){
			if($this->resultSet){
				$this->lastError= OCIPDO::makeErrorInfo(oci_error($this->resultSet));
				$this->lastError['query']= $this->stm;
				$this->lastError['params']= $this->binds;
				$this->_setLastError($this->lastError);

			}else{
				$this->lastError= $this->_errorInfo();
			}
		}
	}

	private static function makeErrorAry($code, $ociError){
		return array($code,$ociError['code'],$ociError['message']);
	}
	public function getNewLobDescriptor(){
		return oci_new_descriptor($this->db, OCI_DTYPE_LOB);
	}
	public function getNewFileDescriptor(){
		return oci_new_descriptor($this->db, OCI_DTYPE_FILE);
	}
	public function getNewRowIdDescriptor(){
		return oci_new_descriptor($this->db, OCI_DTYPE_ROWID);
	}

	/**
	 * Takes the array from oci_error() and makes a PDO
	 * style exception array.
	 * Code conversion is from https://docs.oracle.com/cd/E16338_01/appdev.112/e10827/appd.htm
	 * and is not garaunteed to be up to date.
	 * @param array $ociError
	 * @return string[]
	 */
	public static function makeErrorInfo($ociError){
		if(!$ociError){
			return array('00000', 'ORA-00000', 'No error');
		}
		switch($ociError['code']){
			case 'ORA-00000':
				return self::makeErrorAry('00000', $ociError);

			case 'ORA-01095':
			case 'ORA-01403':
				return self::makeErrorAry('02000', $ociError);

			case 'SQL-02126':
				return self::makeErrorAry('07008', $ociError);

			case 'SQL-02121':
				return self::makeErrorAry('08003', $ociError);

			case 'ORA-01427':
			case 'SQL-02112':
				return self::makeErrorAry('21000', $ociError);

			case 'ORA-01401':
			case 'ORA-01406':
				return self::makeErrorAry('22001', $ociError);

			case 'ORA-01405':
			case 'SQL-02124':
				return self::makeErrorAry('22002', $ociError);

			case 'ORA-01426':
			case 'ORA-01438':
			case 'ORA-01455':
			case 'ORA-01457':
				return self::makeErrorAry('22003', $ociError);

			case 'ORA-01476':
				return self::makeErrorAry('22012', $ociError);

			case 'ORA-00911':
			case 'ORA-01425':
				return self::makeErrorAry('22019', $ociError);

			case 'ORA-01025':
			case 'ORA-01488':
				return self::makeErrorAry('22023', $ociError);

			case 'ORA-01424':
				return self::makeErrorAry('22025', $ociError);

			case 'ORA-00001':
				return self::makeErrorAry('23000', $ociError);

			case 'ORA-01410':
			case 'ORA-08006':
			case 'SQL-02114':
			case 'SQL-02117':
			case 'SQL-02118':
			case 'SQL-02122':
				return self::makeErrorAry('24000', $ociError);

			case 'ORA-00022':
			case 'ORA-00251':
			case 'ORA-01031':
				return self::makeErrorAry('42000', $ociError);

			case 'ORA-01402':
				return self::makeErrorAry('44000', $ociError);

			case 'SQL-02128':
				return self::makeErrorAry('63000', $ociError);

			case 'SQL-02100':
				return self::makeErrorAry('82100', $ociError);

			case 'SQL-02101':
				return self::makeErrorAry('82101', $ociError);

			case 'SQL-02102':
				return self::makeErrorAry('82102', $ociError);

			case 'SQL-02103':
				return self::makeErrorAry('82103', $ociError);

			case 'SQL-02104':
				return self::makeErrorAry('82104', $ociError);

			case 'SQL-02105':
				return self::makeErrorAry('82105', $ociError);

			case 'SQL-02106':
				return self::makeErrorAry('82106', $ociError);

			case 'SQL-02107':
				return self::makeErrorAry('82107', $ociError);

			case 'SQL-02108':
				return self::makeErrorAry('82108', $ociError);

			case 'SQL-02109':
				return self::makeErrorAry('82109', $ociError);

			case 'SQL-02110':
				return self::makeErrorAry('82110', $ociError);

			case 'SQL-02111':
				return self::makeErrorAry('82111', $ociError);

			case 'SQL-02113':
				return self::makeErrorAry('82112', $ociError);

			case 'SQL-02115':
				return self::makeErrorAry('82113', $ociError);

			case 'SQL-02116':
				return self::makeErrorAry('82114', $ociError);

			case 'SQL-02119':
				return self::makeErrorAry('82115', $ociError);

			case 'SQL-02120':
				return self::makeErrorAry('82116', $ociError);

			case 'SQL-02122':
				return self::makeErrorAry('82117', $ociError);

			case 'SQL-02123':
				return self::makeErrorAry('82118', $ociError);

			case 'SQL-02125':
				return self::makeErrorAry('82119', $ociError);

			case 'SQL-02129':
				return self::makeErrorAry('82121', $ociError);
				//return self::makeErrorAry('02000', $ociError);

		}
		if(strrpos($ociError['code'], 'ORA-', 0) === 0){
			$code= intval(explode('-', $ociError['code'])[1], 10);
			if($code > 2999 && $code < 4000){
				return self::makeErrorAry('0A000', $ociError);
			}
			if($code >= 1800  && $code <= 1899){
				return self::makeErrorAry('22008', $ociError);
			}
			if($code >= 4000 && $code <= 4019){
				return self::makeErrorAry('22023', $ociError);
			}
			if($code >= 1479 && $code <= 1480){
				return self::makeErrorAry('22024', $ociError);
			}
			if($code >= 2290 && $code <= 2299){
				return self::makeErrorAry('23000', $ociError);
			}
			if($code >= 1001 && $code <= 1003){
				return self::makeErrorAry('24000', $ociError);
			}
			if($code >= 2091 && $code <= 2092){
				return self::makeErrorAry('40000', $ociError);
			}
			if(
				$code >= 1490 && $code <= 1493 ||
				$code >= 1700 && $code <= 1799 ||
				$code >= 1900 && $code <= 2099 ||
				$code >= 2140 && $code <= 2289 ||
				$code >= 2420 && $code <= 2424 ||
				$code >= 2450 && $code <= 2499 ||
				$code >= 3276 && $code <= 3299 ||
				$code >= 4040 && $code <= 4059 ||
				$code >= 4070 && $code <= 4099
			){
				return self::makeErrorAry('42000', $ociError);
			}
			if(
				$code >=  370 && $code <=  429 ||
				$code >=  600 && $code <=  899 ||
				$code >= 6430 && $code <= 6449 ||
				$code >= 7200 && $code <= 7999 ||
				$code >= 9700 && $code <= 9999
			){
				return self::makeErrorAry('60000', $ociError);
			}
			if(
				$code >=   18 && $code <=   35 ||
				$code >=   50 && $code <=   68 ||
				$code >= 2376 && $code <= 2399 ||
				$code >= 4020 && $code <= 4039
			){
				return self::makeErrorAry('61000', $ociError);
			}
			if(
				$code >=  100 && $code <=  120 ||
				$code >=  440 && $code <=  569
			){
				return self::makeErrorAry('62000', $ociError);
			}
			if(
				$code >=  150 && $code <=  159 ||
				$code >= 2700 && $code <= 2899 ||
				$code >= 3100 && $code <= 3199 ||
				$code >= 6200 && $code <= 6249
			){
				return self::makeErrorAry('63000', $ociError);
			}
			if(
				$code >=  200 && $code <=  369 ||
				$code >= 1100 && $code <= 1250
			){
				return self::makeErrorAry('64000', $ociError);
			}
			if($code >= 6500 && $code <= 6599){
				return self::makeErrorAry('65000', $ociError);
			}
			if(
				$code >= 6000 && $code <= 6149 ||
				$code >= 6250 && $code <= 6429 ||
				$code >= 6600 && $code <= 6999 ||
				$code >= 12100 && $code <= 12299 ||
				$code >= 12500 && $code <= 12599
			){
						return self::makeErrorAry('66000', $ociError);
			}
			if($code >= 430 && $code <= 439){
				return self::makeErrorAry('67000', $ociError);
			}
			if(
				$code >=   570 && $code <=   599 ||
				$code >=  7000 && $code <=  7199
				){
				return self::makeErrorAry('69000', $ociError);
			}
			if(
				$code >=  1000 && $code <=  1099 ||
				$code >=  1400 && $code <=  1489 ||
				$code >=  1495 && $code <=  1499 ||
				$code >=  1500 && $code <=  1699 ||
				$code >=  2400 && $code <=  2419 ||
				$code >=  2425 && $code <=  2449 ||
				$code >=  4060 && $code <=  4069 ||
				$code >=  8000 && $code <=  8190 ||
				$code >= 12000 && $code <= 12019 ||
				$code >= 12300 && $code <= 12499 ||
				$code >= 12700 && $code <= 21999
			){
				return self::makeErrorAry('72000', $ociError);
			}
			if($code >= 10000 && $code <= 10999){
				return self::makeErrorAry('90000', $ociError);
			}
		}
		return self::makeErrorAry('99999', $ociError);
	}
}

class OCIPDOStatement extends PDOStatement{
	private static function pdoToSqlType($pdoType){
		switch($pdoType){//5,1,2
			case PDO::PARAM_BOOL:
			case PDO::PARAM_INT:
				return SQLT_INT;
			case PDO::PARAM_STR:
				return SQLT_CHR;
			default:
				return $pdoType;
// 				throw new PDOException("Data type not supported");
		}
	}
	private $stm, $db;
	private $binds= array();
	private $fetchStyle;
	private $resultSet= null;
	private $errHandler;
	private $originalErrHandler;
	private $caughtError;
	private $lastError;
	public function __construct(OCIPDO $db, $query){
		$this->db= $db;
		$pos= -1;
		preg_replace_callback("/(?<!')\?(?!')/", function($matches) use (&$pos) { $pos++; return ":pdooci_m$pos"; }, $query);
		$this->stm= $query;
		$this->errHandler= array($this, 'errHandler');
		$this->fetchStyle= $db->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
	}
	private function listenForErrors(){
		$this->caughtError= false;
		$this->originalErrHandler= set_error_handler($this->errHandler);

	}
	private function stopListeningForErrors($operationSuccess, $message= null){
		restore_error_handler();
		if($this->caughtError){
			$this->db->_setLastError($this->lastError);
			$this->caughtError= false;
		}else if(!$operationSuccess){
			if($this->resultSet){
				$this->lastError= OCIPDO::makeErrorInfo(oci_error($this->resultSet));
				$this->lastError['query']= $this->stm;
				$this->lastError['params']= $this->binds;
				if($message){
					$this->lastError['extraDetail']= $message;
				}
				$this->db->_setLastError($this->lastError);

			}else{
				$this->lastError= $this->db->_errorInfo();
			}
		}
	}
	public function errHandler($number, $string, $file, $line, $context){
		// because oci_* emits freaking warnings and oci_error() insists everything is fine
		$error= explode(':', $string, 3);
		$this->caughtError= true;
		$this->lastError= OCIPDO::makeErrorInfo(array('code'=>trim($error[1]),'message'=>trim($error[2])));

		switch($this->db->getAttribute(PDO::ATTR_ERRMODE)){
			case PDO::ERRMODE_EXCEPTION:
				$exception= new PDOException('OCI error');
				$exception->errorInfo= $this->lastError;
				$this->stopListeningForErrors(false);
				throw $exception;
			case PDO::ERRMODE_WARNING:
				$this->originalErrHandler($number, $string, $file, $this->lastError, $context);
		}

	}
	public function setFetchMode($mode, $params= null){
		switch($mode){
			case PDO::FETCH_ASSOC:
			case PDO::FETCH_BOTH:
			case PDO::FETCH_NUM:
				$this->fetchStyle= $mode;
			break;
			default:
				throw new PDOException("Fetch mode not supported");
		}
	}
	public function closeCursor(){
		$ret= true;
		if($this->resultSet){
			$this->listenForErrors();
			$ret= oci_free_statement($this->resultSet);
			if($ret){
				$this->resultSet= null;
			}
			$this->stopListeningForErrors($ret);
		}
		return $ret;
	}
	public function bindColumn($column, &$param, $type= PDO::PARAM_STR, $maxlen= null, $driverdata= null){
		throw new PDOBindException('Not supported');
	}

	public function bindParamOci($parameter, &$variable, $dataType= SQLT_CHR){
		if(is_int($parameter)){
			$parameter= ":pdooci_m$parameter";
		}
		$this->binds[$parameter]= array(&$variable, $dataType);
	}
	public function bindValueOci($parameter, $value, $dataType= SQLT_CHR){
		if(is_int($parameter)){
			$parameter= ":pdooci_m$parameter";
		}
		$this->binds[$parameter]= array($value, $dataType);
	}

	public function bindParam($parameter, &$variable, $dataType= PDO::PARAM_STR, $len= null, $diver_option= null){
		if(is_int($parameter)){
			$parameter= ":pdooci_m$parameter";
		}
		$this->binds[$parameter]= array(&$variable, OCIPDOStatement::pdoToSqlType($dataType));
	}
	public function bindValue($parameter, $value, $dataType= PDO::PARAM_STR){
		if(is_int($parameter)){
			$parameter= ":pdooci_m$parameter";
		}
		$this->binds[$parameter]= array($value, OCIPDOStatement::pdoToSqlType($dataType));
	}
	public function execute($params= null){
		$lobs= array();
// 		if($this->resultSet){
// 			trigger_error("Potential memory leak: execute called while current result set still open", E_USER_WARNING);
// 		}

		$this->listenForErrors();
		$this->resultSet= $resultSet= $this->db->_parse($this->stm);
		oci_set_prefetch($resultSet, $this->db->getAttribute(PDO::ATTR_PREFETCH));
// 		logit($this->stm);
		if($params){
			foreach($params as $param=>$val){
				if(!is_array($val)){
					$params[$param]= array($val, false);
				}
			}
			$params= array_merge($this->binds, $params);
		}else{
			$params= $this->binds;
		}
// 		logit($params);

		foreach($params as $param => $val){
			$type= $val[1];
			if($type == OCI_D_LOB || $type == OCI_DTYPE_LOB){
				$type= OCI_DTYPE_LOB;
// 				logit('NEW LOB!' . OCI_D_LOB . ' ' . OCI_DTYPE_LOB);
				$lob= $this->db->getNewLobDescriptor();
				if(!$lob){
// 					logit('LOB creation failed!');
// 					logit($this->db->_errorInfo());
					$this->stopListeningForErrors(false, 'Error making LOB descriptor');
					return false;
				}
				$lobs[]= array($lob, $val[0]);
				// maybe need to unset the param?
// 				$value= $lob;
			}
			if(!
				($type === false ? oci_bind_by_name($resultSet, $param, $val[0]) : oci_bind_by_name($resultSet, $param, $val[0], -1, $type))
			){
// 				logit('bind by name failed!');
// 				logit($this->db->_errorInfo());
				$this->stopListeningForErrors(false, "Bind by name failed for $param");
				return false;
			}
		}
		if(count($lobs)){
// 			logit('lobs: ' . count($lobs));
			foreach($lobs as $lob){
				if(!$lob[0]->save($lob[1])){
// 					logit('LOB write error');
					$this->stopListeningForErrors(false);
					return false;
				}
			}
			$resultSet= oci_execute($resultSet, OCI_NO_AUTO_COMMIT) ? $resultSet : null;
			if($resultSet){
				oci_commit($this->db);
			}else{
				oci_rollback($this->db);
			}
			foreach($lobs as $lob){
				$lob[0]->free();
			}
			$this->resultSet= $this->caughtError ? null : $resultSet;
		}else{
			$this->resultSet= oci_execute($resultSet, $this->db->shouldCommit() ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT) ? $resultSet : null;
		}
		$this->stopListeningForErrors($this->resultSet);
		return $this->resultSet != null;
	}
	public function fetch($fetchStyle= null, $cursorOrientation= PDO::FETCH_ORI_NEXT, $cursorOffset= 0){
		if(!$this->resultSet){
			return false;
		}
		if($cursorOrientation != PDO::FETCH_ORI_NEXT){
			throw new PDOException("Cursor orientation not supported");
		}
		if($fetchStyle == null){
			$fetchStyle= $this->fetchStyle;
		}
		switch($fetchStyle){
			case PDO::FETCH_ASSOC:
				$fetchStyle= OCI_ASSOC;
			break;
			case PDO::FETCH_BOTH:
				$fetchStyle= OCI_BOTH;
			break;
			case PDO::FETCH_NUM:
				$fetchStyle= OCI_NUM;
			break;
			default:
				throw new PDOException("Fetch style not supported: $fetchStyle");
		}
		$this->listenForErrors();
		$row= oci_fetch_array($this->resultSet, $fetchStyle + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
		$this->stopListeningForErrors(true);
		return $row;
	}
	public function fetchAll($fetchStyle= null, $fetchArgument= null, $constructorArgs= null){
		if(!$this->resultSet){
			return false;
		}
		if($fetchStyle == null){
			$fetchStyle= $this->fetchStyle;
		}
		switch($fetchStyle){
			case PDO::FETCH_ASSOC:
				$fetchStyle= OCI_ASSOC;
				break;
			case PDO::FETCH_NUM:
				$fetchStyle= OCI_NUM;
				break;
			default:
				throw new PDOException("Fetch style not supported");
		}
		$rows= array();

		$this->listenForErrors();
		$res= oci_fetch_all($this->resultSet, $rows, 0, -1, $fetchStyle + OCI_FETCHSTATEMENT_BY_ROW);
		$this->stopListeningForErrors($res !== false);
		return $res === false ? $res : $rows;
	}
	public function fetchColumn($columnNumber= null){
		throw new PDOException("Method not supported");
	}
	public function fetchObject($className= null, $constructorArgs= null){
		throw new PDOException("Medthod not supported");
	}
	public function columnCount(){
		$this->listenForErrors();
		$res= $this->resultSet ? oci_num_fields($this->resultSet) : 0;
		$this->stopListeningForErrors($res !== false);
		return $res;
	}
	public function rowCount(){
		$this->listenForErrors();
		$res= $this->resultSet ? oci_num_rows($this->resultSet) : 0;
		$this->stopListeningForErrors($res !== false);
		return $res;
	}
	public function errorCode(){
		return $this->lastError ? $this->lastError[0] : '00000';
	}
	public function errorInfo(){
		return $this->lastError ? $this->lastError : OCIPDO::makeErrorInfo(false);
	}
	public function debugDumpParams(){
		// TODO: what to dump?
		echo 'debugDumpParams() not implemented. Sorry.' . PHP_EOL;
	}
	public function getAttribute($attribute){
		return null;
	}
	public function setAttribute($attribute, $value){
		return false;
	}
	public function getColumnMeta($column){
		$column= $column + 1;
		$attrs= array();
		$this->listenForErrors();
		$attr= oci_field_name($this->resultSet, $column);
		if($attr === false){
			$this->stopListeningForErrors(false);
			throw new PDOException('Error fecting column name. See errorInfo() for more details.');
		}
		$attrs['name']= $attr;

		$attr= oci_field_precision($this->resultSet, $column);
		if($attr === false){
			$this->stopListeningForErrors(false);
			throw new PDOException('Error fecting column precision. See errorInfo() for more details.');
		}
		$attrs['precision']= $attr;

		$attr= oci_field_size($this->resultSet, $column);
		if($attr === false){
			$this->stopListeningForErrors(false);
			throw new PDOException('Error fecting column length. See errorInfo() for more details.');
		}
		$attrs['length']= $attr;

		$attr= oci_field_type($this->resultSet, $column);
		if($attr === false){
			$this->stopListeningForErrors(false);
			throw new PDOException('Error fecting column type. See errorInfo() for more details.');
		}
		$attrs['driver:decl_type']= $attr;

		$attrs['native_type']= OCIPDOStatement::getNativeType($attr);
		// TODO: pdo_type, table, flags
		// table and flags are probably impossible


		$this->stopListeningForErrors(true);
		return $attrs;
	}
	public function nextRowset(){
		throw new PDOException("Not supported");
	}
	private static function getNativeType($typeName){
		// as of 12c
		// https://docs.oracle.com/database/121/SQLRF/sql_elements001.htm#SQLRF30020
		switch($typeName){
			case 'DECIMAL':
			case 'NUMBER':
			case 'FLOAT':
			case 'BINARY_FLOAT':
			case 'BINARY_DOUBLE':
			case 'NUMERIC':
			case 'DOUBLE PRECISION':
			case 'REAL':
				return 'float';
			case 'INTEGER':
			case 'INT':
			case 'SMALLINT':
				return 'integer';
			// pretty sure these will be string
// 			case 'RAW':
// 			case 'LONG RAW':
			default:
				return 'string';
		}
	}
}
