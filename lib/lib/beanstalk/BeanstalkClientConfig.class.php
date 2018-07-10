<?php

class BeanstalkClientConfig{
	private
		$host,
		$port,
		$watch= array(),
		$use,
		$ignoreDefault= false;
	public function __construct($host, $port= 11300){
		$this->host= $host;
		$this->port= $port;
	}
	public function getHost(){
		return $this->host;
	}
	public function getPort(){
		return $this->port;
	}
	public function ignoreDefault(){
		$this->ignoreDefault= true;
	}
	public function useTube($tube){
		$this->use= $tube;
	}
	public function watch($tubes){
		if(is_array($tubes)){
			foreach($tubes as $tube){
				$this->watch[]= $tube;
			}
		}
	}
	/**
	 * For use by the BeanstalkClient
	 * @param BeanstalkClient $client
	 */
	public function _apply(BeanstalkClient $client){
		if(count($this->watch)){
			foreach ($this->watch as $tube){
				$client->watch($tube);
			}
			if($this->ignoreDefault){
				$client->ignore('default');
			}
		}
		if($this->use){
			$client->useTube($this->use);
		}
	}
}