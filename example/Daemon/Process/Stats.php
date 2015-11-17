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
namespace Daemon\Process;

use \Daemon\Util\Log;
use \Daemon\Util\Monitor;

class Stats {
	
	//Aha实例
	protected $_objAha = null;
	
	//coroutine scheduler
	protected $_objScheduler = null;

	//process实例
	protected $_worker = null;
	
	//signal实例
	protected $_objSignal = null;
    
    //最近180s内的流量统计
    protected $_arrFlow = array();

    /**
	 * @brief 使用共享内存的数据
	 * @param type $objAha
	 * @return \Daemon\Asyncworker
	 */
	public function __construct($objAha) {
		$this->_objAha = $objAha;
        $this->_arrFlow = array();
		return $this;
	}
	
	/**
	 * @brief 启动子进程
	 * @param \swoole_process $worker
	 */
	public function create(\swoole_process $worker) {
		$this->_worker = $worker;
		$this->_worker->name('DAEMON_STATS');
		$this->_objSignal = new \Daemon\Library\Ipc\Signal();
		$this->_objSignal->initChildSignal();
		$this->_objScheduler = new \Aha\Coroutine\Scheduler();
		$this->_start();
        $this->_initTimer();
	}
	
	/**
	 * @brief 子进程进入控制调度
	 */
	protected function _start() {
		swoole_event_add($this->_worker->pipe, function($pipe) {
			$package = $this->_worker->read();
            $this->_stats($package);
		});
	}
    
    //初始化定时器
	protected function _initTimer() {
        /*
        $redoConf = $this->_objAha->getConfig()->get('aha','stats');
		$interval        = $redoConf['interval'];
		
		swoole_timer_tick($interval, function(){
            
            $coroutine = XXXXXXX();
            if ( $coroutine instanceof \Generator ) {
                $this->_objScheduler->newTask($coroutine);
                $this->_objScheduler->run();
            }
		});
        */
	}
    
    //整个应用的健康监测和监控
    protected function _stats($package) {
        $arrWorkers = unserialize($package);
        if ( !is_array($arrWorkers) || empty($arrWorkers) ) {
            return;
        }
        
        $total = 0;
        $arrRet = array();
        foreach ( $arrWorkers as $pid ) {
            $taskNum = \Daemon\Library\Ipc\Shared::getCurrentTaskNumByKey($pid);
            $arrRet["w_$pid"] = $taskNum;
            $total += $taskNum;
        }
        $arrRet['total'] = $total;
        
        $flow = \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get();
        $arrRet['flow'] = $flow;
        
        $driveConf		= $this->_objAha->getConfig()->get('aha','drive');
		$maxProcessNum	= intval($driveConf['max_process_num']);
        if ( $flow > $total  ) {
            $arrRet['MONITOR1'] = "FLOW_OVER_FLOW_TOTAL";
        }
        if ( $flow >= $maxProcessNum ) {
            $arrRet['MONITOR2'] = "FLOW_OVER_FLOW_MAX_PROCESS_NUM";
        }
        Log::statsLog()->debug($arrRet);
        
        $average = $this->_flowPredict($flow);
        
        if ( $average <= 0 ) {
            foreach ( $arrWorkers as $pid ) {
                \Daemon\Library\Ipc\Shared::setCurrentTaskTable($pid, array('taskNum'=>0));
            }
        }
    }
    
    //流量预测和清零
    protected function _flowPredict($flow) {
        if ( count($this->_arrFlow) >= 18 ) {
            array_shift($this->_arrFlow);
        }
        array_push($this->_arrFlow, $flow);
        $average = ceil(array_sum($this->_arrFlow)/count($this->_arrFlow));
        
        $driveConf = $this->_objAha->getConfig()->get('aha','drive');
		$maxFlow  = $driveConf['max_process_num'];
        if ( $average > $maxFlow*0.85 ) {
            \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->set(0);//根据流量预测 可能已经堵住了 清零自恢复
            Log::monitor()->error(array(Monitor::KEY=>Monitor::FLOW_OVER_PREDICT_RESET,'average'=>$average,'maxFlow'=>$maxFlow*0.85));
        }
        Log::statsLog()->debug( array('type'=>'flowPredict','average'=>$average,'maxFlow'=>$maxFlow,'point'=>$maxFlow*0.85) );
        
        return $average;
    }
	
}