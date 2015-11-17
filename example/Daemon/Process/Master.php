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

class Master {
	
	/**
	 * @brief Aha实例
	 * @var type 
	 */
	protected $_objAha = null;
	
	//进程管理器
	protected $_objManager = null;

	//Master单例
	protected static $_instance = null;
	
	//协议包解析工具
	protected $_objProtocolPackage = null;

    //===========================================================
	//获取Master的单例
	public static function getInstance() {
		if ( null === self::$_instance ) {
			self::$_instance = new \Daemon\Process\Master();
		}
		return self::$_instance;
	}
	
	//获取Aha实例
	public function getAha() {
		return $this->_objAha;
	}
	
	/**
	 * @brief 异步后台进程初始化
	 */
	public function __construct() {
		define('APP_NAME','Daemon');
		define('APPLICATION_PATH', dirname(__DIR__) . '/../');
		define('AHA_SRC_PATH', dirname(__DIR__) . '/../../src');
		
		require_once AHA_SRC_PATH . '/Aha/Daemon.php';
		
		\Aha\Daemon::initLoader();
		
		$this->_objAha = \Aha\Daemon::getInstance(APP_NAME, 'dev');
		$this->_objAha->getLoader()->registerNamespace(APP_NAME, APPLICATION_PATH);
		$this->_objAha->run();
		
		$this->_objProtocolPackage = new \Aha\Process\Protocol\Package();
        
        \Daemon\Library\Ipc\Shared::initTable();
	}
	
	//创建主进程
	public function create() {
		$objSignal = \Daemon\Library\Ipc\Signal::getInstance();
		$objSignal->initSignal();
		
		$workerConf = $this->_objAha->getConfig()->get('aha','process');
        
		\Daemon\Library\Ipc\Shared::initAtomic();

		$this->_objManager = \Daemon\Library\Ipc\Manager::getInstance();
		$this->_objManager->createTaskWorker($this->_objAha, $workerConf['task_worker_num']);
		$this->_objManager->createRedoWorker($this->_objAha, $workerConf['redo_worker_num']);
		$this->_objManager->createDriveWorker($this->_objAha, $workerConf['drive_worker_num']);
		$process = $this->_objManager->createStatsWorker($this->_objAha, $workerConf['stats_worker_num']);
		$process->name('DAEMON_MASTER');
        
        $this->_initTimer();
	}
    
    //初始化一个定时器
    protected function _initTimer() {
        $redoConf = $this->_objAha->getConfig()->get('aha','stats');
        $statsInterval   = $redoConf['stats_interval'];
        swoole_timer_tick($statsInterval, function(){
            $objManager = \Daemon\Library\Ipc\Manager::getInstance();
            $workers = $objManager->getWorkersPid();
            $arrStatsWorker = $objManager->getStatsWorker();
            if ( !empty($arrStatsWorker) ) {
                list($statsPid,$statsWorker) = each($arrStatsWorker);
                $statsWorker->write(serialize($workers));
            }
        });
    }

    //==================================================================
	
	//管道消息处理
	public function dispatch($process) {
		$this->_objProtocolPackage->readPipe($process);
		
		$arrPackage = $this->_objProtocolPackage->getPackages();
        
		if ( empty($arrPackage) ) {
			return;
		}
		foreach ( $arrPackage as $package ) {
			$message = json_decode($package, true);
			$cmd	 = intval($message['cmd']);

			switch ($cmd) {
				case Constant::PACKAGE_TYPE_COMPLETE ://来自task进程 通知drive进程继续发任务
					$this->_taskWorkerComplete($message);
					break;
				default:
					$this->_notifyTaskWorker($message);//使用排除的方式，需要特殊处理的消息内容放在上面 其它的都转发到task进程
					break;
			}
		}
	}
	
	//通知task进程
	protected function _notifyTaskWorker($arrPackage) {
		$package = json_encode($arrPackage);
		
		//负载均衡一个worker进程
		$distributeWorkers = $this->_objManager->getTaskWorker();
		$pid = $this->_taskWorkerBalance($distributeWorkers);
		
		$ret = $distributeWorkers[$pid]->write($package . Constant::PACKAGE_EOF);
		if ( false === $ret ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::IPC_PIPE_WRITE_ERR, '#package'=>$package));
			Log::redoLog(array('redo'=>$package));
			return false;
		}
        
		$res = \Daemon\Library\Ipc\Shared::getCurrentTaskTable()->incr($pid,'taskNum', 1);
		if ( false === $res ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::TABLE_INCR_ERR, 'res'=>$res,'#package'=>$package));
		}
        Log::billLog()->debug( array('#package'=>$package, 'type'=>'task') );
		return true;
	}
	
	//发布任务进程的负载均衡控制
	protected function _taskWorkerBalance($distributeWorkers) {
		$pids	= array_keys($distributeWorkers);
		$index	= mt_rand(0, count($pids)-1);
		$pid	= $pids[$index];
		
		$driveConf  = $this->_objAha->getConfig()->get('aha','drive');
		$maxCnt		= intval($driveConf['max_task']);
		$taskNum = \Daemon\Library\Ipc\Shared::getCurrentTaskNumByKey($pid);
		if ( $taskNum < $maxCnt ) {
			return $pid;
		}
		
		foreach ( $pids as $sparePid ) {
			$taskNum = \Daemon\Library\Ipc\Shared::getCurrentTaskNumByKey($sparePid);
			if ( $taskNum < $maxCnt ) {
				return $sparePid;
			}
		}
		
		return $pid;
	}
	
	//任务进程完成任务计数器维护
	protected function _taskWorkerComplete($package) {
		$pid = $package['content']['pid'];
		$res = \Daemon\Library\Ipc\Shared::getCurrentTaskTable()->decr($pid,'taskNum', 1);
		if ( false === $res ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::TABLE_DECR_ERR, '#package'=>$package));
		}
        
        $driveWorker = $this->_objManager->getDriveWorker();
		list($drivePid,$driveProcess) = each($driveWorker);
		
		$ret = $driveProcess->write(json_encode($package) . Constant::PACKAGE_EOF);
		if ( false === $ret ) {
			Log::monitor()->error(array(Monitor::KEY=>Monitor::IPC_PIPE_WRITE_ERR, '#package'=>$package));
			Log::redoLog(array('redo'=>$package));
			return false;
		}
        Log::billLog()->debug( array('#package'=>$package, 'type'=>'complete') );
		return true;
	}
	
}

$objDeamon = \Daemon\Process\Master::getInstance();
$objDeamon->create();