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

use Aha\Server\Http;

class HttpSample extends Http {

	public function __construct() {
		$server = new \swoole_http_server('0.0.0.0', 9601);
		parent::__construct($server, 'Http-Sample');
		$server->start();
	}
	
	/**
	 * @brief 初始化Yaf
	 * @param \swoole_server $server
	 * @param int $workerId
	 */
	public function onWorkerStart(\swoole_server $server, int $workerId) {
		parent::onWorkerStart($server, $workerId);
	}
	
	/**
	 * @brief 请求初始化
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 */
	public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
		parent::onRequest($request, $response);
		try {
			$response->end(json_encode($request));
		} catch (Exception $ex) {
			$message = '[onRequest_callBack_excaption] [code]' . $ex->getCode() . ' [message]' .
				$ex->getMessage() . '[file]' . $ex->getFile() . '[line]' . $ex->getLine() . PHP_EOL;
			$response->end($message);
		}
	}
	
}

$httpServer = new HttpSample();
