<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

PackageManager::requireClassOnce('util.propertylist');

/**
 *
 * Basic database profiling class.
 * @author Ken
 *
 */
class DBProfile{
	private static $queries=array('insert'=>0,'select'=>0,'delete'=>0,'update'=>0,'run'=>0);
	public static function query($type){
		self::$queries[$type]++;
	}
	public static function get($type){
		return self::$queries[$type];
	}
	public static function getTotal(){
		$sum=0;
		foreach(self::$queries as $v)
			$sum+=$v;
		return $sum;
	}
}
PackageManager::requireClassOnce('ml.html');
PackageManager::requireClassOnce('error.IllegalArgumentException');
PackageManager::requireClassOnce('error.IllegalStateException');
//require_once LIB.'lib/ml/class_html.inc.php';
//require_once LIB.'lib/error/class_IllegalArgumentException.php';
global $_DB, $_DB_OPEN_CON;
$_DB = null;
$_DB_OPEN_CON = false;
/**
 * Sets/returns the state of the debugging status for the db_* functions
 * @param boolean $toggle Optional.
 * @return boolean If the db_* functions provide debugging info.
 */
function db_debug($toggle=null){
	$conf=LibConfig::getConfig('db');
	if($toggle!==null)
		$conf['debug']=$toggle;
	if($conf['debug']==true)
			return true;
	return false;
}
/**
 * creates a connection to the database if none exists
 * or returns one already created.
 * @param boolean $forcenew Forces the creation of a new connection.
 * @return Ambigous <NULL, resource> Returns a PDO object on success, null on failure. Throws a PDOException if database debug is on.
 */
function &db_get_connection($forcenew = false) {
	global $_DB, $_DB_OPEN_CON;
	if ($forcenew){db_close_connection();}
	if (!$_DB_OPEN_CON || $_DB == null) {
		$conf = LibConfig::getConfig('db');
		try{
		$_DB = new PDO($conf['engine'].':host='.$conf['host'].';dbname='.$conf['dbname'],$conf['user'],$conf['password']);
		$_DB_OPEN_CON=true;
		//$_DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		}catch(PDOException $e){
			if(db_debug())
				throw $e;
			$_DB=null;
			$_DB_OPEN_CON=false;
		}
	}
	return $_DB;
}
/**
 * Closes the connection to the database.
 */
function db_close_connection() {
	global $_DB, $_DB_OPEN_CON;
	$_DB=null;
	$_DB_OPEN_CON = false;
}
/** Logs a database error.
 * Common usage:
 * if (!$res)
 *		echo db_log_error(mysql_error(), $query);
 * @param string $msg Error message
 * @param string $qry The query that caused it. Optional.
 * @return string $msg
 */
function db_log_error($statement,$args=null) {
	static $stm=null;
	if($stm==null){
		$db = db_get_connection();
		if(!$db)throw new IllegalStateException('Failed to get a database object.');
		$stm=$db->prepare('INSERT INTO errors (err_date, err_msg, err_query) VALUES (NOW(),:message,:query)');
		if(!$stm)throw new IllegalStateException('Failed to prepare the error statement.');
	}
	if(!is_object($statement))throw new IllegalArgumentException('$statement is not an object.');
	$err=$statement->errorInfo();
	$params=array(
		':query'=>db_stm_to_string($statement->queryString,$args),
		':message'=>'Err array:'.var_export($err,true)
	);
	try{
		$stm->execute($params);
	}catch(PDOException $e){
		if(db_debug())throw $e;
	}
	return $err;
}
/** Checks to see if a record exists that matches the conditions.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table required
 * @param string $condition required
 * @return mixed boolean on success or a string containing the error message.
 */
function db_record_exist($db, $table, array $condition) {
	if ($db===null)
		$db = db_get_connection();
	if ($condition) {
		$ret = db_num_rows($db,$table,$condition);
		if(is_numeric($ret))return $ret>0;
		return false;
	} else {
		throw new IllegalArgumentException('$condition MUST be set.');
	}
}
/** Fetches a single column value from the database.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $column
 * @param array $condition see _db_build_where
 * @param mixed $default Default value if the query failed or no value was found.
 * @return mixed The found value or $default
 */
function db_get_column($db, $table, $column, array $condition = null, $default = null) {
	if ($db===null)
		$db = db_get_connection();
	$row=db_get_row($db,$table,$condition,$column,PDO::FETCH_NUM);
	if(!is_array($row)||count($row)===0)return $default;
	return $row[0];
}
/**
 * Alias of db_get_column(..)
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $column
 * @param string $condition
 * @param mixed $default
 * @return mixed The found value or $default
 */
function db_get_field($db, $table, $column, array $condition = null, $default = null) {
	return db_get_column($db, $table, $column, $condition, $default);
}
/**
 * Shortcut for db_get_row($db,$table,$condition,$columns,$cache,MYSQL_ASSOC);
 * @param resource $db
 * @param string $table
 * @param string $condition
 * @param string $columns
 * @param boolean $cache
 * @return array the resulting array or null.
 */
function db_get_row_assoc($db, $table, $condition = null, $columns = '*') {
	return db_get_row($db, $table, $condition, $columns, PDO::FETCH_ASSOC);
}
/** Fetches a single row from the database.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table
 * @param string $condition
 * @param string $columns
 * @param int $type one (or more with && or +) of PDO::FETCH_*
 * @return array the resulting array or null.
 */
function db_get_row($db, $table,array $conditions=null, $columns='*', $type=PDO::FETCH_BOTH) {
	DBProfile::query('select');
	if ($db===null)
		$db = db_get_connection();

	if($conditions===null){
		$stm = "SELECT :columns FROM :table LIMIT 0,1";
		$conditions=array(null,null);
	}else{
		$conditions=_db_build_where($conditions);
		$stm = "SELECT :columns FROM :table ".$conditions[0].' LIMIT 0,1';
	}
	$conditions[1][':table']=$table;
	$conditions[1][':columns']=$columns;
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	if (!db_run_query($stm,$conditions[1])) {
		return db_log_error($stm,$conditions[1]);
	}else{
		$row=$stm->fetch($type);
		$stm->closeCursor();
		return $row;
	}
}

/**
 * Prints a table displaying the result.
 * @param PDOStatement $result
 */
function result_table($result) {
	echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse" id="table1">';
		$rowc = 0;
		$color = "";
		$index = 0;
		$row=$result->fetch(PDO::FETCH_ASSOC);
		echo '<tr>';
		foreach(array_keys($row) as $column)
			echo '<th>'.$column.'</th>';
		echo '</tr><tr>';
		foreach($row as $col) {
				echo "<td bgcolor='#FFFFFF'>".nl2br(htmlspecialchars($col))."</td>";
			$index++;
		}
		echo '</tr>';
		$rowc++;
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$index = 0;
			if ($rowc%2 == 0)
				$color = "#FFFFFF";
			else
				$color = "#DDDDDD";
			echo "<tr>";
			foreach($row as $col) {
					echo "<td bgcolor='$color'>".nl2br(htmlspecialchars($col))."</td>";
				$index++;
			}
			$rowc++;
			echo "</tr>";
		}
		echo '</table>';
}

/**
 * Returns the proper form of a variable for the query.
 * @param mixed $var
 * @return string|unknown|Ambigous <unknown, number>
 */
