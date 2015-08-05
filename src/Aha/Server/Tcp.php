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
namespace \Aha\Server;

use \Aha\Network\Server;

class Tcp extends Server {
	
	/**
	 * @brief 初始化 tcp server
	 * @param \swoole_server $server 在外部创建 本类只完成创建的繁琐的工作 
	 *			不会去做那种过度封装的工作 最大的自由留给开发者
	 * @return \Aha\Server
	 */
	public function __construct(\swoole_server $server, string $appName = '') {
		parent::__construct($server, $appName);
		return $this;
	}
	
	//初始化事件回调
	protected function _initEvents() {
		parent::_initEvents();
		$this->_objServer->on('connect',array($this, 'onConnect') );
		$this->_objServer->on('receive', array($this, 'onReceive') );
		$this->_objServer->on('close', array($this, 'onClose'));
		return $this;
	}
	
	/**
	 * @brief 有新的连接进入时，在worker进程中回调
	 * UDP协议下没有onConnect/onClose事件
	 */
	public function onConnect(\swoole_server $server, int $fd, int $fromId) {
		
	}
	
	/**
	 * @brief 接受到数据时回调此函数，发生在worker进程中
	 * UDP协议下：可以保证总是收到一个完整的包，最大长度不超过64K
	 * UDP协议下：$fd是对应客户端的IP，$fromId是客户端的端口
	 * TCP协议下：无法保证数据包的完整性，可能收到多个请求包或者同一个请求的部分数据
	 * 开启了eof_check/length_check/open_http_protocol,$data可能超过64K，
	 *								最大$server->setting['package_max_length']
	 * 开启open_eof_check/open_length_check/open_http_protocol,可以保证数据包的完整性
	 * @param string $data 可能是文本或者二进制内容
	 */
	public function onReceive(\swoole_server $server, int $fd, int $fromId, string $data) {
		
	}
	
	/**
	 * @brief TCP客户端链接关闭时，在worker进程中回调此函数
	 * onClose回调函数如果发生了致命错误，会导致链接泄漏。
	 * 通过netstat命令会看到大量的CLOSE_WAIT状态的TCP连接
	 */
	public function onClose(\swoole_server $server, int $fd, int $fromId) {
		
	}
	
}
