<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

$form = new HTML_form();

$uagent = false;
if ($data['cukoo']) {
	$uagent = $data['uagent'];
	unset($data['uagent']);
}
$form->addAllHidden($data);
$form->setMethod(HTML_form::METHOD_GET);
$form->addItem(array(HTML_form::INPUT_TEXT, 'User-Agent:', 'uagent', $uagent));
$form->setSubmit('submit', 'cukoo');
$form->output();
if ($uagent) {
	$error = db_record_exist($db, 'user_agent', 'agent_id=\''.md5($uagent).'\'');
	if ($error===false) {
		$error = db_insert($db, 'user_agent', array('agent_id'=>md5($uagent), 'agent_string'=>mysql_real_escape_string($uagent, $db), 'agent_count'=>0));
		if ($error)
			echo $error.'<br />';
	} elseif ($error!==true) {
		echo $error.'<br />';
	}
	$error = db_insert($db, 'bots', array('agent_id'=>md5($uagent)));
	if ($error)
		echo $error;
	else
		echo 'Added.';
}
?>