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
namespace Aha\Client;
use \Aha\Network\Client;

class Tcp extends Client {
	
	/**
	 * @brief 初始化一个tcp客户端
	 * @param  $host
	 * @param  $port
	 * @param type $timeout
	 * @param type $connectTimeout
	 */
	public function __construct( $host,  $port, $timeout = 1, $connectTimeout = 0.05) {
		//开启长连接默认用IP:PORT作为key
		$client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $host, $port, $timeout, $connectTimeout);
		$this->_initConfig();
	}
	
	/**
	 * 初始化config 保证收到一个完整的Udp包再回调
	 */
	protected function _initConfig() {
		$setting = array(
			'open_eof_check'		=>	true,
			'package_eof'			=>	"\r\n\r\n",
			'package_max_length'	=>	1024 * 1024 * 2,
			'open_tcp_nodelay'		=>  true
		);
		$this->_objClient->set($setting);
	}

	/**
	 * @brief 接收响应数据的callback
	 * @param \swoole_client $client
	 * @param type $data
	 */
	public function onReceive(\swoole_client $client, $data) {
		if ( null !== $this->_timer ) {
			//\Aha\Network\Timer::del($this->_timer);
		}
		$response = array(
			'errno'		=> \Aha\Network\Client::ERR_SUCESS, 
			'errmsg'	=> 'sucess',
			'requestId'	=> $this->_requestId,
			'const'		=> microtime(true) - $this->_const,
			'data'		=> $data
		);
		
		try {
			call_user_func($this->_callback, $response);
		} catch (\Exception $ex) {
			echo "TcpClient callback failed![exception]" . $ex->getMessage() . PHP_EOL;
		}
		
		$this->_connectionManager();
	}
	
	/**
	 * @brief tcp client connection manager
	 */
	protected function _connectionManager() {
		//把对应的域名和端口对应的tcp长连接对象放入对象连接池
		$key = $this->_host . ':' . $this->_port;
		$this->_free();
		\Aha\Client\Pool::freeTcp($key, $this);
	}
	
	/**
	 * @brief 连接关闭的时候吧雷放回连接回收池
	 * @param \swoole_client $client
	 */
	public function onClose(\swoole_client $client) {
		parent::onClose($client);
		$poolName = $this->_host.':'.$this->_port;
		\Aha\Client\Pool::gcTcp($poolName, $this);
	}
	
	/**
	 * @brief 发生错误的时候 吧连接放回gc 出发close事件可能会gc重复 但是无所谓
	 * @param \swoole_client $client
	 */
	public function onError(\swoole_client $client) {
		parent::onError($client);
		$poolName = $this->_host.':'.$this->_port;
		\Aha\Client\Pool::gcTcp($poolName, $this);
	}
	
	/**
	 * @brief 对外请求开始loop
	 */
	public function loop( $callback = null ) {
		if ( null !== $callback ) {
			$this->setCallback($callback);
		}
		if ( $this->_objClient->sock &&  $this->_objClient->isConnected() ) {
			$this->_send($this->_objClient);//连接池取出的连接 send失败就关闭了吧
		} else {
			$this->_objClient->connect($this->_host, $this->_port, $this->_connectTimeout);
		}
		return parent::loop();
	}
	
	/**
	 * @brief 资源释放
	 */
	protected function _free() {
		$this->_requestId	= null;
		$this->_package	= null;
		$this->_timer = null;
		
		$this->_callback = null;//重要 释放了callback以后才能释放MVC
	}

}