<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

PackageManager::includeFunctionOnce('lib.db.database');
$db = db_get_connection();
$res = db_query($db, 'errors');
?>
<table width="100%">
	<tr><th>error</th><th>date</th><th>message</th><th>query</th></tr>
<?php
while ($row = mysql_fetch_array($res)) {
?>
	<tr>
		<td><?php echo $row['err_id']; ?></td>
		<td><?php echo $row['err_date']; ?></td>
		<td><?php echo $row['err_msg']; ?></td>
		<td><?php echo $row['err_query']; ?></td>
	</tr>
<?php
}
db_close_connection();
?>
</table>