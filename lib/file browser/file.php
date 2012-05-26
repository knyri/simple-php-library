<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
?>
<table>
<tr><th>icon</th><th>name</th><th>size</th></tr>
<?php
function formatBytes($b,$p = null) {
    $units = array("B","kB","MB","GB","TB","PB","EB","ZB","YB");
    $c=0;
    if(!$p && $p !== 0) {
        foreach($units as $k => $u) {
            if(($b / pow(1024,$k)) >= 1) {
                $r["bytes"] = $b / pow(1024,$k);
                $r["units"] = $u;
                $c++;
            }
        }
        return number_format($r["bytes"],2) . " " . $r["units"];
    } else {
        return number_format($b / pow(1024,$p)) . " " . $units[$p];
    }
}
foreach ($files as $file) {
	?>
	<tr>
		<td><?php
		$pos = strpos($file, '.');
		if ($pos===FALSE) {
			echo " ";
		} else {
			?><img src="/images/icons/file/<?php echo substr($file, strpos($file, '.')+1); ?>.png" alt="" /><?php
		}
		?></td>
		<td>
			<a href="<?php echo $file; ?>"><?php echo $file; ?></a>
		</td>
		<td><?php echo formatBytes(@filesize($path.'/'.$file)); ?></td>
	</tr>
	<?php
}
?>
</table>