<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//require_once LIB.'lib/db/functions_database.inc.php';
//require_once LIB.'lib/ml/class_html.inc.php';
//require_once LIB.'lib/ml/functions_form.inc.php';
PackageManager::requireFunctionOnce('lib.db.database');
PackageManager::requireClassOnce('lib.ml.html');
PackageManager::requireFunctionOnce('lib.ml.form');
?>
<style type="text/css">
td { padding: 2px; border: 1px solid black; }
td.url { width: 80%; }
.oh { display: block; width: 100%; overflow: hidden; }
</style>
<?php
if (isset($_SERVER['HTTP_REFERER'])) {
	echo '<a href="'.$_SERVER['HTTP_REFERER'].'">Go Back</a><br />';
}
?>
<a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>">Home</a> | <a href="?action=agent">User-Agents</a> | <a href="?action=addbot">Add Bot</a> | <a href="?action=bots">Bots</a><hr />
<?php
$db = getConnection();
if(!empty($GLOBALS['simple']['lib']['util']['counter']['database']))
	mysql_select_db($GLOBALS['simple']['lib']['util']['counter']['database'], $db);
if (isset($_POST['action']))
	$data = $_POST;
else
	$data = $_GET;
//var_dump($_POST);
switch ($data['action']) {
	case 'view':
		include 'counter_view.php';
		break;
	case 'chart':
		include 'counter_chart.php';
		break;
	case 'refer':
		include 'counter_refer.php';
		break;
	case 'reset':
		include 'counter_reset.php';
		break;
	case 'bots':
		include 'counter_bots.php';
		break;
	case 'agent':
		include 'counter_agent.php';
		break;
	case 'addbot':
		include 'counter_addbot.php';
		break;
	default:
?>
<table>
	<tr><th>page</th><th>views</th><th>actions</th></tr>
	<?php
	$pages = db_query($db, 'pcpages', '*', null, array(array('vcount', 'DESC')));//array(array('LENGTH(page)', 'ASC'), array('page', 'ASC')));
	if ($pages) {
		while ($page = mysql_fetch_array($pages, MYSQL_ASSOC)) { ?>
		<tr>
			<td><?php echo $page['page']; ?></td>
			<td><?php echo $page['vcount'];?></td>
			<td>
				<form method="GET">
				 <input type="hidden" name="page" value="<?php echo $page['page_id']; ?>" />
				 <input type="submit" name="action" value="reset" />
				 <input type="submit" name="action" value="view" />
				 <input type="submit" name="action" value="refer" />
				 <input type="submit" name="action" value="chart" />
				</form>
			</td>
<?php	}
	}
?>
</table>
<?php
}
db_close_connection();
?>