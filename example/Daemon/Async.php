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
namespace Daemon;

class Async {
	
	/**
	 * @brief workers
	 * @var type 
	 */
	protected $_workers = array();
	
	/**
	 * @brief Aha实例
	 * @var type 
	 */
	protected $_objAha = null;

	/**
	 * @brief 异步后台进程初始化
	 */
	public function __construct() {
		define('APP_NAME','Daemon');
		define('APPLICATION_PATH', dirname(__DIR__));
		define('AHA_SRC_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'src');
		
		require_once AHA_SRC_PATH . '/Aha/Daemon.php';
		
		\Aha\Daemon::initLoader();
		
		$this->_objAha = \Aha\Daemon::getInstance(APP_NAME, 'product');
		$this->_objAha->getLoader()->registerNamespace(APP_NAME, APPLICATION_PATH);
		$this->_objAha->run();
	}
	
	/**
	 * @brief 启动子进程
	 */
	public function start() {
		$workerNum = $this->_objAha->getConfig()->get('aha','worker_num');
		for ( $i=0;$i<$workerNum;$i++ ) {
			$worker = new \Daemon\Asyncworker($this->_objAha);
			$process = new \swoole_process(array($worker, 'start'));
			//$process->daemon();
			$workerPid = $process->start();
			$this->_workers[$workerPid] = $process;
			$process->write("worker started!");
		}
		foreach($this->_workers as $process) {
			$workerPid = \swoole_process::wait();
			echo "[Worker Shutdown][WorkerId] $workerPid " . PHP_EOL;
			unset($this->_workers[$workerPid]);
		}
	}
	
}

$objDeamon = new \Daemon\Async();
$objDeamon->start();