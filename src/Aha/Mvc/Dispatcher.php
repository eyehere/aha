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
	
	//protocal
	private $_protocal	=	null;
	
	//=========http protocal=================
	private $_request = null;
	
	private $_response	= null;
	
	//=========tcp protocal===================
	
	
	//=========udp protocal===================
	
	
	//=========websocket======================
	
	/**
	 * @brief 初始化dispatcher
	 * @param \Ala\Mvc\Aha\Bootstrap $bootstrap
	 * @return \Ala\Mvc\Dispatcher
	 */
	public function __construct(Aha\Bootstrap $bootstrap, string $protocal = 'http') {
		$this->_objBootstrap = $bootstrap;
		$this->_protocal	 = $protocal;
		return $this;
	}
	
	public function getBootstrap() {
		return $this->_objBootstrap;
	}
	
	public function getRouter() {
		return $this->_objRouter;
	}
	
	public function getProtocal() {
		return $this->_protocal;
	}

	/**
	 * @brief http protocal request
	 * @param \swoole_http_request $request
	 */
	public function setRequest(\swoole_http_request $request) {
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * @brief get http protocal request
	 * @return type
	 */
	public function getRequest() {
		return $this->_request;
	}
	
	/**
	 * @brief http protocal response
	 * @param \swoole_http_response $response
	 */
	public function setResponse(\swoole_http_response $response) {
		$this->_response = $response;
		return $this;
	}
	
	/**
	 * @brief get http protocal response
	 * @return type
	 */
	public function getResponse() {
		return $this->_response;
	}

	/**
	 * @brief 路由分发
	 * @param \Ala\Mvc\Aha\Mvc\Router $router
	 * @return boolean
	 */
	public function dispatch(Aha\Mvc\Router $router) {
		$this->_objRouter = $router;
		
		//filter
		
		//validate action
		
		//call action
		
		return true;
	}
	
}