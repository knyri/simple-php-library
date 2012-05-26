<?php

class mail{
	private $to=null,
		$from=null,
		$message=null,
		$subject=null,
		$attachments=array(),
		$headers=array(),
		$ishtml=false;
	public function __construct(){
		$this->headers['MIME-Version']='1.0';
	}
	public function addAttachment($file,$type,$deleteonsend=false){
		$this->attachments[$file]=array($type,$deleteonsend);
	}
	public function setTo($to){
		$this->to=$to;
	}
	public function setFrom($from){
		$this->from=$from;
	}
	public function setSubject($subject){
		$this->subject=$subject;
	}
	public function setMessage($message){
		$this->message=$message;
	}
	public function setHeader($name,$value){
		$this->headers[$name]=$value;
	}
	public function isHtml($html=-1){
		if(!is_bool($html))return $this->ishtml;
		$this->ishtml=$html;
	}
	public function send(){
		if(count($this->attachments)){
			$mime_boundary=md5(time());
			$this->headers['Content-Type']="multipart/mixed; boundary=\"{$mime_boundary}\"";
			$mtmp=$this->message;
			$this->message="--{$mime_boundary}\n";
			if($this->ishtml)
				$this->message.="Content-Type: text/html\n\n";
			else
				$this->message.="Content-Type: text/plain\n\n";
			$this->message.=trim($mtmp)."\n";
			unset($mtmp);
			foreach($this->attachments as $file=>$mime){
				if(is_file($file)){
					$this->message .= "--{$mime_boundary}\n";
					$fp =		@fopen($file,"rb");
					$data =		@fread($fp,filesize($file));
					@fclose($fp);
					$data = chunk_split(base64_encode($data));
					$this->message .= "Content-Type: $mime[0]; name=\"".basename($file)."\"\n" .
								"Content-Description: ".basename($file)."\n" .
								"Content-Disposition: attachment; filename=\"".basename($file)."\"; size=".filesize($file).";\n" .
								"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
					if($mime[1]===true)@unlink($file);
				}
			}
			$this->message .= "--{$mime_boundary}--";
		}
		$headers="";
		foreach($this->headers as $name=>$value){
			$headers.="$name: $value\r\n";
		}
		$headers=substr($headers,0,-2);
		return mail($this->to, $this->subject, $this->message,$headers);
	}
}