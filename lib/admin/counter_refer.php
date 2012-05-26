<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
include_once 'counter_crumb_pagenav.php';
$page = db_get_column($db, 'pcpages', 'page', "page_id='$data[page]'", -1);
if ($page === -1) { echo 'Could not find the page.'; break;}
echo '<h2>'.$page.'</h2><br />';
$referrers = db_query($db, 'referrer_visit left join referrers on (referrer_visit.referrer_id=referrers.referrer_id)', 'referrer_url, rcount', "page_id='$data[page]'");;
?>
<table style="table-layout:fixed;" width="100%">
	<tr><th width="80%">Page</th><th width="10%">Link</th><th width="10%">Count</th></tr>
<?php
while ($referer = mysql_fetch_array($referrers, MYSQL_ASSOC)) {
?>
	<tr>
		<td><div class="oh"><?php echo $referer['referrer_url']; ?></div></td>
		<td><a href="<?php echo $referer['referrer_url']; ?>">visit</a></td>
		<td><?php echo $referer['rcount']; ?></td>
	</tr>
<?php
}
?>
</table>