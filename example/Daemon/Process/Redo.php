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
use \Daemon\Util\Constant;

class Redo {
    
    const MAX_RETRY_TIMES = 3;//最大重试次数
	
	//Aha实例
	protected $_objAha = null;

	//process实例
	protected $_worker = null;
	
	//signal实例
	protected $_objSignal = null;
    
    //redo生成器
    protected $_objRedoIterator = null;
    
    //是否正在工作中
    protected $_bolWorking = false;

    /**
	 * @brief 使用共享内存的数据
	 * @param type $objAha
	 * @return \Daemon\Asyncworker
	 */
	public function __construct($objAha) {
		$this->_objAha = $objAha;
		return $this;
	}
	
	/**
	 * @brief 启动子进程
	 * @param \swoole_process $worker
	 */
	public function create(\swoole_process $worker) {
		$this->_worker = $worker;
		$this->_worker->name('DAEMON_REDO');
		$this->_objSignal = new \Daemon\Library\Ipc\Signal();
		$this->_objSignal->initChildSignal();
		$this->_start();
        $this->_initTimer();
	}
	
	/**
	 * @brief 子进程进入控制调度
	 */
	protected function _start() {
		swoole_event_add($this->_worker->pipe, function($pipe) {
			$package = $this->_worker->read();
		});
	}
    
    //初始化定时器
	protected function _initTimer() {
        
        $redoConf = $this->_objAha->getConfig()->get('aha','redo');
		$interval        = $redoConf['interval'];
        $triggerInterval = $redoConf['trigger_interval'];
		
        //当前周期内 把上个周期内失败的进行重试
		swoole_timer_tick($interval, function(){
            $this->_redo();
		});
        
        //每个时钟周期检查是否有redo没有发完的消息包
        swoole_timer_tick($triggerInterval, function(){
			$this->_trigger();
		});
        
	}
    
    //重试进程
    protected function _redo() {
        //如果当前重试的内容没有重试完成 写入redolog 等待下一次重试吧
        if ( null !== $this->_objRedoIterator ) {
            //强制停止触发器的工作
            $this->_bolWorking = true;
            //当前迭代器的内容写入当前文件
            $this->_nextRedo();
        }
        
        //检查新的重试文件 regenerate新的重试文件
        $this->_objRedoIterator = $this->_getRedoIterator();
        $this->_bolWorking = false;
        $this->_trigger();
    }
    
    //时钟周期检查
    protected function _trigger() {
        //如果正在工作中
        if ( null === $this->_objRedoIterator ) {
            Log::redoBill()->debug(array('redo_trigger'=>'healthy'));
            return;
        }
        
		$driveConf		= $this->_objAha->getConfig()->get('aha','drive');
		$maxProcessNum	= intval($driveConf['max_process_num']);

		$currentTaskNum = \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get();
        
        while( !$this->_bolWorking && $currentTaskNum < $maxProcessNum && $this->_objRedoIterator->valid() ) {
			$package = $this->_objRedoIterator->current();
            $this->_send($package);
            $this->_objRedoIterator->next();
			$currentTaskNum = \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get();
        }
		
		if ( !$this->_objRedoIterator->valid() ) {
			$this->_objRedoIterator = null;
            Log::redoBill()->debug(array('redo_trigger'=>'done'));
        }
    }
    
    //生成redo迭代器
    protected function _getRedoIterator() {
        
        $redoFile = Log::getLastRedoFile();
        if ( file_exists($redoFile) && ($handle = fopen($redoFile, "r")) ) {
			while ( ($line = fgets($handle)) !== false) {
                $arrPackage = array();
				if ( preg_match('/redo=(.*)\[BACK_TRACE\]/', $line, $arrElements) ) {
                    $arrPackage = unserialize(trim($arrElements[1]));
                    if ( !isset($arrPackage['retry']) ) {
                        $arrPackage['retry'] = 1;
                    } 
                    elseif ( $arrPackage['retry'] > self::MAX_RETRY_TIMES ) {
                        Log::redoBill()->error( array('redo'=>'more then max_retry_times!', 'package'=>$arrPackage) );
                        Log::monitor()->error( array(Monitor::KEY=>Monitor::OVER_MAX_RETRY_TIMES, '&package'=>$arrPackage) );
                        continue;
                    }
                    else {
                        $arrPackage['retry'] += 1;
                    }
                }
                yield $arrPackage;
			}
			fclose($handle);
			Log::redoBill()->debug(array('redoIterator'=>'retry done!','file'=>$redoFile));
		} else {
			Log::redoBill()->notice(array('redoIterator'=>'last ten minutes has no failed record!','file'=>$redoFile));
		}
        
    }
    
    //再次重试
    protected function _nextRedo() {
        while( $this->_objRedoIterator->valid() ) {
			$package = $this->_objRedoIterator->current();
            Log::redoLog()->debug(array('redo'=>$package));
            $this->_objRedoIterator->next();
        }
        $this->_objRedoIterator = null;
    }
    
    protected function _send($package) {
		$ret = $this->_worker->write(json_encode($package) . Constant::PACKAGE_EOF);
		if ( false === $ret ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::IPC_PIPE_WRITE_ERR, '&package'=>$package));
			Log::redoLog()->debug(array('redo'=>$package));
		} else {
			\Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->add(1);
		}
        Log::billLog()->debug( array('&package'=>$package, 'type'=>'send') );
	}
	
}