function _db_validate_value($var) {
	if (is_null($var)){
			return 'NULL';
		}elseif (is_string($var)){
			if ($var == "NOW()")return $var;
			return "'" . clean_text($var) . "'";
		}else{
			return (is_bool($var)) ? intval($var) : $var;
		}
}
/** Builds the WHERE clause.
 * @param array $where Array of conditions to be met. Each element must be array(column, value[, 'AND'|'OR'|negate[, negate]]) (negate is optional).
 *		The last element must have the 3rd argument ommited or set to NULL.
 *		Special elements:
 *		+array(column, 'IN', list, negate[, 'AND'|'OR'])
 *			list must NOT be an array nor enclosed in ().
 *			negate must be true or false and indicates 'NOT IN' when true.
 *		+array(column, 'BETWEEN', lower, upper, negate[, 'AND'|'OR'])
 *		+array(column, 'LIKE', string value, negate[, 'AND'|'OR'])
 *		+array('LITERAL', literal[, 'AND'|'OR'])
 * @return array the resulting where string and array of values for a PDOStatement
 */
function _db_build_where(array $where) {
	if($where==null || count($where)==0)return '';
	$ret=array('',array());
	$wcount=0;
	$where_2 = array();
	foreach($where as $arg){
		if(count($arg) > 3){
			if($arg[1] == 'IN'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$where_2[]= "$arg[0]" . ($arg[3]?' NOT IN (':' IN (') . ':where'.($wcount) . ')' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount++;
			}elseif($arg[1] == 'LIKE'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$where_2[] = "$arg[0]" . ($arg[3]?' NOT LIKE ':' LIKE ') .':where'.($wcount) . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount++;
			}elseif($arg[1] == 'BETWEEN'){
				$ret[1][':where'.($wcount)]=$arg[2];
				$ret[1][':where'.($wcount+1)]=$arg[3];
				$where_2[]= "$arg[0]" . ($arg[4]?' NOT BETWEEN ':' BETWEEN ') . ':where'.($wcount) . ' AND ' . ':where'.($wcount+1) . ((count($arg)==6)?' '.$arg[5].' ' : '');
				$wcount+=2;
			}elseif($arg[1]===null){
				if($arg[3])
					$where_2[] ="$arg[0] IS NOT NULL $arg[2] ";
				else
					$where_2[] ="$arg[0] IS NULL $arg[2] ";
			}else{//What case is this?
				$ret[1][':where'.$wcount]=$arg[1];
				if($arg[4])
					$where_2[] ="$arg[0]=:where$wcount $arg[2] ";
				else
					$where_2[] ="$arg[0]!=:where$wcount $arg[2] ";
				$wcount++;
			}
		}else{
			if(count($arg) == 3){
				if ($arg[0]=='LITERAL'){
					$where_2[] = $arg[1] . ' '.$arg[2].' ';
				}elseif($arg[1]===null){
					if(is_bool($arg[2])){
						if($arg[2])
							$where_2[] ="$arg[0] IS NOT NULL";
						else
							$where_2[] ="$arg[0] IS NULL";
					}else
						$where_2[] ="$arg[0] IS NULL $arg[2] ";
				}else{
					$ret[1][':where'.$wcount]=$arg[1];
					$where_2[] ="$arg[0]=:where$wcount $arg[2] ";
					$wcount++;
				}
			}else{
				if($arg[0]=='LITERAL'){
					$where_2[] = $arg[1];
				}elseif($arg[1]===null){
					$where_2[] ="$arg[0] IS NULL ";
				}else{
					$ret[1][':where'.($wcount)]=$arg[1];
					$where_2[] = "$arg[0]=:where$wcount";
					$wcount++;
				}
			}
		}
	}
	$ret[0]=' WHERE '.implode('', $where_2);
	return $ret;
}
/** Queries the database and returns the result set or NULL if it failed.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array|string $columns Array or comma delimited string of column names
 * @param array $where See _db_build_where(..).
 * @param array $sort array(array(column1, dir)[, array(column2, dir)[, ...]]) where dir=['ASC'|'DESC']
 * @param string $groupBy Column to group by.
 * @param string $having See mysql documentation on the HAVING clause.
 * @param int $limit Max number of rows to return
 * @param int $offset Row to start at
 * @return mixed resource or false on error.
 */
function db_query($db, $table, array $columns = null,array $where = null,array $sortBy = null, $groupBy = null, $having = null,$limit=0,$offset=0){
	DBProfile::query('select');
	if ($db===null)
		$db = db_get_connection();
	if($where!==null){
		$where=_db_build_where($where);
	}
	$query = 'SELECT ';
	if ($columns!==null){
		$query.=implode(',',$columns);
	}else
		$query .= '*';
	if (!empty($table)){
		$query .= ' FROM '.$table;
	}
	if ($where){
		$query .= ' '.$where[0];
		$where=$where[1];
	}
	if ($groupBy != null) {
		$query .= ' GROUP BY '.$groupBy;
		if ($having != null){
			$query .=  'HAVING '.$having;
		}
	}
	if ($sortBy != null) {
		$query .= ' ORDER BY';
		foreach($sortBy as $sort) {
			$query .= " $sort[0] $sort[1],";
		}
		$query = substr($query, 0, -1);
	}
	if($limit>0){
		$query.=" LIMIT $limit";
		if($offset>0){
			$query.=" OFFSET $offset";
		}
	}
	$stm=db_prepare($db,$query);
	if($where)
		$error=db_run_query($stm,$where);
	else
		$error=db_run_query($stm);
	if($error)return $error;
	return $stm;
}

/** Inserts data into the database. Returns the error message on failure or false on success.
 * TODO: Fix to use PDO
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $columns Array of column names.
 * @param array $data Multi-deminsional array of the values. $data = array(array(row data), array(row data), ...).
 * @deprecated
 */
function db_multi_insert($db, $table, array $columns, array $data) {
	DBProfile::query('insert');
	if ($db===null)
		$db=db_get_connection();
	$query = "INSERT INTO $table (".implode(',',$columns).') VALUES ';
	$values = array();
	foreach ($data as $datum)
		$values[]= '('.implode(', ',array_map('_db_validate_value',$datum)).')';
	$query.= implode(',', $values);
	unset($values);
	$res = mysql_query($query,$db);
	if(!$res){
		return db_log_error(mysql_error(),$query);
	}else{
		return false;
	}
}
/** Deletes data from the database. Returns the error message on failure or false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $conditions See _db_build_where(..).
 * @return mixed false on success or the error
 */
function db_delete($db, $table, array $conditions = null) {
	DBProfile::query('delete');
	if ($db===null)
		$db= db_get_connection();
	$stm= "DELETE FROM `$table`";
	if ($conditions!==null){
		$conditions=_db_build_where($conditions);
		$stm.=' '.$conditions[0];
		$conditions=$conditions[1];
	}
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	return db_run_query($stm,$conditions);
}
/**
 * Enter description here ...
 * @param PDO $db
 * @param string $table
 * @param array $conditions see _db_build_where(...)
 * @return mixed false on error or the count.
 */
