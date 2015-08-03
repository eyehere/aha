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
namespace Ala\Mvc;
//Filter暂时不启用，主要原因：框架运行变得重耦合，实现方不注意细节可能阻塞整个进程
class Filter {
	
	//路由之前
	private $_arrPreRouter = array();
	//路由完成之后
	private $_arrPostRouter = array();
	//分发之前
	private $_arrPreDispatch = array();
	//分发之后
	private $_arrPostDispatch = array();

	public function __construct() {
		;
	}
	
	//注册路由之前回调
	public function registerPreRouter($callback) {
		array_push($this->_arrPreRouter, $callback);
		return $this;
	}
	//注册路由之后回调
	public function registerPostRouter($callback) {
		array_push($this->_arrPostRouter, $callback);
		return $this;
	}
	//注册分发之前回调
	public function registerPreDispatch($callback) {
		array_push($this->_arrPreDispatch, $callback);
		return $this;
	}
	//注册分发之后回调
	public function registerPostDispatch($callback) {
		array_push($this->_arrPostDispatch, $callback);
		return $this;
	}
	
	/**
	 * @brief 所有路由之前注册的回调的call 之后是路由
	 * @param \Ala\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function preRouter(Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPreRouter[$cbIndex]) ) {
			return AHA_DECLINED;
		}
		$data['cbIndex']++;
		call_user_func($this->_arrPreRouter[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 路由之后注册的回调的call 之后是preDispatch
	 * @param \Ala\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function postRouter(Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPostRouter[$cbIndex]) ) {
			return AHA_DECLINED;
		}
		$data['cbIndex']++;
		call_user_func($this->_arrPostRouter[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 分发之前的操作 之后是call action
	 * @param \Ala\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function preDispatch(Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPreDispatch[$cbIndex]) ) {
			return AHA_DECLINED;
		}
		$data['cbIndex']++;
		call_user_func($this->_arrPreDispatch[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 分发之后的操作 之前是call action
	 * @param \Ala\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function postDispatch(Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPostDispatch[$cbIndex]) ) {
			return AHA_DECLINED;
		}
		$data['cbIndex']++;
		call_user_func($this->_arrPostDispatch[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
}