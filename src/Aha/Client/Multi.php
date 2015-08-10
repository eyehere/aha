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
namespace Aha\Multi;

class Multi {
	
	/**
	 * @brief 注册client
	 * @var type 
	 */
	protected $_arrClients = array();

	/**
	 * @ after loop callback
	 * @var type 
	 */
	protected $_callback = null;
	
	/**
	 * @brief 注册并行的client
	 * @param \Aha\Network\Client $client
	 * @return \Aha\Multi\Multi
	 */
	public function register(\Aha\Network\Client $client) {
		array_push($this->_arrClients, $client);
		return $this;
	}
	
	/**
	 * @brief 并行执行注册的请求
	 * @param \callbale $callback
	 * @return \Aha\Multi\Multi
	 */
	public function loop(\callbale $callback) {
		$this->_callback = $callback;
		
		return $this;
	}
	
}