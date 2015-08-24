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
namespace Aha\Storage\Memory;

class Redis {
	
	/**
	 * @brief 当前连接的conf,连接和失败重连
	 * @var type 
	 */
	protected $_conf = array();
	
	/**
	 * @brief redis连接池大小
	 * @var type 
	 */
	protected $_poolSize = 0;

	/**
	 * @brief redis连接池当前此redis实例的连接数
	 * @var type 
	 */
	protected $_connectionNum = 0;
	
	/**
	 * @brief redis实例的空闲连接池
	 * @var type 
	 */
	protected $_idlePool	= array();
	
	/**
	 * @brief redis的工作连接池
	 * @var type 
	 */
	protected $_busyPool	= array();
	
	/**
	 * @brief redis指令任务队列
	 * @var type 
	 */
	protected $_poolQueue = array();
	
	/**
	 * @brief 初始化一个redis实例的连接池
	 * @param type $conf
	 * @return \Aha\Storage\Memory\Redis
	 * @throws \Exception
	 */
	public function __construct($conf) {
		if ( !isset($conf['host']) || !isset($conf['port']) || 
				!isset($conf['poolSize']) || !isset($conf['timeout']) ) {
			throw new \Exception("Please check your redis config,required:host,port,poolSize,timeout.");
		}
		$this->_conf			= $conf;
		$this->_poolSize		= intval($conf['poolSize']);
		$this->_connectionNum	= 0;
		return $this;
	}
	
}