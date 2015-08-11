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
	
	const ERR_SUCESS			=	0;//调用成功
	const ERR_CONNECT_FAILED	=	1;//连接失败
	const ERR_CONNECT_TIMEOUT	=	2;//连接超时
	const ERR_REQUEST_TIMEOUT	=	3;//请求超时
	const ERR_UNEXPECT			=	4;//预期之外的错误 onError
	const ERR_SEND_FAILED		=	5;//发送数据失败

	/**
	 * @brief swoole client : http tcp udp ...
	 * @var type 
	 */
	protected $_objClient	= null;
	
	protected $_host = null;
	protected $_port = null;
	protected $_timeout = 1;
	protected $_connectTimeout = 0.05;

	/**
	 * @brief 请求ID
	 * @var type 
	 */
	protected $_requestId	= null;

	/**
	 * @brief package
	 * @var type 
	 */
	protected $_package	= null;
	
	/**
	 * @brief client调用回调
	 * @var type 
	 */
	protected $_callback = null;
	
	/**
	 * @brief 花费时间
	 * @var type 
	 */
	protected $_const	= 0;
	
	//超时控制定制器
	protected $_timer = null;

	/**
	 * @brief 初始化client
	 * @param \swoole_client $client
	 */
	public function __construct(\swoole_client $client, \string $host, \int $port, $timeout=1, $connectTimeout=0.05) {
		$this->_objClient		= $client;
		$this->_host			= $host;
		$this->_port			= $port;
		$this->_timeout			= $timeout;
		$this->_connectTimeout	= $connectTimeout;
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
		if ( ! $client->send($this->_request) ) {
			$client->close();
			$response = array(
				'errno'		=> \Aha\Network\Client::ERR_SEND_FAILED, 
				'errmsg'	=> array('errCode'=>$client->errCode, 'error'=>  socket_strerror($client->errCode)),
				'requestId'	=> $this->_requestId,
				'const'		=> microtime(true) - $this->_const,
				'data'		=> array()
			);
			call_user_func($this->_callback, $response);
		}
	}
	
	/**
	 * @brief 收到数据时候的回调
	 * @param \swoole_client $client
	 * @param type $data
	 */
	abstract function onReceive(\swoole_client $client, $data);
	
	/**
	 * @brief 发生错误时的回调
	 * @param \swoole_client $client
	 */
	public function onError(\swoole_client $client) {
		$client->close();
		$response = array(
			'errno'		=> \Aha\Network\Client::ERR_UNEXPECT, 
			'errmsg'	=> array('errCode'=>$client->errCode, 'error'=>  socket_strerror($client->errCode)),
			'requestId'	=> $this->_requestId,
			'const'		=> microtime(true) - $this->_const,
			'data'		=> array()
		);
		call_user_func($this->_callback, $response);
	}
	
	/**
	 * @brief 连接关闭时的回调
	 * @param \swoole_client $client
	 */
	public function onClose(\swoole_client $client) {
		
	}
	//================client callback END======================================
	/**
	 * @brief 设置请求的唯一标识 在批量调用中区分是那个请求的返回
	 * @param type $requestId
	 * @return \Aha\Network\Client
	 */
	public function setRequestId($requestId) {
		$this->_requestId = $requestId;
		return $this;
	}
	
	/**
	 * @set request 包括请求行 + 请求头 + 请求体
	 * @param type $package
	 * @return \Aha\Network\Client
	 */
	public function setPackage( $package ) {
		$this->_package = $package;
		return $this;
	}
	
	/**
	 * @brief 设置callback
	 * @param type $callback
	 * @return \Aha\Network\Client
	 */
	public function setCallback( $callback ) {
		$this->_callback = $callback;
		return $this;
	}
	
	//==========================loop BEGIN====================================
	/**
	 * @brief 请求驱动
	 * @return \Aha\Network\Client
	 */
	public function loop() {
		$this->_const = microtime(true);
		if ( floatval($this->_timeout) > 0 ) {
			$this->_timer = swoole_timer_after($this->_timeout * 1000, function(){
				$this->_objClient->close();
				$response = array(
					'errno'		=> \Aha\Network\Client::ERR_REQUEST_TIMEOUT, 
					'errmsg'	=> 'request_timeout',
					'requestId'	=> $this->_requestId,
					'const'		=> microtime(true) - $this->_const,
					'data'		=> array()
				);
				call_user_func($this->_callback, $response);
			});
		}
		return $this;
	}
	
}