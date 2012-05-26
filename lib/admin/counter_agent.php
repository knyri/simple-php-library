<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
?>
<strong>User-Agent</strong><br />
<p>
Shows which browsers have been used. Has no practical purpose outside developing.
</p>
<table>
	<tr><th width="80%">Agent String</th><th width="5%">Count</th><th width="15%">Created</th></tr>
<?php
$agents = db_query($db, 'user_agent', '*', array(array('LITERAL', 'agent_id NOT IN (SELECT agent_id FROM bots)')), array(array('agent_count', 'DESC')));
while ($agent = mysql_fetch_array($agents, MYSQL_ASSOC)) {?>
	<tr>
		<td valign="top"><?php echo $agent['agent_string']; ?></td>
		<td valign="top"><?php echo $agent['agent_count']; ?></td>
		<td valign="top"><?php echo $agent['created']; ?></td>
	</tr>
<?php
}
?>
</table>