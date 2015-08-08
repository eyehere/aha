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

use Aha\Server\Tcp;

class TcpServer extends Tcp {
	
	//Aha实例 
	private $_objAha = null;

	public function __construct() {
		$server = new \swoole_server('0.0.0.0', 9602, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
		
		$this->setVarDirectory(dirname(__DIR__) .'/Var/');
		
		$arrSetting = array('log_file' => dirname(__DIR__) .'/Logs/Aha.log');
		parent::__construct($server, 'TcpServer', $arrSetting);
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
	 * @param \swoole_server $server
	 * @param \int $fd
	 * @param \int $fromId
	 * @param \string $data
	 */
	public function onReceive(\swoole_server $server, \int $fd, \int $fromId, \string $data) {
		parent::onReceive($server, $fd, $fromId, $data);
		try {
			$arrRequest = json_decode($data);
			$cmd	= isset($arrRequest['cmd']) ? $arrRequest['cmd'] : '';
			$router = new \Aha\Mvc\Router($this->_objAha, $cmd, '-');
			$dispatcher = new \Aha\Mvc\Dispatcher($this->_objAha, 'tcp');
			$dispatcher->setTcpClientFd($fd)->setTcpFromId($fromId)->setTcpPackage($data);
			$dispatcher->dispatch($router);
		} catch  (\Exception $ex) {
			$message = '[onRequest_callBack_excaption] [code]' . $ex->getCode() . ' [message]' .
				$ex->getMessage() . '[file]' . $ex->getFile() . '[line]' . $ex->getLine() . PHP_EOL;
			switch ( $ex->getCode() ) {
				case AHA_ROUTER_EXCEPTION : 
					$server->send($fd, "[status] 404 $message");
					break;
				default :
					$server->send($fd, "[status] 500 $message");
					break;
			}
		}
	}

}

$httpServer = new \Application\Server\TcpServer();
