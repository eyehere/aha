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

class Http extends Server {
	
	/**
	 * @brief 初始化 http server
	 * @param \swoole_http_server $server 在外部创建 本类只完成创建的繁琐的工作 
	 *			不会去做那种过度封装的工作 最大的自由留给开发者
	 * @return \Aha\Server
	 */
	public function __construct(\swoole_http_server $server, \string $appName = '') {
		parent::__construct($server, $appName);
		//HTTP_GLOBAL_ALL表示设置所有的超全局变量 使用超全局变量在异步非阻塞的模式下存在不可重入的问题
		$this->_objServer->setGlobal(HTTP_GLOBAL_ALL, HTTP_GLOBAL_GET | HTTP_GLOBAL_POST);
		return $this;
	}
	
	//初始化事件回调
	protected function _initEvents() {
		parent::_initEvents();
		$this->_objServer->on('request', array($this, 'onRequest') );
		$this->_objServer->on('close', array($this, 'onClose'));
		return $this;
	}
	
	/**
	 * @brief 收到一个完整的http请求后回调此函数
	 * @param \swoole_http_request $request Http请求信息对象，包含了header/get/post/cookie等信息
	 * @param \swoole_http_response $response Http响应对象，支持cookie/header/status等Http操作
	 * $request $response传递给其它函数时，不要加&引用符号
	 */
	public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
		
	}
	
	/**
	 * @brief TCP客户端链接关闭时，在worker进程中回调此函数
	 * onClose回调函数如果发生了致命错误，会导致链接泄漏。
	 * 通过netstat命令会看到大量的CLOSE_WAIT状态的TCP连接
	 */
	public function onClose(\swoole_server $server, \int $fd, \int $fromId) {
		
	}
	
}
