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
namespace Aha\Mvc;
//Filter建议慎重使用，主要原因：框架运行变得重耦合，实现方不注意细节可能阻塞整个进程
//init filter:在worker启动的时候 由开发者调用静态类的静态方法添加钩子
//(注册的钩子需要考虑异步情况下的并发问题 避免因为并发下处理同一个对象带来麻烦)
//如果想在filter的hooks中中断请求不继续往下处理后续逻辑，请抛出异常，自定义异常类型并捕获
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
	
	/**
	 * @brief 路由之前回调
	 * @param type $callback  cbFn($dispatcher, $data=array('cbIndex'=>0,'callback'=>cbFn,...))
	 * @return \Aha\Mvc\Filter
	 */
	public function registerPreRouter($callback) {
		array_push($this->_arrPreRouter, $callback);
		return $this;
	}
	
	/**
	 * @brief 路由之前回调
	 * @param type $callback  cbFn($dispatcher, $data=array('cbIndex'=>0,'callback'=>cbFn,...))
	 * @return \Aha\Mvc\Filter
	 */
	public function registerPostRouter($callback) {
		array_push($this->_arrPostRouter, $callback);
		return $this;
	}

	/**
	 * @brief 分发之前回调
	 * @param type $callback  cbFn($dispatcher, $data=array('cbIndex'=>0,'callback'=>cbFn,...))
	 * @return \Aha\Mvc\Filter
	 */
	public function registerPreDispatch($callback) {
		array_push($this->_arrPreDispatch, $callback);
		return $this;
	}
	
	/**
	 * @brief 分发之后回调(注意：为了框架雨业务解耦，这个过程在调用分发之后会立即执行 基本认为与分发过程是并行执行)
	 * @param type $callback  cbFn($dispatcher, $data=array('cbIndex'=>0,'callback'=>cbFn,...))
	 * @return \Aha\Mvc\Filter
	 */
	public function registerPostDispatch($callback) {
		array_push($this->_arrPostDispatch, $callback);
		return $this;
	}
	
	/**
	 * @brief 所有路由之前注册的回调的call 之后是路由
	 * @param \Aha\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function preRouter(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPreRouter[$cbIndex]) ) {
			return $dispatcher->routeLoop();
		}
		$data['cbIndex']++;
		$data['callback'] = array($this, __FUNCTION__);
		call_user_func($this->_arrPreRouter[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 路由之后注册的回调的call 之后是preDispatch
	 * @param \Aha\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function postRouter(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPostRouter[$cbIndex]) ) {
			return $this->preDispatch($dispatcher);
		}
		$data['cbIndex']++;
		$data['callback'] = array($this, __FUNCTION__);
		call_user_func($this->_arrPostRouter[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 分发之前的操作 之后是call action
	 * @param \Aha\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function preDispatch(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPreDispatch[$cbIndex]) ) {
			return $dispatcher->dispatchLoop();
		}
		$data['cbIndex']++;
		$data['callback'] = array($this, __FUNCTION__);
		call_user_func($this->_arrPreDispatch[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 分发之后的操作 之前是call action
	 * @param \Aha\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @param array $data
	 * @return string
	 */
	public function postDispatch(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		if ( !isset($data['cbIndex']) ) {
			$data['cbIndex'] = 0;
		}
		$cbIndex = $data['cbIndex'];
		if ( !isset($this->_arrPostDispatch[$cbIndex]) ) {
			return AHA_DECLINED;
		}
		$data['cbIndex']++;
		$data['callback'] = array($this, __FUNCTION__);
		call_user_func($this->_arrPostDispatch[$cbIndex], $dispatcher, $data);
		return AHA_AGAIN;
	}
	
}