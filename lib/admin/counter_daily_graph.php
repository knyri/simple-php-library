<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//select count(session) from pcvisit group by dtime;

require_once $_SERVER['DOCUMENT_ROOT'].'/libconfig.inc.php';
//require_once LIB.'lib/db/functions_database.inc.php';
//require_once LIB.'lib/ml/class_html.inc.php';
PackageManager::requireFunctionOnce('lib.db.database');
PackageManager::requireClassOnce('lib.ml.html');
$data = $_GET;
$db = getConnection();
if(!empty($GLOBALS['simple']['lib']['util']['counter']['database']))
	mysql_select_db($GLOBALS['simple']['lib']['util']['counter']['database'], $db);

$where = array(array('session', 'IN', 'SELECT session FROM pcvisit WHERE page_id=\''.$data['page'].'\'', false));
if (isset($data['start'])) {
	$where[0][] = 'AND';
	$where[] = array('dtime', 'BETWEEN', $data['start'], $data['end'], false);
}
$days=db_query($db, 'pcvisits','DATE(dtime), count(`session`)',$where, array(array('dtime', 'ASC')), 'DATE(dtime)');

require 'counter_chart_daily.php';
?>