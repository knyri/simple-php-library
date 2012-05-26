<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

if (!defined('EOL')) define('EOL', "\n");
if (!defined('TAB')) define('TAB', "\t");
class result_table {
	private $headers = array();
	private $fetchtype = MYSQL_BOTH;
	/**
	 * Adds a table column.
	 * @param string|int $tcolumn Either the column name or the column index
	 * @param string $display Header text.
	 */
	public function addHeader($tcolumn, $display) {
		$this->headers[] = array($tcolumn, $display);
	}
	public function setFetchType($fetchtype) {
		$this->fetchtype = $fetchtype;
	}
	public function output($result) {
		echo '<table>'.EOL;
		$headcount = count($this->headers);
		echo TAB.'<tr>'.EOL;
		for ($i = 0; $i < $headcount; $i++) {
			echo TAB.TAB.'<th>'.$this->headers[$i].'</th>'.EOL;
		}
		echo TAB.'</tr>'.EOL;
		while ($row = mysql_fetch_array($result, $this->fetchtype)) {
			echo TAB.'<tr>'.EOL;
			for ($i = 0; $i < $headcount; $i++) {
				echo TAB.TAB.'<td>'.$row[$this->headers[$i]].'</td>'.EOL;
			}
			echo TAB.'</tr>'.EOL;
		}
		echo '</table>'.EOL;
	}
}
?>