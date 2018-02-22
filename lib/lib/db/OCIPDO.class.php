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
		$errHandler;
	public function __construct($dsn, $username= null, $passwd= null, $options= null){
		// drop the oci:dbname=
		$dsn= substr($dsn, 11);


		$this->db= oci_connect($username, $passwd, $dsn);
		if(!$this->db){
			$err= oci_error();
			throw new PDOException("Connect failed. " . $err['code'] . ':' . $err['message']);
		}
		$this->errHandler= array($this, 'errHandler');
	}
	public function exec($statement){
		// TODO: exec
		return false;
	}
	public function beginTransaction(){
		// TODO: beginTransaction
		return false;
	}
	public function inTransaction(){
		// TODO: inTransaction
		return false;
	}
	public function rollBack(){
		return oci_rollback($this->db);
	}
	public function commit(){
		return oci_commit($this->db);
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
		if(count(func_get_args()) > 1){
			throw new PDOException("Multiple arguements are not currently supported");
		}
		$ret= new OCIPDOStatement($this, $statement);
		return $ret->execute() ? $ret : false;
	}
	public function getAttribute($attribute){
		return null;
	}
	public function setAttribute($attribute, $value){
		return false;
	}
	public function quote($string, $parameterType= null){
		return false;
	}
	public function lastInsertId($name= null){
		throw new PDOException("Not supported", 'IM001');
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
		$this->lastError= $err;
	}
	public function errHandler($number, $string, $file, $line, $context){
		// because oci_* emits freaking warnings and oci_error() insists everything is fine
		$error= explode(':', $string, 3);
		$this->lastError= OCIPDO::makeErrorInfo(array('code'=>trim($error[1]),'message'=>trim($error[2])));
		logit("$string\n$file\n$line\n".print_r($context, true));

	}
	private function listenForErrors(){
		set_error_handler($this->errHandler);
	}
	private function stopListeningForErrors($operationSuccess){
		restore_error_handler();
		if($this->caughtError){
			$this->db->_setLastError($this->lastError);
			$this->caughtError= false;
		}else if(!$operationSuccess){
			if($this->db){
				$this->lastError= OCIPDO::makeErrorInfo(oci_error($this->db));
			}else{
				$this->lastError= OCIPDO::makeErrorInfo(oci_error());
			}
		}
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
			return array('00000','ORA-00000','No error');
		}
		switch($ociError['code']){
			case 'ORA-00000':
				return array('00000',$ociError['code'],$ociError['message']);

			case 'ORA-01095':
			case 'ORA-01403':
				return array('02000',$ociError['code'],$ociError['message']);

			case 'SQL-02126':
				return array('07008',$ociError['code'],$ociError['message']);

			case 'SQL-02121':
				return array('08003',$ociError['code'],$ociError['message']);

			case 'ORA-01427':
			case 'SQL-02112':
				return array('21000',$ociError['code'],$ociError['message']);

			case 'ORA-01401':
			case 'ORA-01406':
				return array('22001',$ociError['code'],$ociError['message']);

			case 'ORA-01405':
			case 'SQL-02124':
				return array('22002',$ociError['code'],$ociError['message']);

			case 'ORA-01426':
			case 'ORA-01438':
			case 'ORA-01455':
			case 'ORA-01457':
				return array('22003',$ociError['code'],$ociError['message']);

			case 'ORA-01476':
				return array('22012',$ociError['code'],$ociError['message']);

			case 'ORA-00911':
			case 'ORA-01425':
				return array('22019',$ociError['code'],$ociError['message']);

			case 'ORA-01025':
			case 'ORA-01488':
				return array('22023',$ociError['code'],$ociError['message']);

			case 'ORA-01424':
				return array('22025',$ociError['code'],$ociError['message']);

			case 'ORA-00001':
				return array('23000',$ociError['code'],$ociError['message']);

			case 'ORA-01410':
			case 'ORA-08006':
			case 'SQL-02114':
			case 'SQL-02117':
			case 'SQL-02118':
			case 'SQL-02122':
				return array('24000',$ociError['code'],$ociError['message']);

			case 'ORA-00022':
			case 'ORA-00251':
			case 'ORA-01031':
				return array('42000',$ociError['code'],$ociError['message']);

			case 'ORA-01402':
				return array('44000',$ociError['code'],$ociError['message']);

			case 'SQL-02128':
				return array('63000',$ociError['code'],$ociError['message']);

			case 'SQL-02100':
				return array('82100',$ociError['code'],$ociError['message']);

			case 'SQL-02101':
				return array('82101',$ociError['code'],$ociError['message']);

			case 'SQL-02102':
				return array('82102',$ociError['code'],$ociError['message']);

			case 'SQL-02103':
				return array('82103',$ociError['code'],$ociError['message']);

			case 'SQL-02104':
				return array('82104',$ociError['code'],$ociError['message']);

			case 'SQL-02105':
				return array('82105',$ociError['code'],$ociError['message']);

			case 'SQL-02106':
				return array('82106',$ociError['code'],$ociError['message']);

			case 'SQL-02107':
				return array('82107',$ociError['code'],$ociError['message']);

			case 'SQL-02108':
				return array('82108',$ociError['code'],$ociError['message']);

			case 'SQL-02109':
				return array('82109',$ociError['code'],$ociError['message']);

			case 'SQL-02110':
				return array('82110',$ociError['code'],$ociError['message']);

			case 'SQL-02111':
				return array('82111',$ociError['code'],$ociError['message']);

			case 'SQL-02113':
				return array('82112',$ociError['code'],$ociError['message']);

			case 'SQL-02115':
				return array('82113',$ociError['code'],$ociError['message']);

			case 'SQL-02116':
				return array('82114',$ociError['code'],$ociError['message']);

			case 'SQL-02119':
				return array('82115',$ociError['code'],$ociError['message']);

			case 'SQL-02120':
				return array('82116',$ociError['code'],$ociError['message']);

			case 'SQL-02122':
				return array('82117',$ociError['code'],$ociError['message']);

			case 'SQL-02123':
				return array('82118',$ociError['code'],$ociError['message']);

			case 'SQL-02125':
				return array('82119',$ociError['code'],$ociError['message']);

			case 'SQL-02129':
				return array('82121',$ociError['code'],$ociError['message']);
				//return array('02000',$ociError['code'],$ociError['message']);

		}
		if(strrpos($ociError['code'], 'ORA-',0) === 0){
			$code= intval(explode('-',$ociError['code'])[0], 10);
			if($code > 2999 && $code < 4000){
				return array('0A000',$ociError['code'],$ociError['message']);
			}
			if($code >= 1800  && $code <= 1899){
				return array('22008',$ociError['code'],$ociError['message']);
			}
			if($code >= 4000 && $code <= 4019){
				return array('22023',$ociError['code'],$ociError['message']);
			}
			if($code >= 1479 && $code <= 1480){
				return array('22024',$ociError['code'],$ociError['message']);
			}
			if($code >= 2290 && $code <= 2299){
				return array('23000',$ociError['code'],$ociError['message']);
			}
			if($code >= 1001 && $code <= 1003){
				return array('24000',$ociError['code'],$ociError['message']);
			}
			if($code >= 2091 && $code <= 02092){
				return array('40000',$ociError['code'],$ociError['message']);
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
				return array('42000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=  370 && $code <=  429 ||
				$code >=  600 && $code <=  899 ||
				$code >= 6430 && $code <= 6449 ||
				$code >= 7200 && $code <= 7999 ||
				$code >= 9700 && $code <= 9999
			){
				return array('60000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=   18 && $code <=   35 ||
				$code >=   50 && $code <=   68 ||
				$code >= 2376 && $code <= 2399 ||
				$code >= 4020 && $code <= 4039
			){
				return array('61000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=  100 && $code <=  120 ||
				$code >=  440 && $code <=  569
			){
				return array('62000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=  150 && $code <=  159 ||
				$code >= 2700 && $code <= 2899 ||
				$code >= 3100 && $code <= 3199 ||
				$code >= 6200 && $code <= 6249
			){
				return array('63000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=  200 && $code <=  369 ||
				$code >= 1100 && $code <= 1250
			){
				return array('64000',$ociError['code'],$ociError['message']);
			}
			if($code >= 6500 && $code <= 6599){
				return array('65000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >= 6000 && $code <= 6149 ||
				$code >= 6250 && $code <= 6429 ||
				$code >= 6600 && $code <= 6999 ||
				$code >= 12100 && $code <= 12299 ||
				$code >= 12500 && $code <= 12599
			){
				return array('66000',$ociError['code'],$ociError['message']);
			}
			if($code >= 00430 && $code <= 00439){
				return array('67000',$ociError['code'],$ociError['message']);
			}
			if(
				$code >=   570 && $code <=   599 ||
				$code >=  7000 && $code <=  7199
			){
				return array('69000',$ociError['code'],$ociError['message']);
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
					return array('72000',$ociError['code'],$ociError['message']);
			}
			if($code >= 10000 && $code <= 10999){
				return array('90000',$ociError['code'],$ociError['message']);
			}
		}
		return array('99999',$ociError['code'],$ociError['message']);
	}
}

class OCIPDOStatement extends PDOStatement{
	private static function pdoToSqlType($pdoType){
		switch($pdoType){
			case PDO::PARAM_BOOL:
			case PDO::PARAM_INT:
				return SQLT_INT;
			case PDO::PARAM_STR:
				return SQLT_CHR;
			default:
				throw new PDOException("Data type not supported");
		}
	}
	private $stm, $db;
	private $binds= array();
	private $fetchStyle= PDO::FETCH_BOTH;
	private $resultSet= null;
	private $errHandler;
	private $caughtError;
	private $lastError;
	public function __construct(OCIPDO $db, $query){
		$this->db= $db;
		$pos= -1;
		preg_replace_callback("/(?<!')\?(?!')/", function($matches) use (&$pos) { $pos++; return ":pdooci_m$pos"; }, $query);
		$this->stm= $query;
		$this->errHandler= array($this, 'errHandler');
	}
	private function listenForErrors(){
		set_error_handler($this->errHandler);
	}
	private function stopListeningForErrors($operationSuccess){
		restore_error_handler();
		if($this->caughtError){
			$this->db->_setLastError($this->lastError);
			$this->caughtError= false;
		}else if(!$operationSuccess){
			if($this->resultSet){
				$this->lastError= OCIPDO::makeErrorInfo(oci_error($this->resultSet));
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
		$error= oci_error($this->resultSet);
		if(!$error){
			$error= $this->db->_errorInfo();
		}
		logit(oci_error($this->resultSet));
// 		logit("$string\n$file\n$line\n".print_r($context, true));

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
		if($resultSet){
			trigger_error("Potential memory leak: execute called while current result set still open", E_WARNING);
		}

		$this->listenForErrors();
		$resultSet= $this->db->_parse($this->stm);

		foreach($this->binds as $param => $val){
			if(!oci_bind_by_name($resultSet, $param, $val)){
				$this->stopListeningForErrors(false);
				return false;
			}
		}
		if($params){
			foreach($params as $param => $val){
				if(is_array($val)){
					$value= $val[0];
					$type= $val[1];
					if(!oci_bind_by_name($resultSet, $param, $value, -1, $type)){
					$this->stopListeningForErrors(false);
					return false;
					}
				}else{
					if(!oci_bind_by_name($resultSet, $param, $val)){
						$this->stopListeningForErrors(false);
						return false;
					}
				}
			}
		}
		$this->resultSet= $resultSet;// for the error handler
		$this->resultSet= oci_execute($resultSet) ? $resultSet : null;
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
				throw new PDOException("Fetch style not supported");
		}
		$this->listenForErrors();
		$row= oci_fetch_array($this->resultSet, $fetchStyle + OCI_RETURN_NULLS);
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
		// TODO: column meta
	}
	public function nextRowset(){
		throw new PDOException("Not supported");
	}
}
