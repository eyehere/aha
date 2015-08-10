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
namespace Aha\Network;

abstract class Client {
	
	/**
	 * @brief swoole client : http tcp udp ...
	 * @var type 
	 */
	protected $_objClient	= null;
	
	/**
	 * @brief request
	 * @var type 
	 */
	protected $_request	= null;

	/**
	 * @brief 初始化client
	 * @param \swoole_client $client
	 */
	public function __construct(\swoole_client $client) {
		$this->_objClient = $client;
		$this->_initEvents();
		return $this;
	}
	
	/**
	 * @brief swoole client 事件初始化
	 * @return \Aha\Network\Client
	 */
	protected function _initEvents() {
		$this->_objClient->on('connect', array($this, 'onConnect') );
		$this->_objClient->on('receive', array($this, 'onReceive') );
		$this->_objClient->on('error', array($this, 'onError') );
		$this->_objClient->on('close', array($this, 'onClose') );
		return $this;
	}
	
	/**
	 * @brief 连接成功时的回调 发送数据到server
	 * @param \swoole_client $client
	 */
	public function onConnect(\swoole_client $client) {
		$client->send($this->_request);
	}
	
	/**
	 * @brief 收到数据时候的回调
	 * @param \swoole_client $client
	 * @param type $data
	 */
	public function onReceive(\swoole_client $client, $data) {
		
	}
	
	/**
	 * @brief 发生错误时的回调
	 * @param \swoole_client $client
	 */
	public function onError(\swoole_client $client) {
		
	}
	
	/**
	 * @brief 连接关闭时的回调
	 * @param \swoole_client $client
	 */
	public function onClose(\swoole_client $client) {
		
	}
	//================client callback END======================================
	/**
	 * @set request 包括请求行 + 请求头 + 请求体
	 * @param type $request
	 * @return \Aha\Network\Client
	 */
	public function setRequest( $request ) {
		$this->_request = $request;
		return $this;
	}
	
}