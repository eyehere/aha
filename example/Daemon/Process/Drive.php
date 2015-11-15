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

class Drive {
	
	//Aha实例
	protected $_objAha = null;
	
	//coroutine scheduler
	protected $_objScheduler = null;

	//process实例
	protected $_worker = null;
	
	//signal实例
	protected $_objSignal = null;
	
	//任务处理中状态标记
	protected $_bolProcessing = false;
    //是否需要trigger的标记位
    protected $_bolNeedTrigger = false;

    //任务生成器
    protected $_objGrantIterator = null;
	protected $_grantTasks = null;

	//协议包解析工具
	protected $_objProtocolPackage = null;

	/**
	 * @brief 使用共享内存的数据
	 * @param type $objAha
	 * @return 
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
		$this->_worker->name('DAEMON_DRIVE');
		
		$this->_objScheduler = new \Aha\Coroutine\Scheduler();
		
		$this->_objSignal = new \Daemon\Library\Ipc\Signal();
		$this->_objSignal->initChildSignal();
		
		$this->_initPipeEvents();
		
		$coroutine = $this->_start();
		if ( $coroutine instanceof \Generator ) {
			$this->_objScheduler->newTask($coroutine);
			$this->_objScheduler->run();
		}
	}
	
	/**
	 * @brief 子进程进入控制调度
	 */
	protected function _start() {
		//进程刚拉起来的时候先做任务的状态复位(重启、升级、进程异常退出在拉起的情况)
		$objModel = new \Daemon\Models\XX($this->_objAha);
		$ret = ( yield $objModel->XXXX() );
		
		//重置中间状态失败 退出事件循环 等待master重新拉起进程
		if ( false === $ret ) {
			Log::monitor(array(Monitor::KEY=>Monitor::RESET_PROCESSING_ERR));
			\swoole_event_exit();
		} else {
			//重置中间状态成功 初始化定时器
			$this->_initTimer();
		}
        
	}
	
	//收到来自主进程的消息 消息解析
	protected function _initPipeEvents() {
		swoole_event_add($this->_worker->pipe, function($pipe) {
			$this->_onAckProcess();
		});
	}
	
	//初始化定时器
	protected function _initTimer() {
		$driveConf = $this->_objAha->getConfig()->get('aha','drive');
		$interval = $driveConf['interval'];
		
		swoole_timer_tick($interval, function(){
			$coroutine = null;
			if ( $this->_bolProcessing ) {
                if ( $this->_bolNeedTrigger ) {
                    Log::appLog()->debug(array('_bolProcessing'=>true,'_bolNeedTrigger'=>true));
                    $this->_bolNeedTrigger = false;
                    $coroutine = $this->_trigger();
                } else {
                    Log::appLog()->debug(array('_bolProcessing'=>true,'_bolNeedTrigger'=>false));
                }
			} else {
				$coroutine = $this->_taskDetect();
			}

			if ( $coroutine instanceof \Generator ) {
				$this->_objScheduler->newTask($coroutine);
				$this->_objScheduler->run();
			}
			
		});
	}
	
	//检测
	protected function _taskDetect() {
		//优先级最高
		$this->_bolProcessing = true;
		
		//优先级其次 任务发布
		$tasks = (yield $this->_getTasks());
		if ( !empty($tasks) ) {
			$ret = (yield $this->_taskProcessing($tasks));
			if ( $ret === AHA_AGAIN ) {
				goto schedulerAgain;
			} else {
				goto nextTimerSchedule;
			}
		}
		
		nextTimerSchedule:
			$this->_bolProcessing = false;
		schedulerAgain:
	}
	
	//是否有等待发布的任务
	protected function _getTasks() {
		$objGrantLogic = new \Daemon\Logic\Grant($this->_objAha);
		$grantTasks = ( yield $objGrantLogic->getDistributeTasks() );
		
		yield ($grantTasks);
	}

	//任务处理
	protected function _taskProcessing($grantTasks) {
		$objGrantLogic = new \Daemon\Logic\Grant($this->_objAha);
		$grantRet = ( yield $objGrantLogic->lockDistributeProcess($grantTasks, $this->_worker->pid) );
		if ( false === $grantRet ) {
			yield (false);
		} else {
			
			$ret = (yield $this->_sendGrantTasks($grantTasks));
			yield ($ret);
		}
	}
	
