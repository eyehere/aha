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
namespace Daemon\Library\Ipc;

class Signal {
	
	//是否收到退出进程信号
	protected $_sigterm = false;

	//信号单例
	protected static $_instance = null;
	
	//signal实例
	protected $_objSignal = null;
	
	//获取信号单例
	public static function getInstance() {
		if ( null === self::$_instance ) {
			self::$_instance = new \Daemon\Library\Ipc\Signal();
		}
		return self::$_instance;
	}

	//信号初始化
	public function initSignal() {
		\swoole_process::signal(SIGCHLD,array($this, 'signalCallback'));
		\swoole_process::signal(SIGUSR1,array($this, 'signalCallback'));
	}
	
	//信号回调
	public function signalCallback($signalNo) {
		switch ($signalNo) {
			case SIGCHLD :
				$this->_waitChildProcess();
				break;
			case SIGUSR1 :
				$this->_killAllProcess();
				break;
			default;
		}
	}
	
	//等待子进程，并重新拉起一个进程
	protected function _waitChildProcess() {
        //必须为false，非阻塞模式
        //信号发生时可能同时有多个子进程退出
        //必须循环执行wait直到返回false
        $objManager = \Daemon\Library\Ipc\Manager::getInstance();
		while( $result = \swoole_process::wait(false) ) {
            $pid = $result['pid'];
            if ( $objManager->hasDriveWorker($pid) ) {
                $objManager->delDriveWorker($pid);
                \Daemon\Library\Ipc\Shared::delCurrentTaskTable($pid);
                if ( !$this->_sigterm ) {
                    $objManager->createDriveWorker(\Daemon\Process\Master::getInstance()->getAha(), 1);
                }
            } 
            elseif ( $objManager->hasTaskWorker($pid) ) {
                $objManager->delTaskWorker($pid);
                \Daemon\Library\Ipc\Shared::delCurrentTaskTable($pid);
                if ( !$this->_sigterm ) {
                    $objManager->createDirtributeWorker(\Daemon\Process\Master::getInstance()->getAha(), 1);
                }
            }
            elseif ( $objManager->hasRedoWorker($pid) ) {
                $objManager->delRedoWorker($pid);
                \Daemon\Library\Ipc\Shared::delCurrentTaskTable($pid);
                if ( !$this->_sigterm ) {
                    $objManager->createRedoWorker(\Daemon\Process\Master::getInstance()->getAha(), 1);
                }
            }
            elseif ( $objManager->hasStatsWorker($pid) ) {
                $objManager->delStatsWorker($pid);
                \Daemon\Library\Ipc\Shared::delCurrentTaskTable($pid);
                if ( !$this->_sigterm ) {
                    $objManager->createStatsWorker(\Daemon\Process\Master::getInstance()->getAha(), 1);
                }
            }
            else {

            }
        }
		
		//如果是SIGTERM信号 并且子进程都已经退出了 父进程终止
		$workers = \Daemon\Library\Ipc\Shared::getCurrentTaskTable();
		if ( $this->_sigterm && !($workers->count()) ) {
			\swoole_event_exit();
		}
	}
	
	//通知子进程退出
	protected function _killAllProcess() {
		$this->_sigterm = true;
		$workers = \Daemon\Library\Ipc\Manager::getInstance()->getWorkersPid();
		foreach ($workers as $pid) {
			\swoole_process::kill($pid, SIGUSR1);
		}
	}
	
	//信号初始化
	public function initChildSignal() {
		\swoole_process::signal(SIGUSR1,array($this, 'signalChildCallback'));
	}
	
	//信号回调
	public function signalChildCallback($signalNo) {
		switch ($signalNo) {
			case SIGUSR1 :
				swoole_event_exit();
				break;
			default;
		}
	}
	
}