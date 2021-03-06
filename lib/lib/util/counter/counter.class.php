<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */
//require_once LIB.'lib/db/functions_database.inc.php';
//require_once LIB.'lib/error/class_DatabaseException.php';
PackageManager::requireFunctionOnce('lib.db.database');
PackageManager::requireFunctionOnce('lang.string');
PackageManager::requireClassOnce('lib.error.DatabaseException');

/**
 * Counts visits. Keeps track of sessions, user-agents, referrers, and bots.
 * @author Kenneth Pierce
 */
class Counter {
	var $info = array();
	public function __construct($page = null) {
		if (empty($page)) $page = Counter::getDefaultPage();
		$info['page'] = $page;
		unset($page);
	}
	public static function getDefaultPage() {
		$conf=LibConfig::getConfig('util.counter');
		if(str_ends_with('/',$_SERVER['REQUEST_URI'])) { $page=$_SERVER['PHP_SELF'];} else { $page=$_SERVER['REQUEST_URI'];}
		if(!isset($_SERVER['HTTP_HOST'])) return $page;
		$tmp = explode('.', strtolower($_SERVER['HTTP_HOST']));
		if($conf['multidomain']==false) {
			if ($tmp[0]=='www') unset($tmp[0]);
			$tmp = array_reverse($tmp);
			unset($tmp[0]);
			return '/'.implode('/', $tmp).$page;
		} else {
			switch (count($tmp)) {
				case 0:
				case 1:
				case 2:
					return $page;
				case 3:
					if ($tmp[0]=='www') return $page;
					return '/'.$tmp[0].$page;
				default:
					if ($tmp[0]=='www') unset($tmp[0]);
					$tmp = array_reverse($tmp);
					unset($tmp[0]);unset($tmp[1]);
					return '/'.implode('/', $tmp).$page;
			}
		}
	}
	public static function trimQuery($url) {
		$pos = strpos($url, '?');
		if ($pos===false)
			return $url;
		return substr($url, 0, $pos);
	}
	/**
	 * Resets the data for reuse.
	 * @access public
	 */
	public function reset() {
		unset($this->info);
		$this->info = array();
		$info['page'] = Counter::getDefaultPage();
	}
	/**
	 * Sets the target page. Defaults to the page requested
	 * @param string $page
	 * @access public
	 */
	public function setPage($page) {
		$this->info['page'] = $page;
	}
	/**
	 * Sets the referrer. Defaults to the referrer given in the HTTP request.
	 * @param string $referrer
	 */
	public function setReferrer($referrer) {
		$this->info['referrer'] = $referrer;
	}
	/**
	 * Main 'do it all' function.
	 * Increments if the session has expired
	 * @return string The count for the page.
	 */
	public function count() {
		$conf=LibConfig::getConfig('util.counter');
		if(!isset($_SERVER['HTTP_USER_AGENT'])||$_SERVER['HTTP_USER_AGENT']==null)return;
		$db = db_get_connection();
		if(!empty($conf['database']))
			mysql_select_db($conf['database'], $db);

		//fields relevant to both bots and users
		if (empty($this->info['referrer'])) {
			if (isset($_SERVER['HTTP_REFERER'])) {
				$this->info['referrer'] = $_SERVER['HTTP_REFERER'];
				$this->info['referrer_id'] = md5($this->info['referrer']);
			} else {
				$this->info['referrer'] = 'direct';
				$this->info['referrer_id'] = md5($this->info['referrer']);
			}
		} else {
			$this->info['referrer_id'] = md5($this->info['referrer']);
		}
		if (empty($this->info['page'])) {
			$this->info['page'] = Counter::getDefaultPage();
			$this->info['page_id'] = md5($this->info['page']);
		} else if (empty($this->info['page_id'])) $this->info['page_id'] = md5($this->info['page']);

		if ($this->isBot($db)) {//bot only stuff
			if (empty($this->info['agent_id'])) $this->info['agent_id'] = md5($_SERVER['HTTP_USER_AGENT']);
			if (empty($this->info['session'])) $this->info['session'] = md5($_SERVER['HTTP_USER_AGENT'].$this->info['page']);
			$this->getBotSessionInfo($db);
			if (!$this->referrerExists($db)) {
				$this->addReferrer($db);
			}
			$this->info['scount']++;
			$this->updateBotSession($db);
			$this->incrementUserAgent($db);
			return;
		}//NOTE: END BOT
		if (empty($this->info['address'])) $this->info['address'] = $_SERVER['REMOTE_ADDR'];
		if (empty($this->info['session'])) $this->info['session'] = md5($this->info['address'].$this->info['page']);
		$count = -1;

		$this->getSessionInfo($db);
		//echo '<pre>'.var_export($info,true).'</pre>';
		if (!$this->referrerExists($db)) {
			$this->addReferrer($db);
		}
		$this->addUserAgent($db);
		if (isset($this->info['screated'])) {//session added
			$this->increment($db);
		} else {//old session
			$diff =  db_do_operation($db, "TIMEDIFF(NOW(), '{$this->info['dtime']}')");
			$diff = explode(':', $diff);
			$diff = $diff[0]*60+$diff[1];
			if ($diff > $conf['expire']) {
				$this->increment($db);
			}
		}
		$qurl = Counter::trimQuery($this->info['page']);
		if (strlen($qurl)==strlen($this->info['page'])) {
			mysql_close($db);
			return $this->info['vcount'].' visits since '.$this->info['created'];
		} else {
			$c = new Counter();
			$c->setPage($qurl);//sub-counter will close the db
			return 'This page: '.$this->info['vcount'].' visits since '.$this->info['created'].'<br />Base page: '.$c->count();
		}
	}
	/**
	 * Checks and adds a user agent to the database.
	 * @param resource $db A MySQL database resource.
	 */
	private function addUserAgent($db) {
		$agent = $_SERVER['HTTP_USER_AGENT'];
		$agentid = md5($agent);
		if (db_record_exist($db, 'user_agent', "agent_id='$agentid'")===true) return;
		$error = db_insert($db, 'user_agent', array('agent_id'=>$agentid, 'agent_string'=>$agent,'created'=>'NOW()'));
		if ($error)
			throw new DatabaseException('While adding an user agent: '.$error);
	}
	private function isBot($db) {
		return db_record_exist($db, 'bots', 'agent_id=\''.md5($_SERVER['HTTP_USER_AGENT']).'\'');//select * from `user_agent` where agent_count > 0
	}
	private function incrementUserAgent($db) {
		$agent = md5($_SERVER['HTTP_USER_AGENT']);
		$info = db_get_row_assoc($db, 'user_agent', 'agent_id=\''.$agent.'\'');
		if ($info === null) return;
		db_update($db, 'user_agent', array('agent_count'=>($info['agent_count']+1)), array(array('agent_id', $agent)));
	}
	/**
	 * Increments and updates the page count, the referrer count, and the session.
	 * @param resource $db
	 * @throws DatabaseException
	 */
	private function increment($db) {
		$this->info['vcount']++;
		$this->info['scount']++;
		$error = db_update($db, 'pcpages', array('vcount'=>$this->info['vcount']),array(array('page_id',$this->info['page_id'])));
		if ($error)
			throw new DatabaseException('While incrementing page count: '.$error);
		$this->incrementUserAgent($db);
		$this->incrementReferrer($db);
		$this->updateSession($db);
	}
	/**
	 * Adds a page to the counter database
	 * @param resource $db
	 * @throws DatabaseException
	 */
	private function addPage($db) {
		if (empty($this->info['page'])) {
			$this->info['page'] = $_SERVER['PHP_SELF'];
			$this->info['page_id'] = md5($this->info['page']);
		}
		$error = db_insert($db, 'pcpages', array('page_id'=>$this->info['page_id'],'page'=>$this->info['page'], 'created'=>'NOW()'));
		if ($error)
			throw new DatabaseException('While adding page to counter index: '.$error);
	}
	private function getBotSessionInfo($db) {
		$info = db_get_row_assoc($db, 'pcpages', "page_id='{$this->info['page_id']}'");
		if ($info == null) {
			$this->addPage($db);
			$info = db_get_row_assoc($db, 'pcpages', "page_id='{$this->info['page_id']}'");
			$this->info['pcreated'] = true;
		}
		$ses = db_get_row_assoc($db, 'botvisit',"session='{$this->info['session']}'");
		if (empty($ses)) {
			$this->createBotSession($db, $this->info);
			$ses = db_get_row_assoc($db, 'botvisit',"session='{$this->info['session']}'");
			$ses['screated'] = true;
		}
		$ses = array_merge($ses, $info);
		$this->info = array_merge($this->info, $ses);
	}
	private function getSessionInfo($db) {
		$info = db_get_row_assoc($db, 'pcpages', "page_id='{$this->info['page_id']}'");
		if ($info == null) {
			$this->addPage($db);
			$info = db_get_row_assoc($db, 'pcpages', "page_id='{$this->info['page_id']}'");
			$this->info['pcreated'] = true;
		}
		$ses = db_get_row_assoc($db, 'pcvisit',"session='{$this->info['session']}'");
		if (empty($ses)) {
			$this->createSession($db, $this->info);
			$ses = db_get_row_assoc($db, 'pcvisit',"session='{$this->info['session']}'");
			$ses['screated'] = true;
		}
		$ses = array_merge($ses, $info);
		$this->info = array_merge($this->info, $ses);
	}
	private function referrerExists($db) {
		if (!isset($this->info['referrer'])) return true;
		if (!isset($this->info['referrer_id'])) $this->info['referrer_id'] = md5($_SERVER['HTTP_REFERER']);
		$record = db_record_exist($db, 'referrer_visit', "page_id='{$this->info['page_id']}' AND referrer_id='{$this->info['referrer_id']}'");
		if ($record === true)
			return true;
		elseif ($record === false) {
			return false;
		} else {
			throw new DatabaseException('While checking for referrer entry: '.$record);
		}
	}
	private function incrementReferrer($db) {
		if (!isset($this->info['referrer'])) return;
		if (isset($this->info['refcreated'])) return;
		$ref = db_get_row_assoc($db, 'referrer_visit', "page_id='{$this->info['page_id']}' AND referrer_id='{$this->info['referrer_id']}'");
		$error = db_update($db, 'referrer_visit', array('rcount'=>$ref['rcount']+1), array(array('page_id', $this->info['page_id'], 'AND'), array('referrer_id', $this->info['referrer_id'])));
		if ($error)
			throw new DatabaseException('While incrementing a referrer: '.$error);
	}
	private function addReferrer($db) {
		if (!isset($this->info['referrer'])) return;
		if (!db_record_exist($db, 'referrers', 'referrer_id=\''.$this->info['referrer_id'].'\''))
			db_insert($db, 'referrers', array('referrer_id'=>$this->info['referrer_id'], 'referrer_url'=>$this->info['referrer']));
		$error = db_insert($db, 'referrer_visit', array(
			'page_id'=>$this->info['page_id'],
			'referrer_id'=>$this->info['referrer_id'],
			'vtime'=>'NOW()'
		));
		if ($error)
			throw new DatabaseException('While adding a referrer: '.$error);
		$this->info['refcreated'] = true;
	}
	private function createSession($db) {
		$error = db_insert($db, 'pcvisit', array(
				'session'=>$this->info['session'],
				'address'=>$this->info['address'],
				'page_id'=>$this->info['page_id'],
				'dtime'=>'NOW()'
			));
		if ($error)
			throw new DatabaseException('While adding new visitor for page: '.$error);
	}
	private function createBotSession($db) {
		$error = db_insert($db, 'botvisit', array(
				'session'=>$this->info['session'],
				'agent_id'=>$this->info['agent_id'],
				'page_id'=>$this->info['page_id'],
				'dtime'=>'NOW()'
			));
		if ($error)
			throw new DatabaseException('While adding new visitor for page: '.$error);
	}
	private function updateSession($db) {
		$error = db_update($db, 'pcvisit', array(
				'dtime'=>'NOW()',
				'scount'=>$this->info['scount']
			), array(array('session', $this->info['session'])));
		if ($error)
			throw new DatabaseException('While updating a session: '.$error);
		$error = db_insert($db, 'pcvisits', array('session'=>$this->info['session'], 'dtime'=>'NOW()'));
		if ($error)
			throw new DatabaseException('While inserting visit session: '.$error);
	}
	private function updateBotSession($db) {
		$error = db_update($db, 'botvisit', array(
				'dtime'=>'NOW()',
				'scount'=>$this->info['scount']
			), array(array('session', $this->info['session'])));
		if ($error)
			throw new DatabaseException('While updating a session: '.$error);
		if(!db_record_exist($db, 'botvisits', 'session=\''.$this->info['session'].'\' AND dtime=NOW()')){
			$error = db_insert($db, 'botvisits', array('session'=>$this->info['session'], 'dtime'=>'NOW()'));
			if ($error)
				throw new DatabaseException('While inserting visit session: '.$error);
		}
	}
}
?>