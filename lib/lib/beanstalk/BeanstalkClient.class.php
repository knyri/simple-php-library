<?php
require_once 'BeanstalkClientConfig.class.php';
require_once 'BeanstalkJob.class.php';
PackageManager::requireClassOnce('net.socket');

class BeanstalkClient {
	private
		$con,
		$readBuffer= '',
		$readPos= 0;
	public function __construct(){}
	/**
	 * Connects and applies the config.
	 * Disconnects from the current server if already
	 * connected.
	 * @param BeanstalkClientConfig $config
	 * @throws BeanstalkConnectException
	 */
	public function config(BeanstalkClientConfig $config){
		$this->connect($config->getHost(), $config->getPort());
		$config->_apply($this);
	}
	/**
	 * Connects to the server
	 * Disconnects of already connected
	 * @param string $host
	 * @param number $port
	 * @throws BeanstalkConnectException
	 */
	public function connect($host, $port= 11300){
		$this->close();
		$this->con= new Socket();
		if(!$this->con->connect($host, $port)){
			throw new BeanstalkConnectException($this->con->errorStr());
		}
	}
	/**
	 * Fills the buffer if empty.
	 * Blocks until at least 1 byte has been read
	 * @throws IOException
	 */
	private function fillBuffer(){
		$len= strlen($this->readBuffer);
		if($len > 0 && $this->readPos < $len){
			return;
		}
		// Needed since it is possible (but rare) that the read position can
		// go beyond the end of the buffer length. This allows those extra
		// bytes to be discarded
		$this->readPos= $this->readPos - $len;
		$this->readBuffer= $this->con->read();
// 		logit("Read position: {$this->readPos} Buffer: {$this->readBuffer}");
		if($this->readBuffer === false){
			throw new IOException($this->con->errorStr());
		}
		while(strlen($this->readBuffer) < 1){
			sleep(1);
			$this->readBuffer= $this->con->read();
			if($this->readBuffer === false){
				throw new IOException($this->con->errorStr());
			}
		}
	}
	/**
	 *
	 * @param string $cmd
	 * @param string $data
	 * @throws BeanstalkServerException
	 * @throws BeanstalkProtocolException
	 * @return string
	 */
	private function doCommand($cmd, $data=null){
// 		logit('cmd '.$cmd);
		$this->con->write($cmd);
		if($data){
			$this->con->write(
				' ' . strlen($data) . "\r\n" .
				$data);
		}
		$this->con->write("\r\n");

		$resp= $this->getToken();
// 		logit('resp '.$resp);

		switch($resp){
			case "OUT_OF_MEMORY":
				throw new BeanstalkServerException("Server ran out of memory while processing this request.");
			case "INTERNAL_ERROR":
				throw new BeanstalkServerException("Server encountered an internal error while processing this request.");
			case "BAD_FORMAT":
				throw new BeanstalkProtocolException("Command is formatted incorrectly.");
			case "UNKNOWN_COMMAND":
				throw new BeanstalkProtocolException("The server didn't recognize the command.");
		}

		return $resp;

	}

	/**
	 * Reads raw data from the buffer
	 * @return string
	 */
	private function readData(){
		$len= intval($this->getToken(), 10);
		if(strlen($this->readBuffer) - $this->readPos > $len){
			$data= substr($this->readBuffer, $this->readPos, $len);
			$this->readPos+= $len;
		}else{
			$data= substr($this->readBuffer, $this->readPos);
			$read= strlen($data);
			$this->clearBuffer();
			while($read < $len){
				$part= $this->con->read($len - $read);
				$read+= strlen($part);
				$data.= $part;
			}
		}
		$this->fillBuffer();
		// skip the remaining \r\n
		$this->readPos+= 2;
		return $data;
	}

	/**
	 * Parses a simple YAML list
	 * @throws BeanstalkProtocolException
	 * @return string[]
	 */
	private function readList(){
		$raw= $this->readData();
		$list= array();
		$lines= explode("\n", $raw);
		if($lines[0] != '---'){
			throw new BeanstalkProtocolException("Expected '---' but got '" . $list[0] . "'");
		}
		array_shift($lines);
		foreach($lines as $line){
			if($line == '...' || trim($line) == ''){
				continue;
			}
			$list[]= trim(substr($line,1));
		}
		return $list;
	}
	/**
	 * Parses a simple YAML map
	 * @throws BeanstalkProtocolException
	 * @return array<string,string>
	 */
	private function readMap(){
		$raw= $this->readData();
		$lines= explode("\n", $raw);
		if($lines[0] != '---'){
			throw new BeanstalkProtocolException("Expected '---' but got '" . $lines[0] . "'");
		}
		array_shift($lines);
		if($lines[count($lines) - 1] == '...'){
			// The optional end of doc line
			array_pop($lines);
		}
		$map= array();
		foreach($lines as $line){
			if(trim($line) == ''){
				continue;
			}
			$item= explode(':', $line);
			$map[trim($item[0])]= trim($item[1]);
		}
		return $map;
	}


