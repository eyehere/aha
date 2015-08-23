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

class Transaction {
	
	const TRANS_AUTO_COMMIT_ON	=	'autocommiton';
	const TRANS_COMMIT			=	'commit';
	const TRANS_ROLLBACK		=	'rollback';

	/**
	 * @brief 事务回收池
	 * @var type 
	 */
	private static $_TransGc = array();

	/**
	 * @brief 事务队列
	 * @var type 
	 */
	protected $_arrQueue = null;
	
	/**
	 * @brief 事务队列的queue
	 * @var type 
	 */
	protected $_arrQueueKey = null;
	
	/**
	 * @当前正在执行的事务队列序号
	 * @var type 
	 */
	protected $_current = null;

	/**
	 * @brief 事务完成之后的回调
	 * @var type 
	 */
	protected $_callback = null;
	
	/**
	 * @brief mysqli
	 * @var type 
	 */
	protected $_dbObj = null;
	
	/**
	 * @brief 事务结果
	 * @var type 
	 */
	protected $_arrResult = null;

	/**
	 * @brief 事务初始化
	 */
	public function __construct(\Aha\Storage\Db\Mysqli $dbObj) {
		$this->_arrQueue	= array();
		$this->_arrQueueKey	= array();
		$this->_current		= 0;
		$this->_arrResult	= array();
		$this->_dbObj		= $dbObj;
	}

	/**
	 * @brief 事务队列
	 * @param type $key
	 * @param type $sql or callback
	 * @return \Aha\Storage\Db\Transaction
	 */
	public function queue($key, $sql) {
		array_push($this->_arrQueueKey, $key);
		array_push($this->_arrQueue, $sql);
		return $this;
	}
	
	/**
	 * @brief 结束之后的回调
	 * @param type $callback
	 * @return \Aha\Storage\Db\Transaction
	 */
	public function setCallback($callback) {
		$this->_callback = $callback;
		return $this;
	}
	
	/**
	 * @brief 事务执行过程的回调和处理
	 * @param type $prevResult
	 * @param type $dbObj
	 * @param type $dbSock
	 * @return boolean
	 */
	public function transCallback($prevResult, $dbObj, $dbSock) {
		//任何这种错误 直接抛错误
		if ( false === $prevResult && false === $dbObj && false === $dbSock ) {
			return $this->_handleTransException();
		}
		
		//第一条SQL
		if ( $this->_current === 0 ) {
			return $this->_firstQuery($dbSock);
		}
		
		//中间有任何错误 执行回滚 后续sql不在执行
		if ( $this->_current < count($this->_arrQueueKey) && false === $prevResult ) {
			return $this->_rollback($dbSock);
		}
		
		//事务执行过程 从第二条SQL开始
		if ( $this->_current < count($this->_arrQueueKey) ) {
			return $this->_nextQuery($prevResult, $dbObj, $dbSock);
		}
		
		//事务已经执行完 提交事务
		if ( $this->_current === count($this->_arrQueueKey) ) {
			return $this->_commit($dbSock);
		}
		
		//事务已经提交或者回滚
		if ( $this->_current === count($this->_arrQueueKey)+ 1 ) {
			if ( $prevResult ) {//commit or rollback成功
				return $this->_notifyAndAutoCommitOn($dbSock);
			} else {//commit or rollback失败
				return $this->_commitOrRollbackFailed();
			}
		}
		
		//set autocommit=1 不论成功失败 都可以直接返回 因为已经做过处理
		if ( $this->_current === count($this->_arrQueueKey) + 2 ) {
			return true;
		}
		
		return true;
	}
	
	/**
	 * @brief 事务回调中的错误处理
	 * @return boolean
	 */
	private function _handleTransException() {
		try {
			call_user_func($this->_callback, false. false, false);
		} catch (\Exception $e) {
			echo "Mysqli transCallback Exception: {$e->getMessage()}" . PHP_EOL;
		}
		$this->_clean();
		self::$_TransGc[] = $this;
		return false;
	}
	
