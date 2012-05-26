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

$where = array(array('session', 'IN', 'SELECT `session` FROM botvisit WHERE page_id=\''.$data['page_id'].'\' AND agent_id=\''.$data['agent_id'].'\'', false));
if (isset($data['start'])) {
	$where[0][] = 'AND';
	$where[] = array('dtime', 'BETWEEN', $data['start'], $data['end'], false);
}
$days=db_query($db, 'botvisits','HOUR(dtime), count(`session`)',$where, array(array('TIME(dtime)', 'ASC')), 'HOUR(dtime)');

require 'counter_chart_hourly.php';
?>