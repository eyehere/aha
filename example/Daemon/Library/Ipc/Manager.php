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

class Manager {
	
	const WORKER_TYPE_DRIVER		= 1;
	const WORKER_TYPE_TASK			= 2;
	const WORKER_TYPE_STATS			= 3;
	const WORKER_TYPE_REDO			= 4;
	
	//驱动工作进程
	protected $_drive_workers = array();
	
	//工作进程
	protected $_task_workers = array();
	
	//状态监测进程
	protected $_stats_workers = array();
	
	//redo进程
	protected $_redo_workers = array();
	
	//manager管理器单例
	protected static $_instance = null;

	//=============================================================
	
	//获取单例
	public static function getInstance() {
		if ( null === self::$_instance ) {
			self::$_instance = new \Daemon\Library\Ipc\Manager();
		}
		return self::$_instance;
	}
	
	//管道事件监听
	protected function _initPipeEvents($process, $workerType) {
		\Daemon\Library\Ipc\Shared::setCurrentTaskTable($process->pid, array('taskNum'=>0,'workerType'=>$workerType));
		
		$objMaster = \Daemon\Process\Master::getInstance();
		
		swoole_event_add($process->pipe, function($pipe) use ($process, $objMaster) {
			$objMaster->dispatch($process);
		});
	}
	
	//创建任务进程
	public function createTaskWorker($objAha, $workerNum) {
		$processNum = 0;
		do {
			$worker = new \Daemon\Process\Task($objAha);
			$process = new \swoole_process(array($worker, 'create'));
			$workerPid = $process->start();
			$this->_task_workers[$workerPid] = $process;
			$this->_initPipeEvents($process,  self::WORKER_TYPE_TASK);
		} while ( ++$processNum < $workerNum );
	}
	
	//创建重试进程
	public function createRedoWorker($objAha, $workerNum) {
		$processNum = 0;
		do {
			$worker = new \Daemon\Process\Redo($objAha);
			$process = new \swoole_process(array($worker, 'create'));
			$workerPid = $process->start();
			$this->_redo_workers[$workerPid] = $process;
			$this->_initPipeEvents($process, self::WORKER_TYPE_REDO);
		} while ( ++$processNum < $workerNum );
	}
	
	//创建驱动进程
	public function createDriveWorker($objAha, $workerNum) {
		$processNum = 0;
		do {
			$worker = new \Daemon\Process\Drive($objAha);
			$process = new \swoole_process(array($worker, 'create'));
			$workerPid = $process->start();
			$this->_drive_workers[$workerPid] = $process;
			$this->_initPipeEvents($process, self::WORKER_TYPE_DRIVER);
		} while ( ++$processNum < $workerNum );
	}
	
	//创建状态监测进程
	public function createStatsWorker($objAha, $workerNum) {
		$process = null;
		$processNum = 0;
		do {
			$worker = new \Daemon\Process\Stats($objAha);
			$process = new \swoole_process(array($worker, 'create'));
			$workerPid = $process->start();
			$this->_stats_workers[$workerPid] = $process;
			$this->_initPipeEvents($process, self::WORKER_TYPE_STATS);
		} while ( ++$processNum < $workerNum );
		return $process;
	}
	
	//================================================================
	//检测驱动进程是否存在
	public function hasDriveWorker($pid) {
		return isset($this->_drive_workers[$pid]);
	}
	
	//删除驱动进程
	public function delDriveWorker($pid) {
		unset($this->_drive_workers[$pid]);
	}
    
    //获取驱动进程
    public function getDriveWorker() {
        return $this->_drive_workers;
    }
	
	//获取任务进程
	public function getTaskWorker() {
		return $this->_task_workers;
	}
	
	//检测进程是否存在
	public function hasTaskWorker($pid) {
		return isset($this->_task_workers[$pid]);
	}
	
	//删除任务进程
	public function delTaskWorker($pid) {
		unset($this->_task_workers[$pid]);
	}
	
	//检测重试进程是否存在
	public function hasRedoWorker($pid) {
		return isset($this->_redo_workers[$pid]);
	}
	
	//删除重试进程
	public function delRedoWorker($pid) {
		unset($this->_redo_workers[$pid]);
	}
	
	//检测是否有状态监测进程
	public function hasStatsWorker($pid) {
		return isset($this->_stats_workers[$pid]);
	}
	
	//删除状态监测进程
	public function delStatsWorker($pid) {
		unset($this->_stats_workers[$pid]);
	}
    
    //获取状态监测进程
    public function getStatsWorker() {
        return $this->_stats_workers;
    }
	
	//获取所有子进程的PID
	public function getWorkersPid() {
		return array_merge(
				array_keys($this->_drive_workers),
				array_keys($this->_task_workers),
				array_keys($this->_redo_workers),
				array_keys($this->_stats_workers)
		);
	}
	
}