	/**
	 * @brief 执行队列的第一条SQL
	 * @return type
	 */
	private function _firstQuery($dbSock) {
		$this->_dbObj->query($this->_arrQueue[0], array($this, 'transCallback'), false, $dbSock);
		return $this->_current++;
	}
	
	/**
	 * @brief 回滚
	 * @param type $dbSock
	 * @return type
	 */
	private function _rollback($dbSock) {
		$this->_current = count($this->_arrQueueKey) + 1;
		$sql = 'rollback';
		return $this->_dbObj->query($sql, array($this, 'transCallback'), false, $dbSock);
	}
	
	/**
	 * @brief 执行队列的下一条 SQL
	 * @param type $prevResult
	 * @param type $dbObj
	 * @param type $dbSock
	 * @return type
	 */
	private function _nextQuery($prevResult, $dbObj, $dbSock) {
		$key = $this->_arrQueueKey[$this->_current-1];
		$arrData = array(
			'result'		=> $prevResult,
			'affected_rows'	=> $dbObj->affected_rows,
			'last_insert_id'=> $dbObj->insert_id
		);
		$this->_arrResult[$key] = $arrData;

		$sql = $this->_arrQueue[$this->_current];
		/**如果后面的sql依赖前面的执行结果
		 * $sql = function($data) {
		 *		if ( isset($data['aaa']['last_insert_id') ) {
		 *			return "XXXXXXXXXX=id";
		 *		}
		 * }
		 */
		if ( is_callable($sql) ) {
			$sql = $sql($this->_arrResult);
		}
		$this->_dbObj->query($sql, array($this, 'transCallback'), false, $dbSock);
		return $this->_current++;
	}
	
	/**
	 * @brief 提交事务
	 * @param type $dbSock
	 * @return type
	 */
	private function _commit($dbSock) {
		$this->_current++;
		$sql = 'commit';
		return $this->_dbObj->query($sql, array($this, 'transCallback'), false, $dbSock);
	}
	
	/**
	 * @brief 通知事务结果 并且充值事务自动提交
	 * @param type $dbSock
	 * @return type
	 */
	private function _notifyAndAutoCommitOn($dbSock) {
		try {
			call_user_func($this->_callback, $this->_arrResult, false, false);
		} catch (\Exception $e) {
			echo "Mysqli transCallback Exception[commit success]: {$e->getMessage()}" . PHP_EOL;
		}

		$this->_current++;
		//事务完成 开启自动提交
		$sql = 'set autocommit=1';
		return $this->_dbObj->query($sql, array($this, 'transCallback'), 'autocommiton', $dbSock);
	}
	
	/**
	 * @brief commit或者rollback失败
	 * @return boolean
	 */
	private function _commitOrRollbackFailed() {
		try {
			call_user_func($this->_callback, false, false, false);
		} catch (\Exception $e) {
			echo "Mysqli transCallback Exception[commit failed]: {$e->getMessage()}" . PHP_EOL;
		}

		$this->_clean();
		self::$_TransGc[] = $this;

		return false;
	}

	/**
	 * @brief 事务队列bootstrap
	 * @return type 若返回值为false，说明事务开启失败
	 */
	public function execute() {
		if ( !empty(self::$_TransGc) ) {
			//事务对象回收 更快的内存回收和资源释放
			foreach (self::$_TransGc as $key=>$val) {
				unset(self::$_TransGc[$key]);
			}
		}
		
		if ( count($this->_arrQueueKey) <=1 ) {
			throw new Exception("Transaction require more then two sql");
		}
		
		$sql = 'set autocommit=0';
		return $this->_dbObj->query($sql, array($this, 'transCallback'), false);
	}
	
	/**
	 * @brief 释放内存和资源
	 */
	protected function _clean() {
		$this->_arrQueue	= null;
		$this->_arrQueueKey	= null;
		$this->_current		= null;
		$this->_callback	= null;
		$this->_dbObj		= null;
		$this->_arrResult	= null;
	}

	/**
	 * @brief 事务销毁
	 */
	public function __destruct() {
		$this->_clean();
	}
	
}