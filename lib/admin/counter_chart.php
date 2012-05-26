<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
include_once 'counter_crumb_pagenav.php';
$start = null;
$end = null;
?>
<form>
<?php
$page = db_get_column($db, 'pcpages', 'page', "page_id='$data[page]'", -1);
if (isset($data['start'])) {
	$start = $data['start']['year'] . '-' . $data['start']['month'] . '-' . $data['start']['day'] . ' 00:00:00';
	$end = $data['end']['year'] . '-' . $data['end']['month'] . '-' . $data['end']['day'] . ' 00:00:00';
}
echo '<h2>'.$page.'</h2><br />';
echo 'Range: ';
form_create_date('start', $data['start']);
echo ' - ';
form_create_date('end', $data['end']);
unset($data['start']);
unset($data['end']);
form_create_hidden_from_array($data);
?>
<input type="submit" />
</form>
<table>
	<thead>
		<tr><th>Week-day</th><th>Day</th><th>Hour</th></tr>
	</thead>
	<tbody>
		<tr>
			<td valign="top"><img src="/admin/counter_daily_img.php?page=<?php echo $data['page']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
			<td valign="top"><img src="/admin/counter_daily_graph.php?page=<?php echo $data['page']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
			<td valign="top"><img src="/admin/counter_hourly_chart.php?page=<?php echo $data['page']; ?><?php if (!empty($start)) echo '&start='.$start.'&end='.$end;?>" /></td>
		</tr>
	</tbody>
</table>