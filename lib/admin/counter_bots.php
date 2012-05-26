<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
?>
<a href="?action=bots">Bot Index</a><br />
<strong>Bots</strong><br />
<p>
Shows which bots have been browsing the site.
</p>
<?php
if (isset($data['agent_id'])) {
	$agent = db_get_column($db, 'user_agent', 'agent_string', "agent_id='$data[agent_id]'");
	if ($agent) {
		echo '<h1>'.$agent.'</h1>';
		if (isset($data['page_id'])) {
			$page = db_get_column($db, 'pcpages', 'page', "page_id='$data[page_id]'");
			if ($page) {
				echo '<h2>'.$page.'</h2>';
?>
<table>
	<thead><tr><th>Week-day</th><th>Day</th><th>Hour</th></tr></thead>
	<tbody>
		<tr>
			<td valign="top"><img src="/admin/counter_bot_daily_img.php?agent_id=<?php echo $data['agent_id']; ?>&amp;page_id=<?php echo $data['page_id']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
			<td valign="top"><img src="/admin/counter_bot_daily_graph.php?agent_id=<?php echo $data['agent_id']; ?>&amp;page_id=<?php echo $data['page_id']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
			<td valign="top"><img src="/admin/counter_bot_hourly_chart.php?agent_id=<?php echo $data['agent_id']; ?>&amp;page_id=<?php echo $data['page_id']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
		</tr>
	</tbody>
</table>
<?php
			} else {
				echo 'Page not found.';
			}
		} else {//post page id check
			$pages = db_query($db, 'botvisit LEFT JOIN pcpages ON (botvisit.page_id=pcpages.page_id)', 'pcpages.page_id, agent_id, page, scount', array(array('agent_id',$data['agent_id'])));
			if ($pages) {
?>	<table width="100%">
	<tr><th width="80%">page</th><th width="5%">views</th><th width="15%">actions</th></tr>
	<?php
			while ($page = mysql_fetch_array($pages, MYSQL_ASSOC)) { ?>
	<tr>
		<td><?php echo $page['page']; ?></td>
		<td><?php echo $page['scount'];?></td>
		<td>
			<form method="GET">
				<input type="hidden" name="page_id" value="<?php echo $page['page_id']; ?>" />
				<input type="hidden" name="agent_id" value="<?php echo $data['agent_id']; ?>" />
				<input type="hidden" name="action" value ="bots" />
				<input type="submit" value="charts" />
			</form>
		</td>
	<?php		}//end while
			}//page list result check
?>
</table>
<?php
		}//end post page id
	} else {//agent check
		echo 'Agent not found.';
	}
} else {//post agent check
?>
<table>
	<tr><th width="90%">Agent String</th><th width="10%">Count</th></tr>
<?php
	$agents = db_query($db, 'user_agent', '*', array(array('LITERAL', 'agent_id IN (SELECT agent_id FROM bots)')), array(array('agent_count', 'DESC')));
	while ($agent = mysql_fetch_array($agents, MYSQL_ASSOC)) {
?>	<tr>
		<td><a href="?action=bots&amp;agent_id=<?php echo $agent['agent_id']; ?>"><?php echo $agent['agent_string']; ?></a></td>
		<td><?php echo $agent['agent_count']; ?></td>
	</tr>
<?php
	}
}
?>
</table>