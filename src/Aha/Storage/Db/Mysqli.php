<?php

/*
  +----------------------------------------------------------------------+
  | Aha                                                                  |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.0 of the Apache license,    |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.apache.org/licenses/LICENSE-2.0.html                      |
  | If you did not receive a copy of the Apache2.0 license and are unable|
  | to obtain it through the world-wide-web, please send a note to       |
  | yiming_6weijun@163.com so we can mail you a copy immediately.        |
  +----------------------------------------------------------------------+
  | Author: Weijun Lu  <yiming_6weijun@163.com>                          |
  +----------------------------------------------------------------------+
*/
namespace Aha\Storage\Db;

class Mysqli {
	
	/**
	 * @brief 当前连接的conf,连接和失败重连
	 * @var type 
	 */
	protected $_conf = array();
	
	/**
	 * @brief 数据库连接池大小
	 * @var type 
	 */
	protected $_poolSize = 0;

	/**
	 * @brief mysqli连接池当前此数据库的连接数
	 * @var type 
	 */
	protected $_connectionNum = 0;
	
	/**
	 * @brief 数据库实例的空闲连接池
	 * @var type 
	 */
	protected $_idlePool	= array();
	
	/**
	 * @brief 数据库的工作连接池
	 * @var type 
	 */
	protected $_busyPool	= array();
	
	/**
	 * @brief SQL任务队列
	 * @var type 
	 */
	protected $_poolQueue = array();

	/**
	 * @brief 初始化连接
	 * @param type $dbConf
	 * @return \Aha\Storage\Db\Mysqli
	 * @throws Exception
	 */
	public function __construct($dbConf) {
		if ( empty($dbConf['host']) || empty($dbConf['port']) || empty($dbConf['user']) || 
			 empty($dbConf['password']) || empty($dbConf['dbName']) || empty($dbConf['poolSize']) ) {
			throw new \Exception("Please check your db config,required:host,port,user,password,dbName,poolSize.");
		}
		
		if ( !function_exists('swoole_get_mysqli_sock') ) {
			throw new \Exception("function swoole_get_mysqli_sock not exists");
		}
		
		$this->_conf			= $dbConf;
		$this->_connectionNum	= 0;
		$this->_poolSize		= intval($dbConf['poolSize']);
		return $this;
	}
	
	/**
	 * @brief 链接数据库
	 * @return boolean
	 */
	protected function _connect() {
		$dbObj = new \mysqli($this->_conf['host'], $this->_conf['user'], 
				$this->_conf['password'], $this->_conf['dbName'], $this->_conf['port']);
		
		if ( $dbObj->connect_error ) {
			echo "Mysqli Error [connect_db_failed][errno]{$dbObj->connect_errno}"
			. "[error]{$dbObj->connect_error} [conf]" . serialize($this->_conf) . PHP_EOL;
			return false;
		}
		
		if ( !empty($this->_conf['charset']) ) {
			$dbObj->set_charset($this->_conf['charset']);
		}
		
		$dbSock = swoole_get_mysqli_sock($dbObj);
		if ( !is_long($dbSock) ) {
			echo "Mysqli Error [swoole_get_mysqli_sock]" . serialize($dbSock) . PHP_EOL;
			goto errorClose;
		}
		$ret = swoole_event_add($dbSock, array($this, 'onQueryResponse'));
		if ( !is_long($ret) ) {
			echo "Mysqli Error [swoole_event_add]" . serialize($ret) . PHP_EOL;
			goto errorClose;
		}
		
		$this->_idlePool[$dbSock] = compact('dbObj','dbSock');
		$this->_connectionNum++;
		return $this;
		
		errorClose:
			$dbObj->close();
			return false;
	}
	
	/**
	 * @brief 关闭数据库连接
	 * @param type $dbSock
	 * @return \Aha\Storage\Db\Mysqli
	 */
	protected function _close($dbSock) {
		swoole_event_del($dbSock);
		if ( isset($this->_idlePool[$dbSock]) ) {
			$this->_idlePool[$dbSock]['dbObj']->close();
			unset($this->_idlePool[$dbSock]);
		} elseif (isset ($this->_busyPool[$dbSock]) ) {
			$this->_busyPool[$dbSock]['dbObj']->close();
			unset($this->_busyPool[$dbSock]);
		} else {
			echo "Mysqli Exception [_close] not found [sock] $dbSock " . PHP_EOL;
		}
		$this->_connectionNum--;
		return $this;
	}
	
