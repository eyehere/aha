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
use \Aha\Network\Client;

class Rediscli extends Client {
	
	/**
	 * @brief 实例化redis client
	 * @param type $conf
	 * @return \Aha\Storage\Memory\Rediscli
	 */
	public function __construct($conf) {
		$host	= $conf['host'];
		$port	= $conf['port'];
		$timeout= $conf['timeout'];
		$client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $host, $port, $timeout);
		$this->_initConfig();
		return $this;
	}
	
	/**
	 * 初始化config 
	 */
	protected function _initConfig() {
		$setting = array(
			'open_tcp_nodelay'		=>  true
		);
		$this->_objClient->set($setting);
	}

	public function onReceive(\swoole_client $client, $data) {
		
	}
	
	protected function _free() {
		
	}

}