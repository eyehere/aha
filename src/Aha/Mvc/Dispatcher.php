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
	private $_tcpClientFd = null;
	
	private $_tcpFromId	= null;
	
	private $_tcpPackage = null;
	
	//=========udp protocal===================
	private $_udpPackage = null;
	
	private $_udpClientInfo = null;
	
	//=========websocket======================
	
	/**
	 * @brief 初始化dispatcher
	 * @param \Aha\Mvc\Aha\Bootstrap $bootstrap
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function __construct(\Aha\Bootstrap $bootstrap,  $protocal = 'http') {
		$this->_objBootstrap = $bootstrap;
		$this->_protocal	 = $protocal;
		return $this;
	}
	
	/**
	 * @brief 获取bootstrap实例
	 * @return type
	 */
	public function getBootstrap() {
		return $this->_objBootstrap;
	}
	
	/**
	 * @brief 获取router实例
	 * @return type
	 */
	public function getRouter() {
		return $this->_objRouter;
	}
	
	/**
	 * @brief 获取协议
	 * @return type
	 */
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
	 * @brief tcp set fd
	 * @param  $fd
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function setTcpClientFd( $fd) {
		$this->_tcpClientFd = $fd;
		return $this;
	}
	
	/**
	 * @brief tcp get fd
	 * @return type
	 */
	public function getTcpClientFd() {
		return $this->_tcpClientFd;
	}
	
	/**
	 * @brief tcp set fromId
	 * @param  $fromId
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function setTcpFromId( $fromId) {
		$this->_tcpFromId = $fromId;
		return $this;
	}
	
	/**
	 * @brief tcp get fromId 
	 * @return type
	 */
	public function getTcpFromId() {
		return $this->_tcpFromId;
	}
	
	/**
	 * @brief tcp set package
	 * @param  $data
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function setTcpPackage( $data) {
		$this->_tcpPackage = $data;
		return $this;
	}
	
	/**
	 * @brief tcp get package
	 * @return type
	 */
	public function getTcpPackage() {
		return rtrim($this->_tcpPackage, "\r\n\r\n");
	}
	
	/**
	 * @brief udp set package
	 * @param  $data
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function setUdpPackage( $data) {
		$this->_udpPackage = $data;
		return $this;
	}
	
	/**
	 * @brief udp get package
	 * @return type
	 */
	public function getUdpPackage() {
		return rtrim($this->_udpPackage, "\r\n\r\n");
	}
	
	/**
	 * @brief udp set clientInfo
	 * @param array $clientInfo
	 * @return \Aha\Mvc\Dispatcher
	 */
	public function setUdpClientInfo(array $clientInfo) {
		$this->_udpClientInfo = $clientInfo;
		return $this;
	}
	
	/**
	 * @brief udp get clientInfo
	 * @return type
	 */
	public function getUdpClientInfo() {
		return $this->_udpClientInfo;
	}

	/**
	 * @brief 路由分发
	 * @param \Aha\Mvc\Aha\Mvc\Router $router
	 * @return boolean
	 */
	public function dispatch(\Aha\Mvc\Router $router) {
		$this->_objRouter = $router;
		$this->_objFilter = new \Aha\Mvc\Filter();
		//filter 分发时候触发 pre router 钩子
		$this->_objBootstrap->getFilter()->preRouter($this);
	}
	
	/**
	 * @brief 在preRouter中触发
	 */
	public function routeLoop() {
		$this->_objRouter->route();
		$this->_objBootstrap->getFilter()->postRouter($this);
	}


	/**
	 * @brief 在preDispatch 中触发
	 * @throws Exception
	 */
	public function dispatchLoop() {
		$action = $this->_objRouter->getAction();
		
		$objAction = new $action($this);
		if ( !is_subclass_of($objAction,  '\\Aha\\Mvc\\Action') ) {
			throw new \Exception( "class $action is not extends \\Aha\\Mvc\\Action" );
		}
		
		try {
			call_user_func(array($objAction, 'before'), $this);
		} catch ( \Exception $e) {
			\Aha\Log\Sys::log()->error( '\\Aha\\Mvc\\Action dispatchLoop callback exception:'.$e->getMessage() );
		}
	}
	
}