	/**
	 * @brief 当有数据返回时候的处理
	 * @param type $dbSock
	 * @return boolean
	 */
	public function onQueryResponse($dbSock) {
		$task = isset($this->_busyPool[$dbSock]) ? $this->_busyPool[$dbSock] : null;
		if ( empty($task) ) {//这种情况直接丢弃
			echo "MySQLi Warning: Maybe SQLReady receive a Close event , "
					. "such as Mysql server close the socket !" . PHP_EOL;
			$this->_close($dbSock);
			return false;
		}
		
		$callback	= $task['callback'];
		$dbObj		= $task['dbObj'];
		$onReadFree	= $task['onReadFree'];//正常只能是true or false，事务中有三个标记
		$sql		= $task['sql'];
		
		$result = $dbObj->reap_async_query();
		try {
			call_user_func($callback, $result, $dbObj, $dbSock);
		} catch (\Exception $e) {
			echo "Mysqli onReadCallback Exception: {$e->getMessage()} [SQL]{$sql}" . PHP_EOL;
		}
		
		if ( is_object($result) ) {
			mysqli_free_result($result);
		}
		
		if ( !$result ) {
			echo "MySQLi onReadCallback Error:[SQL] {$sql} [error]" . mysqli_error($dbObj) . PHP_EOL;
		}
		
		$this->_onReadTrans($dbObj,$dbSock, $result, $onReadFree);
		
		$this->_trigger();
	}
	
	/**
	 * @brief onRead过程中的事务处理 和 非事务资源回收
	 * @param type $dbSock
	 * @param type $result
	 * @param type $onReadFree
	 * @return type
	 */
	private function _onReadTrans($dbObj,$dbSock, $result, $onReadFree) {
		$arrTransKeep = array(
			\Aha\Storage\Db\Transaction::TRANS_COMMIT,
			\Aha\Storage\Db\Transaction::TRANS_ROLLBACK
		);
		
		$arrTrans = array(
			\Aha\Storage\Db\Transaction::TRANS_AUTO_COMMIT_ON,
			\Aha\Storage\Db\Transaction::TRANS_COMMIT,
			\Aha\Storage\Db\Transaction::TRANS_ROLLBACK
		);
		
		//事务处理过程中，此连接不能被释放
		//commit or rollback成功 连接暂时不能被释放
		if ( false === $onReadFree || ($result && in_array($onReadFree, $arrTransKeep, true)) ) {
			return;
		}
		
		unset($this->_busyPool[$dbSock]);
		
		//如果事务的set autocommit＝1失败，这个连接不能接着用了
		//commit失败 这个连接也不能用了
		//rollback失败 还是不能用
		if ( in_array($onReadFree, $arrTrans, true) && !$result ) {
			return $this->_close($dbSock);
		}

		$this->_idlePool[$dbSock] = compact('dbObj', 'dbSock');
		return;
	}
	
	/**
	 * @brief 等待队列触发器
	 * @return type
	 */
	private function _trigger() {
		if ( count($this->_poolQueue) <= 0 ) {
			return;
		}
		
		$idleCnt = count($this->_idlePool);
		for ( $i=0; $i<$idleCnt; $i++ ) {
			if ( empty($this->_poolQueue) ) {
				break;
			}
			$task = array_shift($this->_poolQueue);
			$this->_doQuery($task['sql'], $task['callback'], $task['onReadFree']);
		}
		echo "Mysqli _trigger [DEBUG] [cnt] $idleCnt" . PHP_EOL;
	}

