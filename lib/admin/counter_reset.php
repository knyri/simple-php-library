<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
include_once 'counter_crumb_pagenav.php';
if (!isset($_GET['really'])) {
?>Really? <a href="?action=reset&amp;really=yes&amp;page=<?php echo $_GET['page']; ?>">yes</a> <?php
} else {
	db_delete($db, 'pcpages', array(array('page_id',$data['page'])));
}
?>