	private function clearBuffer(){
		$this->readBuffer= '';
		$this->readPos= 0;
	}
	/**
	 * Get the next token
	 * @return string
	 */
	private function getToken(){
		$token= '';
		$this->fillBuffer();

		do {
			$buf= $this->readBuffer;
			$end= strlen($buf);
			$start= $this->readPos;
			$cur= $start;
			for(; $cur < $end; $cur++){
				switch($buf[$cur]){
					case "\n":
						if($buf[$cur - 1] == "\r"){
							$token.= substr($buf, $start, $cur - $start - 1);
							$this->readPos= $cur + 1;
						}
						break 3;
					case ' ':
						$token.= substr($buf, $start, $cur - $start);
						$this->readPos= $cur + 1;
						break 3;
				}
			}
			$this->readPos= $cur;
			if($start == 0){
				$token.= $buf;
			}else{
				$token.= substr($buf, $start);
			}
		}while(true);

		return $token;
	}
	private function readJob(){
		return new BeanstalkJob(intval($this->getToken(), 10), $this->readData(), $this);
	}
	/**
	 * Reserves a job from the queue. If no timeout is given then it will block
	 * until a job is ready.
	 * @param int $timeout (-1)
	 * @throws BeanstalkTimeoutException If a timeout is specified and reached
	 * @throws BeanstalkCommandException
	 * @throws BeanstalkProtocolException
	 * @return BeanstalkJob
	 */
	public function reserve($timeout= -1){
		if($timeout == -1){
			$resp= $this->doCommand("reserve");
		}else{
			$resp= $this->doCommand("reserve-with-timeout "+$timeout);
		}
		switch($resp){
			case "TIMED_OUT":
				throw new BeanstalkTimeoutException("Time out wating for a job");
			case "DEADLINE_SOON":
				throw new BeanstalkCommandException("DEADLINE_SOON");
			case "RESERVED":
				break;
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}

		return $this->readJob();
	}
	/**
	 * Deletes a job from the queue
	 * @param int $jobId
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 */
	public function delete($jobId){
		$resp= $this->doCommand("delete " . $jobId);
		switch($resp){
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			case 'DELETED':
				break;
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Releases a reserved job back into the queue.
	 * @param int $jobId
	 * @param number $delay (0)
	 * @param number $priority (1024)
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return PutOrReleaseResponse
	 */
	public function release($jobId, $delay= 0, $priority= 1024){
		$resp= $this->doCommand("release $jobId $priority $delay");
		switch($resp){
			case "RELEASED":
				return new PutOrReleaseResponse(intval($this->getToken(), 10), true);
			case "BURIED":
				return new PutOrReleaseResponse(intval($this->getToken(), 10), false);
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Puts a job into the currently used tube.
	 * @param string $data
	 * @param number $delay
	 * @param number $ttr
	 * @param number $priority
	 * @throws BeanstalkProtocolException
	 * @throws BeanstalkServerException If the server isn't accepting new jobs
	 * @return PutOrReleaseResponse
	 */
	public function put($data, $delay= 0, $ttr= 1, $priority= 1024){
		$resp= $this->doCommand("put $priority $delay $ttr", $data);
		switch($resp){
			case "INSERTED":
				return new PutOrReleaseResponse(intval($this->getToken(), 10), true);
			case "BURIED":
				return new PutOrReleaseResponse(intval($this->getToken(), 10), false);
			case "EXPECTED_CRLF":
				throw new BeanstalkProtocolException("Missing trailing CRLF after data.");
			case "JOB_TOO_BIG":
				throw new BeanstalkProtocolException("Job data too large.");
			case "DRAINING":
				throw new BeanstalkServerException("Server is in drain mode and is not accepting new jobs.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Takes a job out of the ready queue without deleting it
	 * @param int $jobId
	 * @param number $priority
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 */
	public function bury($jobId, $priority= 1024){
		$resp= $this->doCommand("bury $jobId $priority");
		switch($resp){
			case "BURIED":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Refreshes the Time-To-Run timer for a reserved job
	 * @param int $jobId
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 */
	public function touch($jobId){
		$resp= $this->doCommand("touch $jobId");
		switch($resp){
			case "TOUCHED":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Adds the tube to the list of tubes this client is listening to for jobs
	 * @param string $tube
	 * @throws BeanstalkProtocolException
	 */
	public function watch($tube){
		$resp= $this->doCommand("watch $tube");
		if('WATCHING' != $resp){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		// discard watch count
		$this->getToken();
	}
	/**
	 * Removes the tube to the list of tubes this client is listening to for jobs
	 * @param string $tube
	 * @throws BeanstalkProtocolException
	 * @return boolean false if it is the only tube the client is watching
	 */
	public function ignore($tube){
		$resp= $this->doCommand("ignore $tube");
		switch($resp){
			case "WATCHING":
				// discard count
				$this->getToken();
				return true;
			case "NOT_IGNORED":
				return false;
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Gets the job's details without reserving it
	 * @param int $jobId
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return BeanstalkJob
	 */
	public function peek($jobId){
		$resp= $this->doCommand("peek $jobId");
		switch($resp){
			case "FOUND":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}

		return $this->readJob();
	}
	/**
	 * Get the details of the next read job
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return BeanstalkJob
	 */
	public function peekReady(){
		$resp= $this->doCommand("peek-ready");
		switch($resp){
			case "FOUND":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("No jobs in the ready queue");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}

		return $this->readJob();
	}
	/**
	 * Get the details of the next job in the delayed queue without reserving it
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return BeanstalkJob
	 */
	public function peekDelayed(){
		$resp= $this->doCommand("peek-delayed");
		switch($resp){
			case "FOUND":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("No jobs in the delayed queue");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}

		return $this->readJob();
	}
	/**
	 * Get the details of the next job in the buried queue without reserving it
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return BeanstalkJob
	 */
	public function peekBuried(){
		$resp= $this->doCommand("peek-buried");
		switch($resp){
			case "FOUND":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("No jobs in the buried queue");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}

		return $this->readJob();
	}
	/**
	 * Move up to $limit jobs from the buried and delayed queue to the ready queue.
	 * Jobs in the buried queue have priority.
	 * @param int $limit
	 * @throws BeanstalkProtocolException
	 * @return number Number of jobs moved to the ready queue
	 */
	public function kick($limit){
		$resp= $this->doCommand("kick $limit");
		switch($resp){
			case 'KICKED':
				return intval($this->getToken());
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Moves a job from the buried or delayed queue to the ready queue
	 * @param int $jobId
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 */
	public function kickJob($jobId){
		$resp= $this->doCommand("kick-job $jobId");
		switch($resp){
			case "KICKED":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * The client will use this tube for put and kick commands
	 * @param string $tube
	 * @throws BeanstalkProtocolException
	 * @return boolean
	 */
	public function useTube($tube){
		$resp= $this->doCommand("use $tube");
		if($resp != 'USING'){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		return strtolower($tube) == strtolower($this->getToken());
	}
	/**
	 * Gets the name of tube currently being used
	 * @throws BeanstalkProtocolException
	 * @return string
	 */
	public function listTubeUsed(){
		$resp= $this->doCommand("list-tube-used");
		if($resp != 'USING'){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		return $this->getToken();
	}

	/**
	 * @param unknown $jobId
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return array<string,string>
	 */
	public function statsJob($jobId){
		$resp= $this->doCommand("stats-job $jobId");
		switch($resp){
			case "OK":
				return $this->readMap();
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Job $jobId does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * @param unknown $tube
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 * @return array<string,string>
	 */
	public function statsTube($tube){
		$resp= $this->doCommand("stats-tube $tube");
		switch($resp){
			case "OK":
				return $this->readMap();
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Tube $tube does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Prevent jobs from being reserved for a while.
	 * @param string $tube
	 * @param int $delay The delay in seconds
	 * @throws BeanstalkNotFoundException
	 * @throws BeanstalkProtocolException
	 */
	public function pauseTube($tube, $delay){
		$resp= $this->doCommand("pause-tube $tube $delay");
		switch($resp){
			case "PAUSED":
				break;
			case 'NOT_FOUND':
				throw new BeanstalkNotFoundException("Tube $tube does not exist.");
			default:
				throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
	}
	/**
	 * Gets the stats for the server
	 * @throws BeanstalkProtocolException
	 * @return array<string,string>
	 */
	public function stats(){
		$resp= $this->doCommand("stats");
		if($resp != 'OK'){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		return $this->readMap();
	}
	/**
	 * List all tubes on the server
	 * @throws BeanstalkProtocolException
	 * @return string[]
	 */
	public function listTubes(){
		$resp= $this->doCommand("list-tubes");
		if($resp != 'OK'){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		return $this->readList();
	}
	/**
	 * List all tubes this client is watching
	 * @throws BeanstalkProtocolException
	 * @return string[]
	 */
	public function listTubesWatched(){
		$resp= $this->doCommand("list-tubes-watched");
		if($resp != 'OK'){
			throw new BeanstalkProtocolException("Unexpected response: '" + $resp + "'");
		}
		return $this->readList();
	}


	/**
	 * Politely disconnect from the server
	 */
	public function close(){
		if($this->con && $this->con->isConnected()){
			$this->con->write("quit\r\n");
			$this->con->close();
		}
	}
}
class PutOrReleaseResponse {
	private $jobid, $inserted;
	public function __construct($jobid, $inserted){
		$this->jobid= $jobid;
		$this->inserted= $inserted;
	}
	public function getJobId(){
		return $this->jobid;
	}
	public function wasInserted(){
		return $this->inserted;
	}
	public function wasReleased(){
		return $this->inserted;
	}
	public function wasBuried(){
		return !$this->inserted;
	}
}
class BeanstalkException extends CustomException{}
class BeanstalkCommandException extends BeanstalkException{}
class BeanstalkNotFoundException extends BeanstalkCommandException{}
class BeanstalkConnectException extends BeanstalkException{}
class BeanstalkServerException extends BeanstalkException{}
class BeanstalkProtocolException extends BeanstalkException{}