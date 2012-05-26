<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
include_once 'counter_crumb_pagenav.php';
$page = db_get_column($db, 'pcpages', 'page', "page_id='$data[page]'", -1);
if ($page === -1) { echo 'Could not find the page.'; break;}
echo '<h2>'.$page.'</h2><br />';
$exitpages;
$entrancepages = db_query($db, 'referrer_visit left join referrers on (referrer_visit.referrer_id=referrers.referrer_id)', 'referrer_url, rcount', "page_id='$data[page]'", array(array('rcount', 'DESC')));
?>
<h3>Entrance from pages</h3>
<table style="table-layout:fixed;" width="100%">
	<tr><th width="80%">Page</th><th width="10%">Link</th><th width="10%">Count</th></tr>
<?php
while ($referer = mysql_fetch_array($entrancepages, MYSQL_ASSOC)) {
?>
	<tr>
		<td><div class="oh"><?php echo $referer['referrer_url']; ?></div></td>
		<td><a target="_blank" href="<?php echo $referer['referrer_url']; ?>">visit</a></td>
		<td><?php echo $referer['rcount']; ?></td>
	</tr>
<?php
}
?>
</table>
<?php
$exitpages = db_query($db, 'referrer_visit left join pcpages on (referrer_visit.page_id=pcpages.page_id)', 'pcpages.page_id AS page_id, page, rcount', 'referrer_id=\''.md5("http://$_SERVER[SERVER_NAME]$page").'\'', array(array('rcount','DESC')));
?>
<h3>Exit to pages</h3>
<table style="table-layout:fixed;" width="100%">
	<tr><th width="80%">Page</th><th width="10%">Link</th><th width="10%">Count</th></tr>
<?php
while ($referer = mysql_fetch_array($exitpages, MYSQL_ASSOC)) {
?>
	<tr>
		<td><div class="oh"><?php echo $referer['page']; ?></div></td>
		<td><a target="_blank" href="<?php echo $referer['page']; ?>">visit</a> <a href="?page=<?php echo $referer['page_id']; ?>&amp;action=view">stats</a></td>
		<td><?php echo $referer['rcount']; ?></td>
	</tr>
<?php
}
?>
</table>
<?php
$visitors = db_query($db, 'pcvisit', '*', array(array('page_id', $data['page'])), array(array('DATE(dtime)', 'DESC'),array('TIME(dtime)','DESC')));
?>
<table style="table-layout:fixed;" width="100%">
	<tr><th width="70%">Visitor</th><th width="20%">Last visit</th><th width="10%">Count</th></tr>
<?php
while ($visitor = mysql_fetch_array($visitors, MYSQL_ASSOC)) {
?>
	<tr>
		<td><?php echo $visitor['address']; ?></td>
		<td><?php echo $visitor['dtime']; ?></td>
		<td><?php echo $visitor['scount']; ?></td>
	</tr>
<?php
}
?>
</table>