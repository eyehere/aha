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
			throw new Exception("Please check your db config,required:host,port,user,password,dbName,poolSize.");
		}
		
		if ( !function_exists('swoole_get_mysqli_sock') ) {
			throw new Exception("function swoole_get_mysqli_sock not exists");
		}
		
		$this->_conf			= $dbConf;
		$this->_connectionNum	= 0;
		$this->_poolSize		= $dbConf['poolSize'];
		return $this;
	}
	
	/**
	 * @brief 链接数据库
	 * @return boolean
	 */
	protected function connect() {
		$dbObj = new \mysqli($this->_conf['host'], $this->_conf['user'], 
				$this->_conf['password'], $this->_conf['dbName'], $this->_conf['port']);
		
		if ( $dbObj->connect_error ) {
			echo "[connect_db_failed]" . serialize($this->_conf) . PHP_EOL;
			return false;
		}
		
		if ( !empty($this->_conf['charset']) ) {
			$dbObj->set_charset($this->_conf['charset']);
		}
		
		$dbSock = swoole_get_mysqli_sock($dbObj);
		swoole_event_add($dbSock, array($this, 'onRead'));
		$this->_idlePool[$dbSock] = compact('dbObj','dbSock');
		$this->_connectionNum++;
		return $this;
	}
	
	/**
	 * @brief 关闭数据库连接
	 * @param type $dbSock
	 * @return \Aha\Storage\Db\Mysqli
	 */
	protected function close($dbSock) {
		swoole_event_del($dbSock);
		$this->_idlePool[$dbSock]['dbObj']->close();
		unset($this->_idlePool[$dbSock]);
		$this->_connectionNum--;
		return $this;
	}
	
	
	
}