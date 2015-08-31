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

	/**
	 * @brief redis请求的通用方法
	 * @param type $name
	 * @param type $arguments
	 */
	public function __call($name, $arguments) {
		$callback = array_pop($arguments);
		array_unshift($arguments, $name);
		$cmd = $this->_buildRequest($arguments);
		return $this->_query($cmd, $callback);
	}
	
	/**
	 * @brief redis hmset指令
	 * @param type $key
	 * @param array $value
	 * @param type $callback
	 * @return type
	 */
	public function hmset($key, array $value, $callback) {
        $lines[] = "hmset";
        $lines[] = $key;
        foreach($value as $k => $v) {
            $lines[] = $k;
            $lines[] = $v;
        }
        $cmd = $this->_buildRequest($lines);
       return $this->_query($cmd, $callback);
    }
	
	/**
	 * @brief redis redis hmget指令
	 * @param type $key
	 * @param array $value
	 * @param type $callback
	 * @return type
	 */
	public function hmget($key, array $value, $callback) {
		$fields = $value;
        array_unshift($value, "hmget", $key);
        $cmd = $this->_buildRequest($value);
        return $this->_query($cmd, $callback, $fields);
    }

	/**
	 * @brief redis request协议请求数据封装
	 * @param type $array
	 * @return string
	 */
	protected function _buildRequest($array) {
        $cmd = '*' . count($array) . "\r\n";
        foreach ($array as $item) {
            $cmd .= '$' . strlen($item) . "\r\n" . $item . "\r\n";
        }
        return $cmd;
    }
	
	/**
	 * @brief 执行redis cmd
	 * @param type $cmd
	 * @param type $callback
	 * @param type $fields
	 * @return boolean
	 */
	protected function _query($cmd, $callback, $fields = array()) {
		//优先从空闲连接池取出
		if (count($this->_idlePool) > 0 ) {
			return $this->_doQuery($cmd, $callback, $fields);
		}
		//连接池动态增长
		if ( $this->_connectionNum < $this->_poolSize ) {
			$this->_idlePool[] = new \Aha\Storage\Memory\Rediscli($this->_conf);
			$this->_connectionNum++;
			return $this->_doQuery($cmd, $callback, $fields);
		}
		//控制队列的堆积
		if ( count($this->_poolQueue) >= $this->_poolSize ) {
			$message = 'Redis Warning: poolQueue Size is beyond poolSize';
			echo "$message ![CMD] {$cmd}" . PHP_EOL;
			$this->_queryFailedNotify($callback, null, $message);
			return false;
		}
		$this->_poolQueue[] = compact('cmd','callback','fields');
		return true;
	}
	
	/**
	 * @brief 执行redis指令
	 * @param type $cmd
	 * @param type $callback
	 * @param type $fields
	 */
	protected function _doQuery($cmd, $callback, $fields = array()) {
		$redisCli = array_shift($this->_idlePool);
		$redisCli->setPackage($cmd)
				->setArguments(compact('callback','fields'))
				->setCallback(array($this,'response'))
				->loop();
	}
	
	/**
	 * @brief 异常或错误时候回调上层以及资源回收
	 * @param type $callback
	 * @param type $redisCli
	 */
	protected function _queryFailedNotify($callback, $redisCli = null, $message=null) {
		if ( null !== $redisCli && $redisCli->isConnected() ) {
			$redisCli->close();
		}
		try {
			call_user_func($callback, false, $message);
		} catch (\Exception $e) {
			echo "Redis _queryFailedNotify Exception: {$e->getMessage()}" . PHP_EOL;
		}
	}
	
	/**
	 * @brief redis响应数据回调上层和资源回收
	 * @param type $result
	 * @param type $callback
	 */
	public function response($result, $callback, \Aha\Storage\Memory\Rediscli $redisCli, $error = null) {
		try {
			call_user_func($callback, $result, $error);
		} catch (\Exception $e) {
			echo "Redis Response Callback Exception: {$e->getMessage()}" . PHP_EOL;
		}
		//如果此连接已经关闭 放入回收器 等待触发资源回收
		if ( !$redisCli->getClient()->isConnected() ) {
			$this->_connectionNum--;
			\Aha\Storage\Memory\Pool::redisCliGc($redisCli);
			return $this->_trigger();
		}
		//未关闭则当作长连接放入连接池
		$this->_idlePool[] = $redisCli;
		return $this->_trigger();
	}
	
	/**
	 * @brief redis队列触发器
	 * @return type
	 */
	protected function _trigger() {
		if ( count($this->_poolQueue) <= 0 ) {
			return;
		}
		
		$idleCnt = count($this->_idlePool);
		if ( $idleCnt <= 0 ) {
			return;
		}
		
		for ( $i=0; $i<$idleCnt; $i++ ) {
			if ( empty($this->_poolQueue) ) {
				break;
			}
			$task = array_shift($this->_poolQueue);
			$this->_doQuery($task['cmd'], $task['callback'], $task['fields']);
		}
		echo "Redis _trigger [DEBUG] [cnt] $idleCnt" . PHP_EOL;
	}
	
	/**
	 * @brief redis连接池的状态监测
	 * @return type
	 */
	public function stats() {
		return array(
			'conf'			=> $this->_conf,
			'poolSize'		=> $this->_poolSize,
			'connectionNum'	=> $this->_connectionNum,
			'idleCnt'		=> count($this->_idlePool),
			'queueLen'		=> count($this->_poolQueue)
		);
	}
	
	/**
	 * @brief 开启一个redis协程
	 * @return \Aha\Storage\Memory\Coroutine
	 */
	public function createCoroutine() {
		return new \Aha\Storage\Memory\Coroutine($this);
	}
	
}