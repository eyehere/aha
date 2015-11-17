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

class Task {
	
	//Aha实例
	protected $_objAha = null;
	
	//coroutine scheduler
	protected $_objScheduler = null;

	//process实例
	protected $_worker = null;
	
	//signal实例
	protected $_objSignal = null;
    
    //协议包解析工具
	protected $_objProtocolPackage = null;

	/**
	 * @brief 使用共享内存的数据
	 * @param type $objAha
	 * @return \Daemon\Asyncworker
	 */
	public function __construct($objAha) {
		$this->_objAha = $objAha;
        $this->_objProtocolPackage = new \Aha\Process\Protocol\Package();
		return $this;
	}
	
	/**
	 * @brief 启动子进程
	 * @param \swoole_process $worker
	 */
	public function create(\swoole_process $worker) {
		$this->_worker = $worker;
		$this->_worker->name('DAEMON_TASK');
		$this->_objSignal = new \Daemon\Library\Ipc\Signal();
		$this->_objSignal->initChildSignal();
		$this->_objScheduler = new \Aha\Coroutine\Scheduler();
		$this->_start();
	}
	
	/**
	 * @brief 子进程进入控制调度
	 */
	protected function _start() {
		swoole_event_add($this->_worker->pipe, function($pipe) {
			$this->_main();
		});
	}
    
    //消息处理的主流程
    protected function _main () {
        $this->_objProtocolPackage->readPipe($this->_worker);
		
        $arrPackage = $this->_objProtocolPackage->getPackages();
        
        if ( empty($arrPackage) ) {
            return;
        }

        foreach ( $arrPackage as $package ) {
            $message = json_decode($package, true);
            $cmd	 = intval($message['cmd']);
            $coroutine = null;

            switch ($cmd) {
                case Constant::PACKAGE_TYPE_TASK :
                    $coroutine = $this->_doTask($message);
                    break;
                default:
                    Log::appLog()->warning(array(Monitor::KEY=>Monitor::UNEXPECTED_ERR, '^package'=>$package));
                    break;
            }
            Log::billLog()->debug( array('^package'=>$package, 'type'=>'_mainDistribute') );
			
            if ( $coroutine instanceof \Generator ) {
                $this->_objScheduler->newTask($coroutine);
                $this->_objScheduler->run();
            }
        }

        $this->_objScheduler->run(); 
    }

    //收到来自主进程的消息
	protected function _doTask($package) {
		$objModel = new \Daemon\Models\Demo($this->_objAha);
        
        $grantRet = ( yield $objModel->doTask($package) );
        
		if ( false === $grantRet ) {
            Log::redoLog()->debug(array('redo'=>$package));
        }
        
        $arrResponse = array(
            'cmd'       => Constant::PACKAGE_TYPE_COMPLETE,
            'content'   => array('pid'=>$this->_worker->pid)
        );
        $this->_send($arrResponse);
        
        yield AHA_DECLINED;
	}
    
    //发送消息给drive进程 完成的ack
    protected function _send($package) {
		$ret = $this->_worker->write(json_encode($package) . Constant::PACKAGE_EOF);
		if ( false === $ret ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::IPC_PIPE_WRITE_ERR, '^package'=>$package));
			Log::redoLog()->debug(array('redo'=>$package));
		}
		 
        \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->sub(1);//在distribute进程减1有最短路径
        if ( \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get() > (2 << 16) ) {
            \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->set(0);
        }
         
		Log::billLog()->debug( array('^package'=>$package, 'type'=>'_distributeResponse') );
	}
	
}