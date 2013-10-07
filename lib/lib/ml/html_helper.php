<?php
function combine_attrib(array $attrib=null){
	if($attrib==null)return '';
	$res='';
	foreach($attrib as $key=>$value){
		$res.=$key.'="'.$value.'" ';
	}
	return trim($res);
}
function paginate($totalRows,$currentRow,$rowsPerPage,$extra='',$prefix=''){
	$cPages=ceil($totalRows/$rowsPerPage);
	if($cPages == 1){return ' ';}
	$pageLinks = '';
	if ($currentRow > 0) {
		$pageLinks .= '<a href="?'.$prefix.'start=0&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_first.png" alt="first page"></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.($currentRow-$rowsPerPage).'&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_previous.png" alt="previous page"></a>';
	}
	$page = $currentRow/$rowsPerPage;
	$cPage = $page;
	$topPage = $page+2;
	$page -= 2;
	if ($page < 0) { $page = 0; }
	for (; $page <= $topPage && $page < $cPages; $page++) {
		if ($page == $cPage)
			$pageLinks .= ' '.($page+1);
		else
			$pageLinks .= ' <a href="?'.$prefix.'start='.($page*$rowsPerPage).'&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'">'.($page+1).'</a>';
	}

	if ($topPage-1 < $cPages) {
		if ($topPage < $cPages-1)
			$pageLinks .= ' ... <a href="?'.$prefix.'start='.(($cPages-1)*$rowsPerPage).'&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'">'.$cPages.'</a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($topPage-1)*$rowsPerPage).'&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_next.png" alt="next page"></a>';
		$pageLinks .= ' <a href="?'.$prefix.'start='.(($cPages-1)*$rowsPerPage).'&amp;'.$prefix.'rpp='.$rowsPerPage.$extra.'"><img src="/lib/i/resultset_last.png" alt="last page"></a>';
	}
	return $pageLinks;
}