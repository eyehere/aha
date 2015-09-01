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

class Coroutine {
	
	/**
	 * @brief redis Manager
	 * @var type 
	 */
	protected $_redis = null;
	
	/**
	 * @brief query arguments
	 * @var type 
	 */
	protected $_arguments = null;
	
	/**
	 * @brief 开启一个redis协程
	 * @param \Aha\Storage\Memory\Redis $redis
	 * @return \Aha\Storage\Memory\Coroutine
	 */
	public function __construct( \Aha\Storage\Memory\Redis $redis ) {
		$this->_redis = $redis;
		return $this;
	}
	
	/**
	 * @brief 执行redis指令
	 * @param type $name
	 * @param type $arguments
	 */
	public function __call($name, $arguments) {
		array_unshift($arguments, $name);
		$this->_arguments = $arguments;
		return $this;
	}
	
	/**
	 * @brief redis协程调度
	 * @param type $callback
	 */
	public function execute( $callback ) {
		$method = array_shift($this->_arguments);
		array_push($this->_arguments, $callback);
		try {
			call_user_func_array(array($this->_redis, $method), $this->_arguments);
		} catch ( \Exception $ex) {
			echo "[REDIS_COROUTINE_EXECUTE_EXCEPTION]" . $ex->getMessage() . PHP_EOL;
		}
	}
	
}