	/**
	 * @brief 执行查询
	 * @param type $sql
	 * @param type $callback
	 * @param type $onReadFree
	 * @param type $dbSock
	 * @return boolean
	 */
	protected function _doQuery($sql, $callback, $onReadFree = true, $dbSock = null, $retry=0) {
		$db = null;
		if ( null !== $dbSock ) {
			$db = $this->_busyPool[$dbSock];
		} else {
			$db = array_shift($this->_idlePool);
		}
		$mysqli = $db['dbObj'];
		$mysqliSock = $db['dbSock'];
		
		$result = $mysqli->query($sql, MYSQLI_ASYNC);
		if ( false !== $result ) {
			$this->_busyPool[$mysqliSock] = array_merge($db,compact('sql','callback','onReadFree'));
			return true;
		}
		
		//事务中途出现错误，不能rollback，也不必rollback，直接错了
		if ( null !== $dbSock ) {//这种情况下 上一阶段 set autocommit=0 成功
			$this->_queryFailedNotify($callback, $dbSock);//事务中途出错的连接不能再用
			return true;
		}

		if ( $mysqli->errno == 2013 || $mysqli->errno == 2006 ) {
			echo "Mysqli Expected Error[errno]{$mysqli->errno} [error]{$mysqli->error} [SQL] $sql" . PHP_EOL;
			$this->_close($db['dbSock']);//连接中断的重新建立新的连接
			return $this->query($sql, $callback, $onReadFree, $mysqliSock, $retry++);
		}
		//其它异常情况 需要通知宿主 但不用关闭连接 可能是sql写错
		echo "Mysqli Unexpected Error[errno]{$mysqli->errno} [error]{$mysqli->error} [SQL] $sql" . PHP_EOL;
		$this->_queryFailedNotify($callback);
		return false;
	}
	
	/**
	 * @brief 执行SQL语句
	 * @param type $sql
	 * @param type $callback
	 * @param type $onReadFree
	 * @param type $dbSock
	 * @return boolean
	 */
	public function query($sql, $callback, $onReadFree = true, $dbSock = null, $retry = 0) {
		if ( $retry >= 1 ) {
			echo "Mysqli Error:[retry exception] [SQL]$sql" . PHP_EOL;
			$this->_queryFailedNotify($callback, $dbSock);//2013和2006重试两次了 直接关闭得了
			return false;
		}
		//用于事务指定在同一个连接上进行query
		if ( null !== $dbSock ) {
			return $this->_doQuery($sql, $callback, $onReadFree, $dbSock);
		}
		//非事务类型 或 事务类型的第一次 query
		if (count($this->_idlePool) > 0 ) {
			return $this->_doQuery($sql, $callback, $onReadFree, $dbSock);
		}
		//连接池动态增长
		if ( $this->_connectionNum < $this->_poolSize ) {
			if ( false === $this->_connect() ) {
				echo "Mysqli Error:[expand connect error] [SQL]$sql" . PHP_EOL;
				$this->_queryFailedNotify($callback);
				return false;
			}
			return $this->_doQuery($sql, $callback, $onReadFree, $dbSock);
		}
		
		//控制等待队列的大小
		if ( count($this->_poolQueue) >= $this->_poolSize  ) {
			echo "MySQLi Warning: poolQueue Size is beyond poolSize ![SQL] {$sql}" . PHP_EOL;
			$this->_queryFailedNotify($callback);
			return false;
		}
		
		//超过连接池大小放入队列等候
		$this->_poolQueue[] = compact('sql','callback','onReadFree');
		return true;
	}
	
	/**
	 * @brief query失败 释放资源 callback 上层调用query的时候也不用进行错误处理
	 * @param type $callback
	 * @param type $dbSock
	 */
	protected function _queryFailedNotify($callback,$dbSock = null) {
		if ( null !== $dbSock ) {
			$this->_close($dbSock);
		}
		try {
			call_user_func($callback, false, false, false);
		} catch (\Exception $e) {
			echo "Mysqli _queryFailedNotify Exception: {$e->getMessage()}" . PHP_EOL;
		}
	}

	/**
	 * @brief 开启一个数据库事务
	 * @return \Aha\Storage\Db\Transaction
	 */
	public function beginTrans() {
		return new \Aha\Storage\Db\Transaction($this);
	}
	
	/**
	 * @brief 开启一个协程
	 * @return \Aha\Storage\Db\Coroutine
	 */
	public function createCoroutine() {
		return new \Aha\Storage\Db\Coroutine($this);
	}
	
}