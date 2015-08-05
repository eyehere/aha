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
namespace \Aha\Mvc;

abstract class Action {
	
	//dispatcher instance
	protected $_objDispatcher = null;
	
	/**
	 * @brief 初始化Action
	 * @param \Aha\Mvc\Aha\Mvc\Dispatcher $dispatcher
	 * @return \Aha\Mvc\Action
	 */
	public function __construct(\Aha\Mvc\Dispatcher $dispatcher) {
		$this->_objDispatcher = $dispatcher;
		return $this;
	}
	
	/**
	 * @brief before execute
	 * block not allowed when init if extends
	 */
	final public function brefore(\Aha\Mvc\Dispatcher $dispatcher) {
		$this->_objDispatcher = $dispatcher;
		$this->excute();
		$this->after();
	}

	/**
	 * @brief 子类必须实现的抽象方法
	 */
	abstract public function excute();
	
	/**
	 * @brief after execute
	 * block not allowed when init if extends
	 */
	final public function after() {
		$this->_objDispatcher->getBootstrap()->getFilter()->postDispatch($this);
	}
	
}