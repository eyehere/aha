<?php
/*
  +----------------------------------------------------------------------+
  | Application                                                                  |
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

namespace Application\Server;

define('AHA_SRC_PATH', dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'src');
require_once AHA_SRC_PATH . '/Aha/Bootstrap.php';
\Aha\Bootstrap::initLoader();

use \Aha\Server\Http;

class Sample extends Http {
	
	//Aha实例 
	private $_objAha = null;

	public function __construct() {
		$server = new \swoole_http_server('0.0.0.0', 9601);
		parent::__construct($server, 'Http-Sample');
		$arrSetting = $this->getConfig();
		$arrSetting['log_file'] = '../logs/Aha.log';
		$server->set( $arrSetting );
		$server->start();
	}
	
	/**
	 * @brief 初始化MVC
	 * @param \swoole_server $server
	 * @param int $workerId
	 */
	public function onWorkerStart(\swoole_server $server, int $workerId) {
		parent::onWorkerStart($server, $workerId);
		define('APP_NAME','Application');
		define('APPLICATION_PATH', dirname(dirname(__DIR__)));
		$this->_objAha = \Aha\Bootstrap::getInstance(APP_NAME, 'product');
		$this->_objAha->getLoader()->registerNamespace(APP_NAME, APPLICATION_PATH);
		$this->_objAha->run();
		/**
		$filter = new \Application\Filters\Track();
		$this->_objAha->getFilter()
				->registerPreRouter(array($filter, 'preRouterOne'))
				->registerPreRouter(array($filter, 'preRouterTwo'))
				->registerPostRouter(array($filter, 'postRouterOne'))
				->registerPostRouter(array($filter, 'postRouterTwo'))
				->registerPreDispatch(array($filter, 'preDispatchOne'))
				->registerPreDispatch(array($filter, 'preDispatchTwo'))
				->registerPostDispatch(array($filter, 'postDispatchOne'))
				->registerPostDispatch(array($filter, 'postDispatchTwo'));
		 */
	}
	
	/**
	 * @brief 请求初始化
	 * @param \swoole_http_request $request
	 * @param \swoole_http_response $response
	 */
	public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
		parent::onRequest($request, $response);
		try {
			$uri	= isset($request->server['request_uri']) ? $request->server['request_uri'] : '';
			$router = new \Aha\Mvc\Router($this->_objAha, $uri);
			$dispatcher = new \Aha\Mvc\Dispatcher($this->_objAha);
			$dispatcher->setRequest($request)->setResponse($response);
			$dispatcher->dispatch($router);
		} catch  (\Exception $ex) {
			$message = '[onRequest_callBack_excaption] [code]' . $ex->getCode() . ' [message]' .
				$ex->getMessage() . '[file]' . $ex->getFile() . '[line]' . $ex->getLine() . PHP_EOL;
			switch ( $ex->getCode() ) {
				case AHA_ROUTER_EXCEPTION : 
					$response->status(404);
					break;
				default :
					$response->status(500);
					break;
			}
			$response->end($message);
		}
	}
	
}

$httpServer = new \Application\Server\Sample();
