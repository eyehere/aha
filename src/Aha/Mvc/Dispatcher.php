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

class Dispatcher {
	
	//application instance
	private $_objBootstrap = null;
	
	//router instance
	private $_objRouter = null;
	
	/**
	 * @brief 初始化dispatcher
	 * @param \Ala\Mvc\Aha\Bootstrap $bootstrap
	 * @return \Ala\Mvc\Dispatcher
	 */
	public function __construct(Aha\Bootstrap $bootstrap) {
		$this->_objBootstrap = $bootstrap;
		return $this;
	}
	
	/**
	 * @brief 路由分发
	 * @param \Ala\Mvc\Aha\Mvc\Router $router
	 * @return boolean
	 */
	public function dispatch(Aha\Mvc\Router $router) {
		$this->_objRouter = $router;
		return true;
	}
	
}