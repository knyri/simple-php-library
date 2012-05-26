<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

session_start();
?>
	<form method="POST">
		<table>
			<tr>
				<td>
	<?php
			echo 'DB:</td><td>';
			echo '<input type=text name=\'db\' value=\''.$_POST['db'].'\'>';
			echo '</td></tr><tr><td>Address:</td><td>';
			echo '<input type=text name=\'ip\' value=\''.$_POST['ip'].'\'>';
			echo '</td></tr><tr><td>Username:</td><td>';
			echo '<input type=text name=\'user\' value=\''.$_POST['user'].'\'>';
			echo '</td></tr><tr><td>Password:</td><td>';
			echo '<input type=password name=\'pass\' value=\''.$_POST['pass'].'\'>';
	?>
			</td>
		</tr>
	</table>
	<br>
	Query:<br>
		<textarea name="query" cols="50" rows="5"><?= $_POST['query'] ?></textarea>
	<br>
		<input type="submit">
	</form>
<?php
if (isset($_POST['query'])) {
	$db = mysql_connect($_POST['ip'],$_POST['user'],$_POST['pass']);
	if (isset($_POST['db']))
		mysql_select_db($_POST['db'], $db);
	$query = str_replace('\\\'', '\'',$_POST['query']);
	$result = mysql_query($query, $db);
?>
<br>
Result:<br>
<?php
	if (!$result) {
		echo 'Query failed.<br>';
		echo mysql_error();
	} else
		echo 'Success!<br>';
	$query=explode(' ',$_POST['query']);
	$resultcmds = array(array('select','show', 'desc', 'describe'), array('insert', 'update','replace','delete'));
	if (in_array($query[0], $resultcmds[0])) {
		echo '<table border="1" width="750" cellspacing="0" bordercolorlight="#0066FF" bordercolordark="#0066FF" style="border-collapse: collapse" id="table1" bordercolor="#0066FF">';
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
				$color = "#CCFFFF";
			else
				$color = "#66FFFF";
			echo "<tr>";
			foreach($row as $col) {
				if ($index%2 != 0)
					echo "<td bgcolor='$color'>$col</td>";
				$index++;
			}
			$rowc++;
			echo "</tr>";
		}
		echo '</table>';
	} else {// if (in_array($query[0], $resultcmds[1])) {
		echo mysql_affected_rows().' rows affected.';
	}
	mysql_close($db);
}
?>
