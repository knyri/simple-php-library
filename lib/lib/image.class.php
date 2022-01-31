<?php
class gd_image{
	private $info=null;
	private $file=null;
	private $resource=null;
	public function __construct($file){
		$this->info=getimagesize($file);
		$this->file=$file;
		$mime=explode('/',$this->info['mime']);
		if($mime[1]=='jpeg'||$mime[1]=='jpg'){
			$this->resource=imagecreatefromjpeg($file);
		}elseif($mime[1]=='gif'){
			$this->resource=imagecreatefromgif($file);
		}elseif($mime[1]=='png'){
			$this->resource=imagecreatefrompng($file);
		}
	}
}