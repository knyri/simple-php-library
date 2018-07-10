<?php
class BeanstalkJob {
	private
		$id,
		$data,
		$client;
	public function __construct($id, $data, BeanstalkClient $client){
		$this->id= $id;
		$this->data= $data;
		$this->client= $client;
	}
	public function id(){
		return $this->id;
	}
	public function data(){
		return $this->data;
	}
	public function delete(){
		$this->client->delete($this->id);
	}
	public function release($delay= 0, $priority= -1){
		if($priority == -1){
			$stats= $this->client->statsJob($this->id);
			$priority= $stats['pri'];
		}
		return $this->client->release($this->id, $delay, $priority);
	}
	public function bury($priority= -1){
		if($priority == -1){
			$stats= $this->client->statsJob($this->id);
			$priority= $stats['pri'];
		}
		$this->client->bury($this->id, $priority);
	}
	public function stats(){
		return $this->client->statsJob($this->id);
	}
	public function touch(){
		$this->client->touch($this->id);
	}
}