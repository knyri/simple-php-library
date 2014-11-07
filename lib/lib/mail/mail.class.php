<?php
/**
 * @package mail
 * @author Ken
 *
 */

/**
 * Simplifies creating and sending email messages.
 * This class does NOT sanitize input and does NOT check for correctness.
 */
class mail{
	private $to=array(),
		$from=null,
		$message=null,
		$subject=null,
		$attachments=array(),
		$headers=array(),
		$ishtml=false,
		$generated=false,
		$cc=array(),
		$bcc=array();
	private static $httpctx;
	public function __construct(){
		$this->headers['MIME-Version']='1.0';
		if(!isset(self::$httpctx)){
			self::$httpctx=stream_context_create(array('http'=>array('method'=>'GET')));
		}
	}
	/**
	 * @param string $file Path to the file
	 * @param string $type MIME type of the file
	 * @param boolean $deleteonsend default is false
	 */
	public function addAttachment($file,$type,$deleteonsend=false){
		$this->attachments[$file]=array($type,$deleteonsend);
		$this->generated=false;
	}
	/**
	 * Add a recipient
	 * @param string $addr
	 */
	public function addTo($addr){
		if(is_array($this->to))
			$this->to[]=$addr;
		else
			$this->to.=','.$addr;
	}
	/**
	 * Sets the To address.
	 * @param string $to
	 */
	public function setTo($to){
		$this->to=$to;
	}
	/**
	 * Add a carbon copy recipient
	 * @param string $to
	 */
	public function addCC($to){
		$this->cc[]=$to;
	}
	/**
	 * Add a blind carbon copy recipient
	 * @param string $to
	 */
	public function addBCC($to){
		$this->bcc[]=$to;
	}
	/**
	 * Set the fron address
	 * @param string $from
	 */
	public function setFrom($from){
		$this->from=$from;
	}
	/**
	 * Set the subject
	 * @param string $subject
	 */
	public function setSubject($subject){
		$this->subject=$subject;
	}
	/**
	 * Set the message
	 * @param string $message
	 */
	public function setMessage($message){
		$this->message=$message;
		$this->generated=false;
	}
	/**
	 * The current message
	 * @return string
	 */
	public function getMessage(){return $this->message;}
	/**
	 * List of recipients
	 * @return array|string
	 */
	public function getTo(){return $this->to;}
	/**
	 * The current from address
	 * @return string
	 */
	public function getFrom(){return $this->from;}
	/**
	 * Get the value of the header named $name
	 * @param string $name
	 * @return string The value of the header or NULL
	 */
	public function getHeader($name){return isset($this->headers[$name])?$this->headers[$name]:null;}
	/**
	 * Set the value of the header named $name
	 * @param string $name
	 * @param string $value
	 */
	public function setHeader($name,$value){
		$this->headers[$name]=$value;
	}
	/**
	 * @param bolean $html If supplied, set the isHtml flag
	 * @return boolean
	 */
	public function isHtml($html=-1){
		if(!is_bool($html))return $this->ishtml;
		$this->ishtml=$html;
	}
	/**
	 * Attempts to send the mail. Uses the standard PHP mail(...) function.
	 * @return boolean true if the mail was sent or false.
	 */
	public function send(){
		if(is_array($this->to))
			$to=implode(',',$this->to);
		else
			$to=$this->to;
		if(count($this->attachments) && !$this->generated){
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
				if(substr($file,0,4)=='http'){
					$this->message .= "--{$mime_boundary}\n";
					$data = chunk_split(base64_encode(file_get_contents($file,false,self::$httpctx)));
					$this->message .= "Content-Type: $mime[0]; name=\"".basename($file)."\"\n" .
							"Content-Description: ".basename($file)."\n" .
							"Content-Disposition: attachment; filename=\"".basename($file)."\"; size=".strlen($data).";\n" .
							"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
				}else
				if(is_file($file)){
					$this->message .= "--{$mime_boundary}\n";
					$data = chunk_split(base64_encode(file_get_contents($file)));
					$this->message .= "Content-Type: $mime[0]; name=\"".basename($file)."\"\n" .
								"Content-Description: ".basename($file)."\n" .
								"Content-Disposition: attachment; filename=\"".basename($file)."\"; size=".strlen($data).";\n" .
								"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
					if($mime[1]===true)@unlink($file);
				}
			}
			$this->message .= "--{$mime_boundary}--";
			$this->generated=true;
		}elseif($this->ishtml){
			$this->headers['Content-Type']='text/html';
		}elseif(!isset($this->headers['Content-Type'])){
			$this->headers['Content-Type']='text/plain';
		}
		$headers='';
		if(isset($this->from))$headers.='From: '.$this->from."\r\n";
		if(count($this->bcc))$headers.='Bcc: '.implode(',',$this->bcc)."\r\n";
		if(count($this->cc))$headers.='Cc: '.implode(',',$this->cc)."\r\n";
		foreach($this->headers as $name=>$value){
			$headers.="$name: $value\r\n";
		}
		$headers=substr($headers,0,-2);
		return mail($to, $this->subject, $this->message,$headers);
	}
}