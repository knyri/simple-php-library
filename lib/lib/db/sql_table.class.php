<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package database
 */


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
			$sort = $this->select_columns[0];
		}
		$sql = 'SELECT SQL_CACHE ';
		$sql .= implode(',', $this->select_columns);
		//limit [offset,]row count
		if ($conditions == '' || $conditions == null)
			$sql .= " FROM $this->table";
		else
			$sql .= " FROM $this->table WHERE $conditions";
		$sql .= " ORDER BY $sort $sortDir LIMIT $start, $numrows";
		if(db_isDebug())echo $sql;
		$res = mysql_query($sql, $db);
		if (!$res) {
			$err = db_log_error(mysql_error(), $sql);
			return 'Database error: '.$err;
		}
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
		while ($row = mysql_fetch_array($res)) {
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
		if(db_isDebug())echo $sql;
		$res = mysql_query($sql, $db);
		if (!$res) {
			$err = db_log_error(mysql_error(), $sql);
			return 'Database error: '.$err;
		}
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
		while ($row = mysql_fetch_array($res)) {
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
		$pageLinks .= '<a href="?'.$prefix.'start=0&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_first.png" /></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.($currentRow-$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_previous.png" /></a>';
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
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($topPage-1)*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_next.png" /></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($cPages-1)*$rowsPerPage).'&amp;'.$prefix.'numrows='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_last.png" /></a>';
	}
	return $pageLinks;
} // -- getPages --