<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 * @package markup_language
 */
require_once 'html_helper.php';
/**
 *
 * Collection of functions to aid in creating an HTML page.
 * @author Kenneth Pierce
 */
class HTML {
	const MEOL="<br>"; //mark-up EOL
	private static $base_href='';
	/**
	 * Echos the HEAD tag with the given subtags.
	 * @param string $title The content of the TITLE tag.
	 * @param array $meta An array of array('attrib'=>'value',...)
	 * @param array $link An array of array('attrib'=>'value',...). Common attribute value pairs are
	 * 	rel=>stylesheet,href=>url,media=>media type
	 * @param string|array $base
	 * 	If a string the will set the base href.
	 * 	If an array it should be an array of ('attrib'=>'value',...)
	 * @param array $style An array of array('attrib'=>'value',...). A special index 'content' can be set to specify the content between the STYLE tags
	 * @param array $script An array of array('attrib'=>'value',...). A special index 'content' can be set to specify the content between the SCRIPT tags
	 * @param array $ie An array of array('lt'|'gt'|'eq'|'lte'|'gte'|'neq',version,link,style,script) where link,style, and script have the same format as $link,$style, and $script.
	 * 	All may be null but must be set.
	 * @param string $raw Raw output appended to the very end.
	 */
	public static function echo_header($title='',array $meta=null,array $link=null,$base=null,array $style=null,array $script=null,array $ie=null,$raw=null){
		$head=false;
		echo "<head><title>$title</title>";
		if($meta != null){
			foreach ($meta as $metaE){
				echo "<meta ".combine_attrib($metaE).' />';
			}
			unset($meta);
		}//end meta
		if($link!=null){
			foreach($link as $key => $value){
				if(!is_array($value)){
					echo "<link $key=\"$value\" />";
				}else{
					echo "<link ".combine_attrib($value).' />';
				}
			}
			unset($link);
		}//end link
		if($base != null){
			if(is_array($base)){
				foreach ($base as $key=>$value){
					echo "<base $key=\"$value\" />";
				}
			}else{
				echo "<base href=\"$base\" />";
			}
			unset($base);
		}//end base
		if($style != null){
			foreach ($style as $styleE){
				$cont=$styleE['content'];
				unset($styleE['content']);
				echo "<style ".combine_attrib($styleE).">$cont</style>";
			}
			unset($style);
		}//end style
		if($script != null){
			foreach ($script as $scriptE){
				if(isset($scriptE['content'])) $cont=$scriptE['content'];
				else $cont='';
				unset($scriptE['content']);
				echo "<script ".combine_attrib($scriptE).">$cont</script>";
			}
			unset($script);
		}//end script
		//IE specific
		if($ie!==null){
			foreach($ie as $iarr){
				list($comp,$version,$link,$style,$script)=$iarr;
				if($comp==null){
					if($version==null)
						echo '<!--[if IE]>';
					else
						echo '<!--[if IE '.$version.']>';
				}else echo '<!--[if '.$comp.' IE '.$version.']>';
				if($link!=null){
					foreach($link as $key=>$value){
						if(!is_array($value)){
							echo "<link $key=\"$value\" />";
						}else{
							echo "<link ".combine_attrib($value).' />';
						}
					}
					unset($link);
				}//end link
				if($style!=null){
					foreach($style as $styleE){
						$cont=$styleE['content'];
						unset($styleE['content']);
						echo "<style ".combine_attrib($styleE).">$cont</style>";
					}
					unset($style);
				}//end style
				if($script!=null){
					foreach($script as $scriptE){
						if(isset($scriptE['content']))$cont=$scriptE['content'];
						else $cont='';
						unset($scriptE['content']);
						echo "<script ".combine_attrib($scriptE).">$cont</script>";
					}
					unset($script);
				}//end script
				echo '<![endif]-->';
			}
		}
		echo $raw;
		echo '</head>';
	}
	public static function nav_bar($name,$id,$pre='',$post=''){
		echo "<div id=\"$id\">$pre";
		$puzzle=explode('/',substr($_SERVER['PHP_SELF'],1));
		$path="";
		echo '<a href="/">Home</a>';
		foreach ($puzzle as $piece){
			$path.="/$piece";
			echo " &rsaquo; <a href=\"$path\">$piece</a>";
		}
		echo "$post</div>";
	}
	public static function echo_footer(){
		echo '</html>';
	}
	public static function anchor($text, $link){
		return "<a href=\"".HTML::$base_href."$link\">$text</a>";
	}
	public static function anchor_ext($pre, $text, $post, $link){
		return "$pre<a href=\"".HTML::$base_href."$link\">$text</a>$post";
	}
	public static function anchor_back($text){
		return "<a href=\"javascript:history.go(-1);\">$text<prev /></a>";
	}
	public static function set_base_href($link){
		HTML::$base_href=$link;
	}
	public static function bold($text){
		return '<b>'.$text.'</b>';
	}
	public static function italic($text){
		return '<i>'.$text.'</i>';
	}
	public static function underline($text){
		return '<u>'.$text.'</u>';
	}
	function embed_refresh($location, $milli){
	?><script language="javascript">function goto_location(){document.location='<?php echo $location; ?>'}setTimeout('goto_location()',<?php echo $milli; ?>);</script>If your browser does not refresh in <?php echo $milli/1000.0; ?> seconds, click <a href="<?php echo $location; ?>">here</a>.<?php
	}
}
class HTML_Header{
	private $meta=array();
	private $link=array();
	private $style=array();
	private $script=array();
	private $title='';
	private $base=array();
	private $ie=array();
	private $raw='';
	public function setBaseHref($href){
		$this->base['href']=$href;
	}
	public function setBaseTarget($target){
		$this->base['target']=$target;
	}
	public function setTitle($title){
		$this->title=$title;
	}
	public function addMeta(array $attributes){
		$this->meta[]=$attributes;
	}
	public function addInlineStyle($content,$type='text/css',$media='screen',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['media']=$media;
		$attributes['content']=$content;
		$this->style[]=$attributes;
	}
	public function addLink(array $attributes){
		$this->link[]=$attributes;
	}
	public function addExternalStyle($href,$type='text/css',$media='screen',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['media']=$media;
		$attributes['href']=$href;
		if(!isset($attributes['rel']))$attributes['rel']='stylesheet';
		$this->link[]=$attributes;
	}
	public function addScript($content,$type='text/javascript',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['content']=$content;
		$this->script[]=$attributes;
	}
	public function addExternalScript($src,$type='text/javascript',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['src']=$src;
		$this->script[]=$attributes;
	}
	//+++++++++++++++++++++++IE SPECIFIC+++++++++++++++++++++++
	public function addMetaIE($version,$comparator,array $attributes){
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['meta'][]=$attributes;
	}
	public function addInlineStyleIE($version,$comparator,$content,$type='text/css',$media='screen',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['media']=$media;
		$attributes['content']=$content;
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['style'][]=$attributes;
	}
	public function addLinkIE($version,$comparator,array $attributes){
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['link'][]=$attributes;
	}
	public function addExternalStyleIE($version,$comparator,$href,$type='text/css',$media='screen',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['media']=$media;
		$attributes['href']=$href;
		if(!isset($attributes['rel']))$attributes['rel']='stylesheet';
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['link'][]=$attributes;
	}
	public function addScriptIE($version,$comparator,$content,$type='text/javascript',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['content']=$content;
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['script'][]=$attributes;
	}
	public function addExternalScriptIE($version,$comparator,$src,$type='text/javascript',array $attributes=array()){
		$attributes['type']=$type;
		$attributes['src']=$src;
		if($version==null)$version='all';
		if($comparator==null)$comparator='equal';
		$this->ie[$version][$comparator]['script'][]=$attributes;
	}
	/**
	 * Outputs the header to the output buffer and resets the class.
	 */
	public function output(){
		$ending=' />';
		echo "<head><title>$title</title>";
		foreach($this->meta as $metaE){
			echo '<meta '.combine_attrib($metaE).$ending;
		}
		unset($this->meta);

		foreach($this->link as $key=>$value){
			if(!is_array($value)){
				echo "<link $key=\"$value\"$ending";
			}else{
				echo '<link '.combine_attrib($link).$ending;
			}
		}
		unset($this->link);

		foreach($this->base as $key=>$value){
			echo "<base $key=\"$value\"$ending";
		}
		unset($this->base);

		foreach($this->style as $styleE){
			$cont=isset($styleE['content'])?$styleE['content']:'';
			unset($styleE['content']);
			echo '<style '.combine_attrib($styleE).">$cont</style>";
		}
		unset($this->style);

		foreach($this->script as $scriptE){
			$cont=isset($scriptE['content'])?$scriptE['content']:'';
			unset($scriptE['content']);
			echo '<script '.combine_attrib($scriptE).">$cont</script>";
		}
		unset($this->script);

		//IE specific
		foreach($this->ie as $version=>$varray){
			foreach($varray as $comp=>$iarr){
				$meta=isset($iarr['meta'])?$iarr['meta']:array();
				$link=isset($iarr['link'])?$iarr['link']:array();
				$style=isset($iarr['style'])?$iarr['style']:array();
				$script=isset($iarr['script'])?$iarr['script']:array();
				if($comp=='equal'){
					echo ($version=='all')?'<!--[if IE]>':"<!--[if IE $version]>";
				}else echo "<!--[if $comp IE $version]>";
				foreach($meta as $metaE){
					echo '<meta '.combine_attrib($metaE).$ending;
				}
				foreach($link as $linkE){
					echo '<link '.combine_attrib($linkE).$ending;
				}
				unset($link);

				foreach($style as $styleE){
					$cont=isset($styleE['content'])?$styleE['content']:'';
					unset($styleE['content']);
					echo '<style '.combine_attrib($styleE).">$cont</style>";
				}
				unset($style);

				foreach($script as $scriptE){
					$cont=isset($scriptE['content'])?$scriptE['content']:'';
					unset($scriptE['content']);
					echo '<script '.combine_attrib($scriptE).">$cont</script>";
				}
				unset($script);
				echo '<![endif]-->';
			}
		}

		echo $this->raw;
		unset($this->raw);
		unset($this->ie);
		echo '</head>';
	}
}