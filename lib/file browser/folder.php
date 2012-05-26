<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
?>
<ul id="folderlist">
<?php
foreach ($folders as $folder) {
		?>
	<li><a href="<?php echo 'index.php?path='.$path.'/'.$folder; ?>"><?php echo $folder; ?></a></li>
		<?php
}
?>
</ul>