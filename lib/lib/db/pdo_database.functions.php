<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

PackageManager::requireClassOnce('ml.html');
PackageManager::requireClassOnce('error.IllegalArgumentException');
//require_once LIB.'lib/ml/class_html.inc.php';
//require_once LIB.'lib/error/class_IllegalArgumentException.php';
global $_DB, $_DB_OPEN_CON;
$_DB = null;
$_DB_OPEN_CON = false;
function db_isDebug(){
	if(isset($GLOBALS['simple']['lib']['db']['debug']))
		if($GLOBALS['simple']['lib']['db']['debug']==true)
			return true;
	return false;
}
/**
 * creates a connection to the database if none exists
 * or returns one already created.
 * @param boolean $forcenew Forces the creation of a new connection.
 * @return Ambigous <NULL, resource> Returns a PDO object on success, null on failure. Throws a PDOException if database debug is on.
 */
function db_get_connection($forcenew = false) {
	global $_DB, $_DB_OPEN_CON;
	$conf = $GLOBALS['simple']['lib']['db'];
	if ($forcenew){db_close_connection();}
	if (!$_DB_OPEN_CON || $_DB = null) {
		try{
		$_DB = new PDO($conf['engine'].':host='.$conf['host'].';dbname='.$conf['dbname'],$conf['user'],$conf['password']);
		}catch(PDOException $e){
			if(db_isDebug())
				throw $e;
			else
				return null;
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
function db_log_error($statement,$args) {
	$db = db_get_connection();
	$err=$statement->errorInfo();
	$params=array(
		':query'=>$statement->queryString,
		':message'=>"SQLSTATE=$err[0]\nDVR ERR=$err[1]\nDVR MSG='$err[2]'\nARGS:".var_export($args,true)
	);
	try{
		$stm=$db->prepare('INSERT INTO errors (err_date, err_msg, err_query) VALUES (NOW(),:message,:query)');
		$stm->execute();
	}catch(PDOException $e){
		if(db_isDebug())throw $e;
	}

	return $params[':message'];
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
		if(is_numeric($ret))return $ret;
		return $ret>0;
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
	if ($db===null)
		$db = db_get_connection();

	if($conditions===null){
		$stm = "SELECT :columns FROM :table LIMIT 0,1";
		$conditions=array();
	}else{
		$conditions=_db_build_where($conditions);
		$stm = "SELECT :columns FROM :table ".$conditions[0].' LIMIT 0,1';
	}
	$conditions[1][':table']=$table;
	$conditions[1][':columns']=$columns;
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_isdebug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	if (!$stm->execute($conditions[1])) {
		return db_log_error($stm,$conditions[1]);
	}else{
		$row=$stm->fetch($type);
		$stm->closeCursor();
		return $row;
	}
}

/**
 * Prints a table displaying the result.
 * @param resource mysql_result
 */
function result_table($result) {
	echo '<table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse" id="table1">';
		$rowc = 0;
		$color = "";
		$index = 0;
		$i = mysql_num_fields($result);
		echo '<tr>';
		for ($j=0; $j<$i;$j++)
			echo '<th>'.mysql_field_name($result, $j).'</th>';
		echo '</tr>';
		while ($row = mysql_fetch_array($result)) {
			$index = 0;
			if ($rowc%2 == 0)
				$color = "#FFFFFF";
			else
				$color = "#DDDDDD";
			echo "<tr>";
			foreach($row as $col) {

				if ($index%2 != 0)
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
 * @param array $where Array of conditions to be met. Each element must be array(column, value, ['AND'|'OR']).
 *		The last element must have the 3rd argument ommited or set to NULL.
 *		Special elements:
 *		+array(column, 'IN', list, negate, ['AND'|'OR'])
 *			list must NOT be an array nor enclosed in ().
 *			negate must be true or false and indicates 'NOT IN' when true.
 *		+array(column, 'BETWEEN', lower, upper, negate, ['AND'|'OR'])
 *		+array(column, 'LIKE', string value, negate, ['AND'|'OR'])
 *		+array('LITERAL', literal, ['AND'|'OR'])
 * @return array the resulting where string and array of values for a PDOStatement
 */
function _db_build_where(array $where) {
	$ret=array();
	$wcount=0;
	$where_2 = array();
	$wpart='';
	foreach($where as $arg){
		if(count($arg) > 3){
			if($arg[1] == 'IN'){
				$ret[1][':where'.($wcount)]=$arg[0];
				$ret[1][':where'.($wcount+1)]=$arg[2];
				$where_2[]= ':where'.($wcount) . ($arg[3]?' NOT IN (':' IN (') . ':where'.($wcount+1) . ')' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount+=2;
			}elseif($arg[1] == 'LIKE'){
				$ret[1][':where'.($wcount)]=$arg[0];
				$ret[1][':where'.($wcount+1)]=$arg[2];
				$where_2[] = ':where'.($wcount) . ($arg[3]?' NOT LIKE ':' LIKE ') . '\'' . ':where'.($wcount+1) . '\'' . ((count($arg)==5)?' '.$arg[4].' ' : '');
				$wcount+=2;
			}elseif($arg[1] == 'BETWEEN'){
				$ret[1][':where'.($wcount)]=$arg[0];
				$ret[1][':where'.($wcount+1)]=$arg[2];
				$ret[1][':where'.($wcount+2)]=$arg[3];
				$where_2[]= ':where'.($wcount) . ($arg[4]?' NOT BETWEEN ':' BETWEEN ') . ':where'.($wcount+1) . ' AND ' . ':where'.($wcount+2) . ((count($arg)==6)?' '.$arg[5].' ' : '');
				$wcount+=3;
			}
		}else{
			if(count($arg) == 3){
				if ($arg[0]=='LITERAL'){
					$ret[1][':where'.($wcount)]=$arg[0];
					$where_2[] = $arg[1] . ' '.$arg[2].' ';
				}else{
					$ret[1][':where'.($wcount)]=$arg[0];
					$ret[1][':where'.($wcount+1)]=$arg[1];
					$where_2[] =':where'.($wcount) . '=' . ':where'.($wcount+1) . ' '.$arg[2].' ';
				}
			}else{
				if($arg[0]=='LITERAL'){
					$ret[1][':where'.($wcount)]=$arg[0];
					$where_2[] = $arg[1];
				}else{
					$ret[1][':where'.($wcount)]=$arg[0];
					$ret[1][':where'.($wcount+1)]=$arg[1];
					$where_2[] = ':where'.($wcount) . '=' . ':where'.($wcount+1);
				}
			}
		}
	}
	$ret[0]='WHERE '.implode('', $where_2);
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
	if ($db===null)
		$db = db_get_connection();
	if($where!==null){
		$where=_db_build_where($where);
	}else{
		$where=array();
	}
	$query = 'SELECT ';
	if ($columns!==null){
		$ccount=0;
		foreach($columns as $column){
			$query .= ':col'.$ccount.',';
			$where[1][':col'.$ccount]=$column;
		}
		$query= trim($query,',');
	}else
		$query .= '*';
	if (!empty($table)){
		$query .= ' FROM :table';
		$conditions[1][':table']=$table;
	}
	if (isset($where[0])){
		$query .= ' '.$where[0];
	}
	if ($groupBy != null) {
		$query .= ' GROUP BY :groupby';
		$where[1][':groupby']=$groupBy;
		if ($having != null){
			$query .=  'HAVING :having';
			$where[1][':having']=$having;
		}
	}
	if ($sortBy != null) {
		$query .= ' ORDER BY';
		$ccount=0;
		foreach($sortBy as $sort) {
			$query .= " :sort$ccount :sortd$ccount,";
			$where[1][':sort'.$ccount]=$sort[0];
			$where[1][':sortd'.$ccount]=$sort[1];
			$ccount++;
		}
		$query = substr($query, 0, -1);
	}
	if($limit>0){
		$query.=" LIMIT :qlimit";
		$where[1][':qlimit']=$limit;
		if($offset>0){
			$query.=" OFFSET :qoffset";
			$where[1][':qoffset']=$offset;
		}
	}
	$stm=$db->prepare($query);
	if (!$stm->execute($where[1])) {
		return db_log_error($stm,$where[1]);
	}
	return $stm;
}
/** Updates data in the database. Returns the error on failure and false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $data Array of column names and the new values. Each element must be array[column]=value.
 * @param array $conditions See _db_build_where(..).
 * @return mixed false on success or the error on failure.
 */
function db_update($db, $table, array $data, array $conditions = null) {
	if ($db===null)
		$db = db_get_connection();
	$query = "UPDATE $table SET ";
	$data_2 = array();
	foreach($data as $key=>$value) {
		$data_2[] = $key.'='._db_validate_value($value);
	}
	$query .= implode(',', $data_2);
	if ($conditions != null)
		$query .= ' ' . _db_build_where($conditions);
	if(db_isDebug()) echo $query;
	$res = mysql_query($query, $db);
	if (!$res) {
		return db_log_error(mysql_error(), $query);
	}
	return false;
}

/** Inserts data into the database. Returns NULL on failure or false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $data Array of column names and the new values. Each element must be array[column]=value.
 * @return mixed false on success or the error.
 */
function db_insert($db, $table, array $data) {
	if ($db===null)
		$db = db_get_connection();
	//printVar($data);
	//printVar(array_map('_db_validate_value', $data));
	$stm = "INSERT INTO $table " . '(`' . implode('`, `',array_keys($data)) . '`) VALUES ('
		. trim(str_repeat('?,',count($data)),',') . ')';
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_isdebug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	if (!$stm->execute($conditions[1])) {
		return db_log_error($stm,array_merge(array(':table'=>$table),$conditions[1]));
	}else{
		$stm->closeCursor();
		return true;
	}
}

/** Inserts data into the database. Returns the error message on failure or false on success.
 * @param resource $db mysql database link. Set to null to use the default settings.
 * @param string $table Name of the table
 * @param array $columns Array of column names.
 * @param array $data Multi-deminsional array of the values. $data = array(array(row data), array(row data), ...).
 */
function db_multi_insert($db, $table, array $columns, array $data) {
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
	if ($db===null)
		$db= db_get_connection();
	$stm= "DELETE FROM :table";
	if (!$conditions!==null){
		$conditions=_db_build_where($conditions);
		$stm+=' '.$conditions[0];
	}else{$conditions=array();}
	$conditions[1][':table']=$table;
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_isdebug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	if(!$stm->execute($conditions[1])){
		return db_log_error($stm,$conditions);
	}else{
		return false;
	}
}
/**
 * Enter description here ...
 * @param resource $db
 * @param string $table
 * @param array $conditions see _db_build_where(...)
 * @return mixed false on error or the count.
 */
function db_num_rows($db,$table,array $conditions=null){
	if ($db===null)
		$db= db_get_connection();
	if($conditions===null){
		$stm = 'SELECT COUNT(*) FROM :table';
		$conditions=array();
	}else{
		$conditions=_db_build_where($conditions);
		$stm = 'SELECT COUNT(*) FROM :table WHERE '.$conditions[0];
	}
	$conditions[1][':table']=$table;
	try{
		$stm = $db->prepare($stm);
	}catch(PDOException $e){
		if(db_isdebug()){
			throw $e;
		}else{
			return 'Could not prepare the statement.';
		}
	}
	if (!$stm->execute($conditions[1])) {
		return db_log_error($stm,array_merge(array(':table'=>$table),$conditions[1]));
	}
	$ret = $stm->fetch(PDO::FETCH_NUM);
	$stm->closeCursor();
	return $ret[0];
}