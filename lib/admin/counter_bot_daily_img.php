<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/libconfig.inc.php';
PackageManager::requireFunctionOnce('lib.db.database');
PackageManager::requireFunctionOnce('lib.time.date');
PackageManager::requireClassOnce('lib.ml.html');
//require_once LIB.'lib/db/functions_database.inc.php';
//require_once LIB.'lib/ml/class_html.inc.php';
//require_once LIB.'lib/time/date_functions.php';
$data = $_GET;
$start = null;
$end = null;


if (isset($data['start'])) {
	$start = $data['start'];//$data['start']['year'] . '-' . $data['start']['month'] . '-' . $year['start']['day'] . ' 00:00:00';
	$end = $data['end'];//$data['end']['year'] . '-' . $data['end']['month'] . '-' . $year['end']['day'] . ' 00:00:00';
}
$db = getConnection();
if(!empty($GLOBALS['simple']['lib']['util']['counter']['database'])) {
	mysql_select_db($GLOBALS['simple']['lib']['util']['counter']['database'], $db);
}

$where = array(array('session', 'IN', 'SELECT `session` FROM botvisit WHERE page_id=\''.$data['page_id'].'\' AND agent_id=\''.$data['agent_id'].'\'', false));
if (isset($data['start'])) {
	$where[0][] = 'AND';
	$where[] = array('dtime', 'BETWEEN', $data['start'], $data['end'], false);
}
$days=db_query($db, 'botvisits','DAYOFWEEK(dtime), count(`session`)',$where, array(array('dtime', 'ASC')), 'DAYOFWEEK(dtime)');

require 'counter_chart_weekday.php';
?>