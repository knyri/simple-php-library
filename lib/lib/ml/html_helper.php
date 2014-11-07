<?php
/**
 * @package markup_language
 */
/**
 * Takes a key=>value array and creates HTML tag attibutes
 * @param array $attrib
 * @return string
 */
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
/**
 * Simple class to build a simple link.
 * @author Ken
 *
 */
class Link{
	private
	$arguments,
	$link;
	public function __construct(){
		$this->arguments=new PropertyList();
	}
	public function addArgs(array $args){
		$this->arguments->setAll($args);
	}
	public function setArg($key,$value){
		$this->arguments->set($key,$value);
	}
	public function getArg($key){
		return $this->arguments->get($key);
	}
	public function setLink($l){$this->link=$l;}
	public function toString(){
		$args='';
		foreach($this->arguments->copyTo(array()) as $k=>$v)
			$args.=urlencode($k).'='.urlencode($v).'&amp;';
		return $this->link.'?'.substr($args, 0,-5);
	}
}
/**
 * Class for paginating.
 * Don't forget to set $offsetvar and $perpagevar to the ones that you use.
 *
 * @author Ken
 *
 */
class Pagination{
	private $link,
	$perpage,
	$offset,
	$totalitems;
	public $offsetvar='offset',
	$perpagevar='perpage',
	$maxnumbers=12;
	public function __construct($page='',array $args=array(),$perpage,$offset,$totalitems){
		$this->link=new Link;
		$this->link->setLink($page);
		$this->link->addArgs($args);
		$this->perpage=$perpage;
		$this->offset=$offset;
		$this->totalitems=$totalitems;
	}
	public function generate(){
		$pagecount=ceil($this->totalitems/$this->perpage);
		if($pagecount<2)return '';
		$pagelinks='';
		$this->link->setArg($this->perpagevar,$this->perpage);
		if($this->offset>0){
			$this->link->setArg($this->offsetvar,0);
			$pagelinks.='<a href="'.$this->link->toString().'">&laquo;</a> ';
			$this->link->setArg($this->offsetvar,$this->offset-$this->perpage);
			$pagelinks.='<a href="'.$this->link->toString().'">&lsaquo;</a> ';
		}
		$curpage=ceil($this->offset/$this->perpage);
		if($pagecount>$this->maxnumbers){
			if($curpage<$this->maxnumbers/2){
				//output 1 through $this->maxnumbers
				for($i=0;$i<$this->maxnumbers;$i++){
					$this->link->setArg($this->offsetvar,$i*$this->perpage);
					$pagelinks.='<a href="'.$this->link->toString().'">'.($i+1).'</a> ';
				}
			}else{
				//output $this->maxnumbers/2-1 before the current page and $this->maxnumbers/2 after
				$i=$curpage-1-$this->maxnumbers/2;
				for(;$i<$curpage;$i++){
					$this->link->setArg($this->offsetvar,$i*$this->perpage);
					$pagelinks.='<a href="'.$this->link->toString().'">'.($i+1).'</a> ';
				}
				echo $curpage.' ';
				$end=$curpage+$this->curpage/2;
				for($i=$curpage+1;$i<$end;$i++){
					$this->link->setArg($this->offsetvar,$i*$this->perpage);
					$pagelinks.='<a href="'.$this->link->toString().'">'.($i+1).'</a> ';
				}
			}
		}else{
			//output 1 to $pagecount
			for($i=0;$i<$pagecount;$i++){
				$this->link->setArg($this->offsetvar,$i*$this->perpage);
				$pagelinks.='<a href="'.$this->link->toString().'">'.($i+1).'</a> ';
			}
		}
		if($curpage<$pagecount){
			$this->link->setArg($this->offsetvar,$this->offset+$this->perpage);
			$pagelinks.='<a href="'.$this->link->toString().'">&rsaquo;</a> ';
			$this->link->setArg($this->offsetvar,($pagecount-1)*$this->perpage);
			$pagelinks.='<a href="'.$this->link->toString().'">&raquo;</a> ';
		}
		return $pagelinks;
	}
}