	//发送审核通过等待发布的任务
	protected function _sendGrantTasks($grantTasks = null) {
		$driveConf		= $this->_objAha->getConfig()->get('dtc','drive');
		$maxProcessNum	= intval($driveConf['max_process_num']);

		$currentTaskNum = \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get();
		
        $record = ( yield $this->_getTaskRecord($grantTasks) );
        
        while( $currentTaskNum < $maxProcessNum && is_array($record) ) {
            
            $this->_filterAndSend($record, $driveConf);
            
            $record = (yield $this->_getTaskRecord($grantTasks));
			$currentTaskNum = \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->get();
        }
		
		if ( is_array($record) || AHA_AGAIN === $record ) {
            
            if ( is_array($record) ) {
                $this->_filterAndSend($record, $driveConf);
            }
            
            $this->_bolNeedTrigger = true;
			yield (AHA_AGAIN); 
		} else {
            //发送完毕 更新任务组状态为已经完成中止
			$taskGid = $this->_grantTasks['task_group'][0]['id'];
			$objGrantLogic = new \Daemon\Logic\Grant($this->_objAha);
            
            $grantDoneRet = ( yield $objGrantLogic->grantDone(array($taskGid)) );
            
			$this->_clean();
            
			yield (AHA_DECLINED);
		}
	}
    
    //发送之前过一下反作弊的过滤
    protected function _filterAndSend( $record,$driveConf ) {
        //发送之前进行黑名单的过滤
        $objSpam = \Daemon\Models\Spam\Blacklist::getInstance($driveConf['spam_file']);
        if ( !$objSpam->isBlack($record['driver_id']) ) {
            $package = array(
                'cmd'       => Constant::PACKAGE_TYPE_DISTRIBUTE,
                'content'   => $record
            );
            $this->_send($package);
        } else {
            Log::appLog()->notice(array('spamBlackHited'=>$record));
        }
    }

    //获取一条一条的任务记录
     protected function _getTaskRecord($grantTasks = null) {
        if ( null === $grantTasks && null === $this->_objGrantIterator ) {
            Log::monitor()->error(array(Monitor::KEY=>Monitor::UNEXPECTED_ERR, 'grantTasks'=>$grantTasks, 'generator'=>  $this->_objGrantIterator));
            yield false;
        }
        if ( null === $this->_objGrantIterator ) {
            $objReactor = new \Daemon\Logic\Reactor($this->_objAha);
			$this->_grantTasks = $grantTasks;
            $this->_objGrantIterator = ( yield $objReactor->getTaskGenerator($grantTasks) );
        }
        
        if ( $this->_objGrantIterator->valid() ) {
            $record = (yield $this->_objGrantIterator->current());
            $ret = (yield $this->_objGrantIterator->next());
            
            yield $record;
        } else {
			
            yield AHA_DECLINED;
        }
    }
    
    //发任务的时候 迭代器使用完毕进程资源重置
    protected function _clean() {
        $this->_objGrantIterator = null;
		$this->_grantTasks = null;
		
        $this->_bolProcessing = false;
        $this->_bolNeedTrigger = false;
        
        \Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->set(0);//每次跑完一个任务 进行一次清零操作(其实没必要 压测看再说)
		
    }
	
	protected function _send($package) {
		$ret = $this->_worker->write(json_encode($package) . Constant::PACKAGE_EOF);
		if ( false === $ret ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::IPC_PIPE_WRITE_ERR, '%package'=>$package));
			Log::redoLog()->debug(array('redo'=>$package));
		} else {
			\Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->add(1);
		}
        Log::billLog()->debug( array('%package'=>$package, 'type'=>'send') );
	}
    

    //发出去的消息处理完成 给一个ACK的处理流程
    protected function _onAckProcess() {
        $this->_objProtocolPackage->readPipe($this->_worker);
		
        $arrPackage = $this->_objProtocolPackage->getPackageArr();
        if ( empty($arrPackage) ) {
            return AHA_AGAIN;;
        }

        foreach ( $arrPackage as $package ) {
            $message = json_decode($package, true);
            $cmd	 = intval($message['cmd']);
            
            switch ($cmd) {
                case Constant::PACKAGE_TYPE_COMPLETE ://来自distribute进程 通知drive进程继续发任务
                    //\Daemon\Library\Ipc\Shared::getMaxTaskNumAtomic()->sub(1);//在distribute进程减1有最短路径
                    break;
                default:
                    Log::appLog()->warning(array(Monitor::KEY=>Monitor::UNEXPECTED_PACK, '%package'=>$package));
                    break;
            }
            Log::billLog()->debug( array('%package'=>$package, 'type'=>'onAckProcess') );
        }
        
        if ( $this->_bolNeedTrigger ) {
            $this->_bolNeedTrigger = false;
            $this->_onAckTrigger();
        }
        
        return AHA_AGAIN;
    }
    
    //ACK触发器
    protected function _onAckTrigger () {
        $coroutine = $this->_trigger();
        if ( $coroutine instanceof \Generator ) {
            $this->_objScheduler->newTask($coroutine);
            $this->_objScheduler->run();
        }
    }
	
	//当收到distribute进程完成任务的ack之后的出发 或者定时器任务处理中时候也触发一次
	protected function _trigger() {

        $ret = AHA_DECLINED;
        if ( null !== $this->_objGrantIterator ) {
            $ret = (yield $this->_sendGrantTasks());
        }
        
        yield $ret;
        
	}
    
}