function db_num_rows($db,$table,array $conditions=null){
	DBProfile::query('select');
	if ($db===null)
		$db= db_get_connection();
	if($conditions===null){
		$stm = "SELECT COUNT(*) FROM $table";
		$conditions=array();
	}else{
		$conditions=_db_build_where($conditions);
		$stm = "SELECT COUNT(*) FROM $table ".$conditions[0];
		$conditions=$conditions[1];
	}
	try{
		$stm = db_prepare($db,$stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	if(db_run_query($stm,$conditions))
		return false;
	$ret = $stm->fetch(PDO::FETCH_NUM);
	$stm->closeCursor();
	return $ret[0];
}
/**
 * @param unknown $db
 * @param unknown $table
 * @param array $conditions
 * @throws PDOException
 * @return boolean|string false on error. '0' or '1'
 */
function db_exists($db,$table,array $conditions=null){
	DBProfile::query('select');
	if ($db===null)
		$db= db_get_connection();
	if($conditions===null){
		$stm="SELECT EXISTS(SELECT 1 FROM $table)";
		$conditions=array();
	}else{
		$conditions=_db_build_where($conditions);
		$stm="SELECT EXISTS(SELECT 1 FROM $table {$conditions[0]})";
		$conditions=$conditions[1];
	}
	try{
		$stm = db_prepare($db,$stm);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	if(db_run_query($stm,$conditions))
		return false;
	$ret = $stm->fetch(PDO::FETCH_NUM);
	$stm->closeCursor();
	return $ret[0];
}

/**
 * Exists for logging purposes.
 * @param PDOStatement $stm
 * @param array $params
 * @return Ambigous <string>|boolean The error if failed or false on success.
 */
function db_run_query($stm, array $params=null){
	DBProfile::query('run');
	if(db_debug())echo '[['.db_stm_to_string($stm,$params).']]'."\n";
	if ($stm->execute($params)===false && $stm->errorCode()!='00000') {
		return db_log_error($stm,$params);
	}
	return false;
}
function db_stm_to_string($stm,array $params=null){
	if($params==null)return is_object($stm)?$stm->queryString:$stm;
	if(is_object($stm))
		$stm=$stm->queryString;
	foreach($params as $key=>$value){
		$stm=str_replace($key, "'$value'", $stm);
	}
	return $stm;
}
/**
 * Attempts to prepare the statement.
 * @param PDO $db
 * @param string $query
 * @throws PDOException
 * @return boolean|PDOStatement
 */
function db_prepare(PDO $db,$query){
	try{
		$query = $db->prepare($query);
	}catch(PDOException $e){
		if(db_debug()){
			throw $e;
		}else{
			return false;
		}
	}
	return $query;
}

function db_now(){
	return date('Y-m-d',time());
}
class WhereBuilder{
	private static $icnt=0;
	private $where='',$values=array(),$pre,$ci=0;
	public function __construct($prefix='wh'){
		$this->pre=":$prefix".(++self::$icnt);
	}
	public function appendWhere($andor,WhereBuilder $where,$parens=false){
		if($parens)
			$this->where.="$andor (".$where->where.')';
		else
			$this->where.="$andor ".$where->where;
	}
	public function openParen($andor){
		$this->where.="$andor (";
	}
	public function closeParen(){
		$this->where.=')';
	}
	/**
	 * Accepts multiple arguments.
	 * Assumes AND if this is not the first condition
	 * (['and'|'or',]col,val[,negate])
	 * (['and'|'or',]col,'in',vals[,negate])
	 * (['and'|'or',]col,'like',val[,negate])
	 * (['and'|'or',]col,'between',start,end[,negate])
	 * (['and'|'or',]col,comparator,val)
	 * comparator='>' | '<' | '>=' | '<='
	 * @return WhereBuilder A reference to itself for chaining.
	 */
	public function &addCond(){
		$arg=func_get_args();
		if(strlen($arg[0]) < 4 && (strtolower($arg[0])=='and' || strtolower($arg[0])=='or')){
			$this->where.=' '.array_pop($arg).' ';
		}elseif($this->ci>0){
			$this->where.=' AND ';
		}
		$ac=count($arg);
		if($ac===1)throw new ErrorException('At least 2 parameters are expected.');
		if($ac==3)$arg[1]=strtolower($arg[1]);
		if($ac==2){
			if($arg[1]==null){
				$this->where.="$arg[0] IS NULL";
			}else{
				$this->where.="$arg[0]=".$this->pre.$this->ci;
				$this->values[$this->pre.$this->ci]=$args[1];
				$this->ci++;
			}
		}elseif($ac==3 && (is_bool($arg[2]) || $arg[1][0]=='>' || $arg[1][0]=='<')){
			if(is_bool($arg[2])){
				if($arg[1]==null){
					if($arg[2])
						$this->where.="$arg[0] IS NULL";
					else
						$this->where.="$arg[0] IS NOT NULL";
				}else{
					if($arg[2])
						$this->where.="$arg[0]=".$this->pre.$this->ci;
					else
						$this->where.="$arg[0]!=".$this->pre.$this->ci;
					$this->values[$this->pre.$this->ci]=$args[1];
					$this->ci++;
				}
			}else{
				$this->where.="$arg[0]$arg[1]".$this->pre.$this->ci;
				$this->values[$this->pre.$this->ci]=$args[2];
				$this->ci++;
			}
		}elseif($arg[1]=='in'){
			switch($ac){
				case 3:
					$this->values[$this->pre.$this->ci]=$arg[2];
					$this->where.="$arg[0] IN (".$this->pre.$this->ci.')';
					$this->ci++;
				break;
				case 4:
					$this->values[$this->pre.$this->ci]=$arg[2];
					$this->where.=$arg[0].($arg[3]?' NOT IN (':' IN (').$this->pre.$this->ci.')';
					$this->ci++;
				break;
				default:
					throw new IllegalStateException('Arguement count not correct for IN clause.');
			}
		}elseif($arg[1]=='like'){
			switch($ac){
				case 3:
					$this->values[$this->pre.$this->ci]=$arg[2];
					$this->where.="$arg[0] LIKE ".$this->pre.$this->ci;
					$this->ci++;
				break;
				case 4:
					$this->values[$this->pre.$this->ci]=$arg[2];
					$this->where.=$arg[0].($arg[3]?' NOT LIKE ':' LIKE ').$this->pre.$this->ci;
					$this->ci++;
				break;
				default:
					throw new IllegalStateException('Arguement count not correct for LIKE clause.');
			}
		}elseif($arg[1]=='between'){
			switch($ac){
				case 4:
					$this->values[$this->pre.($this->ci)]=$arg[2];
					$this->values[$this->pre.($this->ci+1)]=$arg[3];
					$this->where.= $arg[0].' BETWEEN '.$this->pre.$this->ci .' AND '. $this->pre.($this->ci+1);
				break;
				case 5:
					$this->values[$this->pre.($this->ci)]=$arg[2];
					$this->values[$this->pre.($this->ci+1)]=$arg[3];
					$this->where.= $arg[0].($arg[4]?' NOT BETWEEN ':' BETWEEN ').$this->pre.$this->ci .' AND '. $this->pre.$this->ci+1;
					$this->ci+=2;
				break;
				default:
					throw new IllegalStateException('Arguement count not correct for BETWEEN clause.');
			}
		}else{
			throw new ErrorException('Unknown Clause:'.$arg[1]);
		}
		return $this;
	}
	public function getWhere(){
		return $this->where;
	}
	public function getValues(){
		return $this->values;
	}
}
class PDOStatementWrapper extends PropertyList{
	protected $dataset=null;
	public function __construct($stm,$fetch_mode=PDO::FETCH_ASSOC){
		if(!$stm instanceof PDOStatement)throw new ErrorException('$stm is not a PDOStatement');
		$this->dataset=$stm;
		$this->dataset->setFetchMode($fetch_mode);
	}
	public function bindParam($key,&$value,$type){
		$this->dataset->bindParam($key,$value,$type);
	}
	public function bindValue($key,$value,$type){
		$this->dataset->bindValue($key,$value,$type);
	}
	/**
	 * Closes the cursor and runs the statement.
	 * @param array $args [optional] The arguements for the statement
	 * @return boolean true on success, false on failure
	 */
	public function run(array $args=null){
		$this->dataset->closeCursor();
		return !db_run_query($this->dataset,$args);
	}
	public function loadNext(){
		if(!$this->dataset)throw new IllegalStateException('No query run or last query faled.');
		$this->data=$this->dataset->fetch();
		return $this->data!=false;
	}
	public function recycle(){
		if($this->dataset)$this->dataset->closeCursor();
		$this->data=array();
	}
}
/**
 * Class for working a PDO table.
 * @author Ken
 *
 */
class PDOTable{
	protected $table,
		$columns,
		$data,
		$dataset=null,
		$pkey=null,
		$db=null,
		$rowCountstm=null,
		$saveopstm=null,
		$loadstm=null,
		$plainloadall=null,
		$trackChanges=false,
		$lastOperation=self::OP_NONE,
		$lastError=null;
	const OP_NONE=0,OP_LOAD=1,OP_INSERT=2,OP_UPDATE=3,OP_DELETE=4;
	/**
	 * If set to TRUE it will keep a second array with the changes made to the model.
	 * @param boolean $v Optional. Sets the state if supplied
	 * @return boolean
	 */
	public function trackChanges($v=null){
		if($v===null)return $this->trackChanges;
		$v=$v===true;
		if($this->trackChanges){
			if(!$v){
				$t=new ChangeTrackingPropertyList();
				$t->initFrom($this->data->copyTo(array()));
				$this->data=$t;
			}
		}elseif($v){
			$t=new PropertyList();
			$t->initFrom($this->data->copyTo(array()));
			$this->data=$t;
		}
		$this->trackChanges=$v;
	}
	public function uset($k){
		$this->data->uset($k);
	}
	/**
	 * Merges the changes with the main array and clears the changes.
	 * Does NOT save the changes to the database.
	 */
	public function mergeChanges(){
		if($this->trackChanges)
			$this->data->mergeChanges();
	}
	/**
	 * Forgets any changes to the model.
	 * Will NOT undo changes commited to the database by calling save().
	 */
	public function forgetChanges(){
		if($this->trackChanges)
			$this->data->discardChanges();
	}
	/**
	 * Attempts to undo the last change.
	 * It will NOT revert any auto increment values. Do not use this
	 * as a replacement for transactions.
	 * @return boolean|string false on success or the error message.
	 */
	public function rollback(){
		switch($this->lastOperation){
			case self::OP_DELETE:
				return $this->insert();
			case self::OP_INSERT:
				return $this->delete();
			case self::OP_UPDATE:
				$change=clone $this->data;
				$this->data->discardChanges();
				$err=$this->update();
				$this->data=$data;
				return $err;
			case self::OP_LOAD:
			case self::OP_NONE:
				return false;
				break;
		}
	}
	/**
	 * Enter description here ...
	 * @param string $table
	 * @param array $columns column names
	 * @param array $colTypes columns types. One of PDO::PARAM_*
	 * @param string|array $pkey The primary key(s) for the table
	 * @param resource $db
	 * @param bool $trackChanges defaults to false
	 */
	public function __construct($table,array $columns,array $colTypes,$pkey,$db,$trackChanges=false){
		$this->table=$table;
		$this->columns=array_combine($columns,$colTypes);
		$this->pkey=$pkey;
		$this->db=$db;
		$this->data=$trackChanges?new ChangeTrackingPropertyList:new PropertyList;
		$this->trackChanges=$trackChanges;
	}
	public function copyTo(array $ary){
		return $this->data->copyTo($ary);
	}
	public function get($k,$d=null){
		return $this->data->get($k,$d);
	}
	public function set($k,$v){
		$this->data->set($k,$v);
	}
	/**
	 * @throws ErrorException
	 * @return number the number of rows in the table.
	 */
	public function getTotalRows(){
		if($this->rowCountstm==null){
			$this->rowCountstm=db_prepare($this->db,'SELECT COUNT(*) FROM '.$this->table);
			if(!$this->rowCountstm)throw new ErrorException('Could not prepare the statement.');
		}
		$err=db_run_query($this->rowCountstm);
		if($err)throw new ErrorException('Could not execute the query.:'.$err);
		$ret=$this->rowCountstm->fetch(PDO::FETCH_NUM);
		return $ret[0];
	}
	/**
	 * @throws ErrorException
	 * @return int The number of rows matched by the query.
	 */
	public function count(){
		$stm='SELECT COUNT(*) FROM '.$this->table;
		$args=null;
		if($this->data->count()){
			$args=array();
			$stm.=' WHERE ';
			$data=$this->data->copyTo(array());
			foreach($data as $k=>$v){
				$stm.="$k=? AND";
				$args[]=$v;
			}
			$stm=substr($stm,0,-4);
		}
		$stm=db_prepare($this->db,$stm);
		if(!$stm)throw new ErrorException('Could not prepare the statement.');
		$err=db_run_query($stm,$args);
		if($err)throw new ErrorException('Could not execute the query:'.$err);
		$ret=$stm->fetch(PDO::FETCH_NUM);
		return $ret[0];
	}
	/**
	 * Get's the primary key.
	 * If it is a compound key, an array in the form of ($key=>$value) containing the values is returned.
	 * @return multitype:|Ambiguous false if the ID is not set
	 */
	public function getId(){
		if(is_array($this->pkey)){
			$ret=array();
			foreach($this->pkey as $k){
				$ret[$k]=$this->data->get($k);
				if(null===$ret[$k])return false;
			}
			return $ret;
		}else
			return $this->data->get($this->pkey,false);
	}
	public function isPkeySet(){
		if(is_array($this->pkey)){
				$ret=array();
				foreach($this->pkey as $k){
					if(null===$this->data->get($k))return false;
				}
				return true;
			}else
				return $this->data->get($this->pkey)!==null;
	}
	/**
	 * Gets the primary key(s) for update and delete operations.
	 * @param mixed $id
	 * @throws IllegalArgumentException
	 * @return array
	 */
	protected function getPkey($id=null){
		if($id!=null){
			if(is_array($this->pkey)){
				if(!is_array($id))
					throw new IllegalArgumentException('Primary key is an array. Supplied IDs must also be an array.');
				elseif(count($this->pkey)!=count($id))
					throw new IllegalArgumentException('Key count('.count($this->pkey).') and ID count('.count($id).') are not equal');
				else{
					$ret=array();
					$keys=array_combine($this->pkey,$id);
					foreach($keys as $k=>$v){
						$ret[]=array($k,$v,'AND');
					}
					unset($ret[count($ret)-1][2]);
					return $ret;
				}
			}else
				return array(array($this->pkey,$id));
		}else{//id==null
			if(is_array($this->pkey)){
				$ret=array();
				foreach($this->pkey as $key){
					$ret[]=array($key,$this->data->get($key),'AND');
				}
				unset($ret[count($ret)-1][2]);
				return $ret;
			}else
				return array(array($this->pkey,$this->data->get($this->pkey)));
		}
	}
	/**
	 * Useful function for helper classes
	 * @param PDOStatement $data
	 */
	public function setDataset(PDOStatement $data){
		$this->recycle();
		$this->dataset=$data;
	}
	/**
	 * Loads the record.
	 * @param mixed $id
	 * @return boolean
	 */
	public function load($id){
		if($this->dataset)$this->dataset->closeCursor();
		$where=_db_build_where($this->getPkey($id));
		if($this->loadstm==null){
			$this->loadstm=db_prepare($this->db,'SELECT * FROM '.$this->table.' '.$where[0]);
		}else
			$this->loadstm->closeCursor();
		$error=db_run_query($this->loadstm,$where[1]);
		if($error){
			$this->lastError=$error;
			return false;
		}
		$row=$this->loadstm->fetch(PDO::FETCH_ASSOC);
		$this->data->initFrom($row?$row:array());
		$this->lastOperation=self::OP_LOAD;
		return $row!=null;
	}
	public function loadAll(array $columns=null,array $sortBy=null, $groupBy=null, $limit=0, $offset=0){
		if($this->dataset)$this->dataset->closeCursor();
		if($columns || $sortBy || $groupBy || $limit || $offset){
			$this->dataset=db_query($this->db, $this->table,$columns,null,$sortBy,$groupBy,null,$limit,$offset);
		}else{
			if($this->plainloadall==null){
				$this->plainloadall=db_prepare($this->db,'SELECT * FROM '.$this->table);
			}
			$error=db_run_query($this->plainloadall);
			if(!$error){
				$this->dataset=$this->plainloadall;
			}else
				$this->lastError=$error;
		}
		$this->lastOperation=self::OP_LOAD;
		return $this->dataset!=false;
	}
	public function find(array $columns = null,array $where = null,array $sortBy = null, $groupBy = null, $having = null,$limit=0,$offset=0){
		if($this->dataset)$this->dataset->closeCursor();
		if($where==null){
			$data=$this->data->copyTo(array());
			$where=array();
			foreach($data as $key=>$value){
				$where[]=array($key,$value,'AND');
			}
			unset($where[count($where)-1][2]);
		}
		if($this->exists($where))
			$this->dataset=db_query($this->db, $this->table,$columns,$where,$sortBy,$groupBy,$having,$limit,$offset);
		else
			$this->dataset=null;
		$this->lastOperation=self::OP_LOAD;
		return $this->dataset!=null;
	}
	public function exists(array $where=null){
		if($this->dataset)$this->dataset->closeCursor();
		if($where==null){
			$data=$this->data->copyTo(array());
			$where=array(array(1,1,'AND'));
			foreach($data as $k=>$v){
				$where[]=array($k,$v,'AND');
			}
			unset($where[count($where)-1][2]);
		}
		$count=db_exists($this->db, $this->table,$where);
		return $count=='1';
	}
	public function getLoadAllResult(){
		return $this->dataset;
	}
	public function loadNext(){
		if(!$this->dataset)throw new IllegalStateException('No query run or last query faled.');
		$row=$this->dataset->fetch(PDO::FETCH_ASSOC);
		$this->data->initFrom($row?$row:array());
		$this->lastOperation=self::OP_LOAD;
		return $row!=false;
	}
	/**
	 * Deletes the record represented by this object.
	 * @return bool false on success or the error.
	 */
	public function delete(){
		$error=db_delete($this->db,$this->table,$this->getPkey());
		$this->lastOperation=self::OP_DELETE;
		if($error)	$this->lastError=$error;
		return $error;
	}
	/**
	 * Automatically chooses between insert and update based on the availability of the
	 * primary keys.
	 * @return string,boolean false on success or the error.
	 */
	public function save(){
		$error=false;
		if($this->isPkeySet()){
			$error=$this->update();
		}else{
			$error=$this->insert();
		}
		return $error;
	}
	/**
	 * Forces an update.
	 * @return string,boolean FALSE on success or the error.
	 */
	public function update(){
		$query='UPDATE `'.$this->table.'`  SET ';
		$update=$this->data->copyTo(array());
		$data=array();
		foreach($update as $k=>$v){
			$query.="$k=:$k,";
			$data[':'.$k]=$v;
		}
		$where=_db_build_where($this->getPkey());
		$query=substr($query,0,-1).' '.$where[0];
		$stm=db_prepare($this->db, $query);
		$data=array_merge($data,$where[1]);
		$error=db_run_query($stm,$data);
		$this->lastOperation=self::OP_UPDATE;
		if($error)$this->lestError=$error;
		return $error;
	}
	/**
	 * Forces an insert. Useful if you want to specify an Auto Increment field.
	 * @return string,boolean false on success or the error.
	 */
	public function insert(){
		$data=$this->data->copyTo(array());
		$query='INSERT INTO `'.$this->table.'` (`'.implode('`,`',array_keys($data)).'`) VALUES (:'.implode(',:',array_keys($data)).')';
		$stm=db_prepare($this->db, $query);
		foreach($data as $k=>$v)
			$stm->bindValue(":$k",$v,$this->columns[$k]);
		$error=db_run_query($stm);
		if(!$error && !is_array($this->pkey) && !$this->isPkeySet())
			$this->data->set($this->pkey,$this->db->lastInsertId());
		$this->lastOperation=self::OP_INSERT;
		if($error)$this->lastError=$error;
		return $error;
	}
	protected function saveOperation($type){
		if($this->saveopstm==null){
			$this->saveopstm=db_prepare($this->db,'INSERT INTO `updates` (`type`,`data`) VALUES (:type,:data)');
			if(!$this->saveopstm)throw new ErrorException('Could not prepare the statement.');
		}
		db_run_query($this->saveopstm,array(':type'=>$type,':data'=>serialize($this)));
	}
	/**
	 * Resets the internal arrays and frees any open result sets.
	 */
	public function recycle(){
		if($this->dataset)$this->dataset->closeCursor();
		$this->data->initFrom(array());
		$this->lastOperation=self::OP_NONE;
		$this->lastError=null;
	}
	public function __clone(){
		$this->data=clone $this->data;
	}
	/**
	 * @return Ambigous <Ambigous, boolean, mixed, string> The last error.
	 */
	public function getLastError(){
		return $this->lastError;
	}
}


/**
 * Class to build a table with pagination and sorting backed by a SQL table.
 * For the format of a cell, the current column's value is referenced by $value$. A hidden column's value can be referenced by $col name$.
 * WARNING: Spaces ARE allowed in the column names! There is no escape character. You CAN have $ in the column value.
 * @author Kenneth Pierce
 */
class sql_table {
	private $prefix='';
	private $table = '';
	private $select_columns = array();
	private $shown_columns=array();
	private $hidden_columns = array();
	/**
	 * Double entry array mapping columns to aliases and aliases to columns for shown columns. Only a single entry is entered for hidden columns mapping the alias to the column.
	 * @var array
	 */
	private $aliases_columns = array();
	private $column_format = array();
	private $column_attributes = array();
	private $col_callback = array();
	private $defaultSort=array();
	private $caption=null;
	public function setCaption($caption){
		$this->caption=$caption;
	}
	public function addSort($column,$dir){
		$this->defaultSort[]=array($column,$dir);
	}
	public function setPrefix($prefix){
		$this->prefix=$prefix;
	}
	/**
	 * Sets the table to be queried.
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}
	function addAlias($col,$alias){
		$this->aliases_columns[$alias]=$col;
		if(!isset($this->hidden_columns[$col]))
			$this->aliases_columns[$col]=$alias;
	}
	/**
	 * Adds a hidden column. It is in the select statement, but not displayed.
	 * @param string $column The column name.
	 * @param string $alias Column alias(for easier reference)
	 * @param string $callback Function to be called on the value
	 */
	public function addHiddenColumn($column,$alias=null,$callback=null) {
		$this->select_columns[] = $column;
		$this->hidden_columns[] = $column;
		if($alias)
			$this->aliases_columns[$alias]=$column;
		if($callback)
			$this->col_callback[$column]=$callback;
	}
	/**
	 * Adds several hidden columns.
	 * @param array $columns Column list.
	 */
	public function addHiddenColumns(array $columns) {
		$this->select_columns = array_merge($this->select_columns, $columns);
		$this->hidden_columns = array_merge($this->hidden_columns, $columns);
	}
	/**
	 * Adds a column that will only use other columns to build it's content. This column will NOT be in the select statement. As such, you cannot set an alias for or sort by this column.
	 * @param string $column
	 * @param string $format
	 * @param array $tdattib
	 */
	public function addDummyColumn($column,$format,array $tdattib=null){
		$this->shown_columns[]=$column;
		$this->column_format[$column] = $format;
		if(isset($tdattrib))
			$this->column_attributes[$column] = $tdattrib;
	}
	/**
	 * Adds a function that will be called on each value of the column. The function should take one argument and return a value.
	 * @param string $column Name of the column or alias. You can use aliases to refrence the same data with different callbacks.
	 * @param string $callback
	 */
	public function addCallback($column, $callback){
		$this->col_callback[$column]=$callback;
	}
	/**
	 * Adds a column to the select query.
	 * @param string $column The table column
	 * @param string $alias The name to be displayed.
	 * @param string $format String containing the format for the column. Use $value$ to specify where the column value should be.
	 * @param array $tdattrib array of key=>value mappings to be added to the TD element containing this value.
	 * @param string $callback A string containing the name of a function that will be called on this value. It should take one argument and return a value.
	 */
	public function addColumn($column, $alias=null,$format=null,array $tdattrib=null,$callback=null) {
		$this->select_columns[] = $column;
		$this->shown_columns[]=$column;
		if ($alias!=null){
			$this->aliases_columns[$column] = $alias;
			$this->aliases_columns[$alias] = $column;
		}
		if($format!=null)
			$this->column_format[$column] = $format;
		if($tdattrib!=null)
			$this->column_attributes[$column] = $tdattrib;
		if($callback!=null)
			$this->col_callback[$column] = $callback;
	}
	/**
	 * Adds the columns to the select query.
	 * @param array $columns Table columns.
	 * @param array $aliases Display columns.
	 */
	public function addColumns(array $columns, array $aliases) {
		$merged = array_combine($columns, $aliases);
		foreach ($merged as $column => $alias)
			$this->addColumn($column, $alias);
	}
	/**
	 * Sets the format/content for the column. Columns can be
	 * referenced by $column.
	 * @param string $column The column name.
	 * @param string $format String containing the format for the column. Use $value$ to specify where the column value should be.
	 */
	public function setColumnFormat($column, $format) {
		$this->column_format[$column] = $format;
	}
	/**
	 * Queries and returns the table.
	 * @param resource $db MySQL database connection.
	 * @param string $conditions SQL query conditions.
	 * @param string $extra Extra appended to the link.
	 * @return string The table.
	 */
	public function printTable($db, $conditions = null, $extra = '') {
		$row_count=0;
		$row_count=db_num_rows($db,$this->table,$conditions);
		if($row_count==0){
			return 'Nothing found.';
		}
		$buf = '';
		$start = 0;
		$numrows = 10;
		$sort = null;
		$sortDir = 'ASC';
		$dir = 'up';
		$pageLinks = '';
		$shown_columns = array_diff($this->select_columns, $this->hidden_columns);
		if (isset($_GET[$this->prefix.'start'])){
			$start = $_GET[$this->prefix.'start'];
		}
		if (isset($_GET[$this->prefix.'numrows'])) {
			$numrows = $_GET[$this->prefix.'numrows'];
			if ($numrows > 100) {
				$numrows = 100;
			} else if ($numrows < 1) {
				$numrows = 10;
			}
		}
		/*if (isset($_GET['letter']) && !empty($_GET['letter'])) {
			if (ereg('^[a-z]?$', $_GET['letter']))
			$letter = $_GET['letter'];
		}*/
		if (isset($_GET[$this->prefix.'sort']) && !empty($_GET[$this->prefix.'sort'])) {
			$sort = $_GET[$this->prefix.'sort'];
			if(!in_array($sort, $this->select_columns)){
				if(isset($this->aliases_columns[$sort])){
					$sort=$this->aliases_columns[$sort];
				}else{$sort=null;}
			}
			if (isset($_GET[$this->prefix.'dir'])) {
				if ($_GET[$this->prefix.'dir'] == 'down') {
					$sortDir = 'DESC';
				} else {
					$sortDir = 'ASC';
				}
				$dir = $_GET['dir'];
			}
		}
		if ($sort==null){
			if(count($this->defaultSort)>0){
				$sort='';
				foreach($this->defaultSort as $dsort)
					$sort.= $dsort[0].' '.$dsort[1].',';
				$sort=substr($sort,0,-1);
			}
		}else{
			$sort.=" $sortDir";
		}
		$sql = 'SELECT SQL_CACHE ';
		$sql .= implode(',', $this->select_columns);
		$where=null;
		//limit [offset,]row count
		if ($conditions == '' || $conditions == null)
			$sql .= " FROM $this->table";
		else{
			$where=_db_build_where($conditions);
			$sql.= " FROM $this->table $where[0]";
			$where=$where[1];
		}
		if($sort)
			$sql.= " ORDER BY $sort";
		$sql.= " LIMIT $start, $numrows";
		if(db_debug())echo $sql;
		$res=db_prepare($db,$sql);
		if(!$res->execute($where)) {
			$err = db_log_error($res,$where);
			return 'Database error: '.$err;
		}
		$res->setFetchMode(PDO::FETCH_ASSOC);
		//$row_count=mysql_num_rows($res);
		/*if($row_count==0){
			return 'Nothing found.';
		}*/
		/********************
		 ********************
		 ********************/
		?>
		<form method="get">
		Results per page:
			<select name="<?php echo $this->prefix ?>numrows">
		<?php for ($i = 10; $i < 101; $i+=10) {?>
				<option value="<?php echo $i; ?>" <?php echo ($i == $numrows)?'selected="selected"':''; ?>><?php echo $i; ?></option>
		<?php }?>
			</select>
			<?php
				$gcopy=$_GET;
				unset($gcopy[$this->prefix.'numrows']);
				foreach($gcopy as $key=>$value) {
					//if ($key == 'numrows') continue;
					echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
				}
				unset($gcopy);
			?>
		<input type="submit" value="Update" />
		</form>
		<?php
		$pageLinks = getPages($row_count, $start, $numrows, "&amp;".$this->prefix."sort=$sort&amp;".$this->prefix."dir=".$dir.$extra,$this->prefix);
		$buf .= "<div>$pageLinks</div>\n";
		$buf .= '<table cellspacing="0">';
		$buf.= "<caption>$this->caption</caption>";
		/********************
		 ****TITLES**********
		 ********************/
		// DIR CHANGES USES HERE!! IT NO LONGER CONTAINS THE DIRECTION STRING
		$buf .= "\n<tr>\n";
		$baseurl = '';//$_SERVER['PHP_SELF'];

		foreach($this->shown_columns as $column) {
			$display = isset($this->aliases_columns[$column])?$this->aliases_columns[$column]:$column;
			if(in_array($column,$this->select_columns)){
				$buf.="\t<th><a href=\"".$baseurl.'?'.$this->prefix.'sort='.$display;
				$dir=false;// false is up
				if ($sort==$column) {
					if ($sortDir=='ASC') {
						$buf .= '&amp;'.$this->prefix.'dir=down';
						$dir = true;
					} else {
						$buf .= '&amp;'.$this->prefix.'dir=up';
					}
					$buf .= '&amp;'.$this->prefix.'numrows='.$numrows.$extra.'">'.$display;
					if ($dir) {
						$buf .= '&nbsp;<img src="/lib/i/arrow_down.png" />';
					} else {
						$buf .= '&nbsp;<img src="/lib/i/arrow_up.png" />';
					}
				} else {
					$buf .= '&amp;'.$this->prefix.'dir=down&amp;'.$this->prefix.'numrows='.$numrows.$extra.'">'.$display;
				}
				$buf .= "</a></th>\n";
			}else{
				$buf.="\t<th>$display</th>\n";
			}
		}
		$buf .= '</tr>';
		/********************
		 ***ROWS*************
		 ********************/
		 /*
		$select_columns = array();
		$hidden_columns = array();
		$aliases_columns = array();
		$column_format = array();
		*/
		while ($row = $res->fetch()) {
			if($numrows<1)break;
			$rowBuf = "<tr>\n";
			foreach($this->shown_columns as $column) {
				if(isset($this->column_attributes[$column])){
					$rowBuf .= "\t<td";
					foreach($this->column_attributes[$column] as $key=>$value){
						$rowBuf.=" $key=\"$value\"";
					}
					$rowBuf.=">";
				}else{
					$rowBuf .= "\t<td>";
				}
				if(isset($this->col_callback[$column])){
					$row[$column]=call_user_func($this->col_callback[$column],$row[$column]);
				}
				if (isset($this->column_format[$column])){
					if(isset($row[$column]))
						$cvalue=str_replace('$value$',$row[$column],$this->column_format[$column]);
					else
						$cvalue=$this->column_format[$column];
					$idx=-1;
					while(($idx=strpos($cvalue,'$',$idx+1))!==false){
						$idx2=strpos($cvalue,'$',$idx+1);
						if($idx2===false){break;}
						$sidx=substr($cvalue,$idx+1,$idx2-$idx-1);
						if(isset($this->aliases_columns[$sidx])&&isset($row[$this->aliases_columns[$sidx]])){
							$cbvalue=$row[$this->aliases_columns[$sidx]];
							if(isset($this->col_callback[$sidx])){
								$cbvalue=call_user_func($this->col_callback[$sidx],$row[$this->aliases_columns[$sidx]]);
							}
							$cvalue=str_replace('$'.$sidx.'$',$cbvalue,$cvalue);
							$idx+=strlen($cbvalue);
						}elseif(isset($row[$sidx])){
							$cbvalue=$row[$sidx];
							if(isset($this->col_callback[$sidx])){
								$cbvalue=call_user_func($this->col_callback[$sidx],$row[$sidx]);
							}
							$cvalue=str_replace('$'.$sidx.'$',$cbvalue,$cvalue);
							$idx+=strlen($cbvalue);
						}else{$idx=$idx2-1;}
					}
					$rowBuf.=$cvalue;
				}else
					$rowBuf .= $row[$column];
				$rowBuf .= "</td>\n";
			}
			$buf .= $rowBuf."</tr>\n";
			$numrows--;
		}
		$buf .= '</table>';
		$buf .= "<div>$pageLinks</div>";
//		$buf .= '<span style="font-size:smaller;">'.getElapsed('query').' seconds</span>';
		return $buf;
	}
}// -- end class sql_table

/**
 * Class to build a table without pagination and sorting backed by a SQL table.
 * For the format of a cell, the current column's value is referenced by $value$. A hidden column's value can be referenced by $col name$.
 * WARNING: Spaces ARE allowed in the column names! There is no escape character. You CAN have $ in the column value.
 * @author Kenneth Pierce
 */
class sql_table_simple {
	private $sort=null;
	private $dir='desc';
	private $table = '';
	private $showHeader=true;
	private $select_columns = array();
	private $shown_columns=array();
	private $hidden_columns = array();
	/**
	 * Double entry array mapping columns to aliases and aliases to columns for shown columns. Only a single entry is entered for hidden columns mapping the alias to the column.
	 * @var array
	 */
	private $aliases_columns = array();
	private $column_format = array();
	private $column_attributes = array();
	private $quirk_col=array();
	private $col_callback = array();
	public function setShowHeader($bool){
		$this->showHeader=$bool;
	}
	public function setPrefix($prefix){
		$this->prefix=$prefix;
	}
	/**
	 * Sets the table to be queried.
	 * @param string $table
	 */
	public function setTable($table) {
		$this->table = $table;
	}
	function addAlias($col,$alias){
		$this->aliases_columns[$alias]=$col;
		if(!isset($this->hidden_columns[$col]))
			$this->aliases_columns[$col]=$alias;
	}
	/**
	 * Adds a hidden column. It is in the select statement, but not displayed.
	 * @param string $column The column name.
	 * @param string $alias Column alias(for easier reference)
	 * @param string $callback Function to be called on the value
	 */
	public function addHiddenColumn($column,$alias=null,$callback=null) {
		$this->select_columns[] = $column;
		$this->hidden_columns[] = $column;
		if($alias)
			$this->aliases_columns[$alias]=$column;
		if($callback)
			$this->col_callback[$column]=$callback;
	}
	/**
	 * Adds several hidden columns.
	 * @param array $columns Column list.
	 */
	public function addHiddenColumns(array $columns) {
		$this->select_columns = array_merge($this->select_columns, $columns);
		$this->hidden_columns = array_merge($this->hidden_columns, $columns);
	}
	/**
	 * Adds a column that will only use other columns to build it's content. This column will NOT be in the select statement. As such, you cannot set an alias for or sort by this column.
	 * @param string $column
	 * @param string $format
	 * @param array $tdattib
	 */
	public function addDummyColumn($column,$format,array $tdattib=null){
		$this->shown_columns[]=$column;
		$this->column_format[$column] = $format;
		if(isset($tdattrib))
			$this->column_attributes[$column] = $tdattrib;
	}
	/**
	 * Adds a function that will be called on each value of the column. The function should take one argument and return a value.
	 * @param string $column Name of the column or alias. You can use aliases to refrence the same data with different callbacks.
	 * @param string $callback
	 */
	public function addCallback($column, $callback){
		$this->col_callback[$column]=$callback;
	}
	/**
	 * Specifies a column that needs to be referred by a different name. The problem that sparked this addition:
	 * Column in the select statement: roster.pos
	 * Column in the array: pos
	 * @param unknown_type $col The original column name
	 * @param unknown_type $resolved The name that should be used
	 */
	public function addQuirkCol($col,$resolved){
		$this->quirk_col[$col]=$resolved;
	}
	/**
	 * Adds a column to the select query.
	 * @param string $column The table column
	 * @param string $alias The name to be displayed.
	 * @param string $format String containing the format for the column. Use $value$ to specify where the column value should be.
	 * @param array $tdattrib array of key=>value mappings to be added to the TD element containing this value.
	 * @param string $callback A string containing the name of a function that will be called on this value. It should take one argument and return a value.
	 */
	public function addColumn($column, $alias=null,$format=null,array $tdattrib=null,$callback=null) {
		$this->select_columns[] = $column;
		$this->shown_columns[]=$column;
		if ($alias!=null){
			$this->aliases_columns[$column] = $alias;
			$this->aliases_columns[$alias] = $column;
		}
		if($format!=null)
			$this->column_format[$column] = $format;
		if($tdattrib!=null)
			$this->column_attributes[$column] = $tdattrib;
		if($callback!=null)
			$this->col_callback[$column] = $callback;
	}
	/**
	 * Adds the columns to the select query.
	 * @param array $columns Table columns.
	 * @param array $aliases Display columns.
	 */
	public function addColumns(array $columns, array $aliases) {
		$merged = array_combine($columns, $aliases);
		foreach ($merged as $column => $alias)
			$this->addColumn($column, $alias);
	}
	/**
	 * Sets the format/content for the column. Columns can be
	 * referenced by $column.
	 * @param string $column The column name.
	 * @param string $format String containing the format for the column. Use $value$ to specify where the column value should be.
	 */
	public function setColumnFormat($column, $format) {
		$this->column_format[$column] = $format;
	}
	public function setSort($column,$direction){
		if(isset($this->aliases_columns[$column]))
			$this->sort=$this->aliases_columns[$column];
		else
			$this->sort=$column;
		$this->dir=$direction;
	}
	/**
	 * Queries and returns the table.
	 * @param resource $db MySQL database connection.
	 * @param string $conditions SQL query conditions.
	 * @param string $extra Extra appended to the link.
	 * @return string The table.
	 */
	public function printTable($db, $conditions = null, $extra = '') {
		$row_count=0;
		$row_count=db_num_rows($db,$this->table,$conditions);
		if($row_count==0){
			return 'Nothing found.';
		}
		$buf = '';
		$shown_columns = array_diff($this->select_columns, $this->hidden_columns);
		if ($this->sort==null){
			$this->sort = $this->select_columns[0];
		}
		$sql = 'SELECT SQL_CACHE ';
		$sql .= implode(',', $this->select_columns);
		//limit [offset,]row count
		if ($conditions == '' || $conditions == null)
			$sql .= " FROM $this->table";
		else
			$sql .= " FROM $this->table WHERE $conditions";
		$sql .= " ORDER BY $this->sort $this->dir";
		if(db_debug())echo $sql;
		$res = $db->query($sql);
		if (!$res) {
			$err = db_log_error($sql);
			return 'Database error: '.$err;
		}
		$res->setFetchType(PDO::FETCH_ASSOC);
		$buf .= '<table cellspacing="0">';
		/********************
		 ****TITLES**********
		 ********************/
		// DIR CHANGES USES HERE!! IT NO LONGER CONTAINS THE DIRECTION STRING
		if($this->showHeader){
			$buf .= "\n<tr>\n";
			$baseurl = '';//$_SERVER['PHP_SELF'];
			foreach($this->shown_columns as $column) {
				$display = isset($this->aliases_columns[$column])?$this->aliases_columns[$column]:$column;
				$buf.="\t<th>$display</th>\n";
			}
			$buf .= '</tr>';
		}
		/********************
		 ***ROWS*************
		 ********************/
		 /*
		$select_columns = array();
		$hidden_columns = array();
		$aliases_columns = array();
		$column_format = array();
		*/
		while ($row = $res->fetch()) {
			$rowBuf = "<tr>\n";
			foreach($this->shown_columns as $scolumn) {
				if(isset($this->quirk_col[$scolumn]))$column=$this->quirk_col[$scolumn]; else $column=$scolumn;
				if(isset($this->column_attributes[$scolumn])){
					$rowBuf .= "\t<td";
					foreach($this->column_attributes[$scolumn] as $key=>$value){
						$rowBuf.=" $key=\"$value\"";
					}
					$rowBuf.=">";
				}else{
					$rowBuf .= "\t<td>";
				}
				if(isset($this->col_callback[$scolumn])){
					$row[$column]=call_user_func($this->col_callback[$scolumn],$row[$column]);
				}
				if (isset($this->column_format[$scolumn])){
					if(isset($row[$column]))
						$cvalue=str_replace('$value$',$row[$column],$this->column_format[$scolumn]);
					else
						$cvalue=$this->column_format[$scolumn];
					$idx=-1;
					while(($idx=strpos($cvalue,'$',$idx+1))!==false){
						$idx2=strpos($cvalue,'$',$idx+1);
						if($idx2===false){break;}
						$sidx=substr($cvalue,$idx+1,$idx2-$idx-1);
						if(isset($this->aliases_columns[$sidx])&&isset($row[$this->aliases_columns[$sidx]])){
							$cbvalue=$row[$this->aliases_columns[$sidx]];
							if(isset($this->col_callback[$sidx])){
								$cbvalue=call_user_func($this->col_callback[$sidx],$row[$this->aliases_columns[$sidx]]);
							}
							$cvalue=str_replace('$'.$sidx.'$',$cbvalue,$cvalue);
							$idx+=strlen($cbvalue);
						}elseif(isset($row[$sidx])){
							$cbvalue=$row[$sidx];
							if(isset($this->col_callback[$sidx])){
								$cbvalue=call_user_func($this->col_callback[$sidx],$row[$sidx]);
							}
							$cvalue=str_replace('$'.$sidx.'$',$cbvalue,$cvalue);
							$idx+=strlen($cbvalue);
						}else{$idx=$idx2-1;}
					}
					$rowBuf.=$cvalue;
				}else
					$rowBuf .= $row[$column];
				$rowBuf .= "</td>\n";
			}
			$buf .= $rowBuf."</tr>\n";
		}
		$buf .= '</table>';
//		$buf .= '<span style="font-size:smaller;">'.getElapsed('query').' seconds</span>';
		return $buf;
	}
}// -- end class sql_table

function getPages($totalRows, $currentRow, $rowsPerPage, $extra = '',$prefix='') {
	$cPages = ceil($totalRows/$rowsPerPage);
	if ($cPages == 1){return ' ';}
	if (isset($_GET[$prefix.'start']))
		$start = $_GET[$prefix.'start'];
	else
		$start = 0;
	$pageLinks = '';
	if ($start > 0) {
		$pageLinks .= '<a href="?'.$prefix.'start=0&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_first.png"></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.($currentRow-$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_previous.png"></a>';
	}
	$page = $currentRow/$rowsPerPage;
	$cPage = $page;
	$topPage = $page+2;
	$page -= 2;
	if ($page < 0) { $page = 0; }
	for (; $page <= $topPage && $page < $cPages; $page++) {
		if ($page == $cPage) {
			$pageLinks .= ' '.($page+1);
		} else {
			$pageLinks .= ' <a href="?'.$prefix.'start='.($page*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'">'.($page+1).'</a>';
		}
	}

	if ($topPage-1 < $cPages) {
		if ($topPage < $cPages-1)
			$pageLinks .= ' ... <a href="?'.$prefix.'start='.(($cPages-1)*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'">'.$cPages.'</a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($topPage-1)*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_next.png"></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($cPages-1)*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_last.png"></a>';
	}
	return $pageLinks;
} // -- getPages --