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
namespace Aha\Server;

use \Aha\Network\Server;

class Udp extends Server {
	
	/**
	 * @brief 初始化 udp server
	 * @param \swoole_server $server 在外部创建 本类只完成创建的繁琐的工作 
	 *			不会去做那种过度封装的工作 最大的自由留给开发者
	 * @return \Aha\Server
	 */
	public function __construct(\swoole_server $server,  $appName = '', array $arrSetting = array() ) {
		parent::__construct($server, $appName, $arrSetting);
		return $this;
	}
	
	//初始化事件回调
	protected function _initEvents() {
		parent::_initEvents();
		//UDP
		$this->_objServer->on('packet', array($this, 'onPacket') );
		return $this;
	}
	
	/**
	 * @brief 收受到UDP数据包时回调此函数
	 * @param string $data 可以是文本或二进制内容
	 * @param array $clientInfo 包括address、port、server_socker 3
	 */
	public function onPacket(\swoole_server $server,  $data, $clientInfo) {
		
	}
	
}
