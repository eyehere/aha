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
namespace Aha\Coroutine;

class Scheduler {
	
	/**
	 * @brief 调度器实例 单例模式
	 * @var type 
	 */
	protected static $_instance = null;

	/**
	 * @brief taskId生成器
	 * @var type 
	 */
	protected $_maxTaskId = 0;

	/**
	 * @brief task队列
	 * @var type 
	 */
	protected $_taskQueue;
	
	/** 
	 * @brief 调度器单利获取
	 * @return type
	 */
	public static function getInstance() {
		if ( null === self::$_instance ) {
			self::$_instance = new \Aha\Coroutine\Scheduler();
		}
		return self::$_instance;
	}

	/**
	 * @brief 初始化task队列
	 */
	public function __construct() {
		$this->_taskQueue = new \SplQueue();
	}
	
	/**
	 * @brief 任务调度
	 * @param \Aha\Coroutine\Task $task
	 */
	public function schedule(\Aha\Coroutine\Task $task) {
		$this->_taskQueue->enqueue($task);
	}
	
	/**
	 * @brief 异步IO任务调度
	 *		   主要是为了资源的安全释放和更快速的回收
	 * @param \Aha\Coroutine\Task $task
	 */
	public function asyncIoSchedule(\Aha\Coroutine\Task $task) {
		$this->schedule($task);
		//这种情况下 如果只有一个任务 需要自己调度自己
		if ( $this->_taskQueue->count() === 1 ) {
			$this->run();
		}
	}

	/**
	 * @bief 投递任务到调度器
	 * @param \Generator $coroutinue
	 * @return type
	 */
	public function newTask(\Generator $coroutinue) {
		$taskId = ++$this->_maxTaskId;
		$task	= new \Aha\Coroutine\Task($taskId, $coroutinue);
		$this->schedule($task);
		return $taskId;
	}
	
	/**
	 * @brief 杀一个任务
	 * @param type $taskId
	 * @return boolean
	 */
	public function killTask($taskId) {
		foreach ( $this->_taskQueue as $queueId=>$task ) {
			if ( $task->getTaskId() === $taskId ) {
				unset($this->_taskQueue[$queueId]);
				break;
			}
		}
	}
	
	/**
	 * @brief 调度器调度流程
	 */
	public function run() {
		while ( !$this->_taskQueue->isEmpty() ) {
			$task = $this->_taskQueue->dequeue();
			$ret = $task->run($task->getCoroutine());
			
			//系统调用 投递到任务池
			if ( !is_null($ret) && $ret instanceof \Aha\Coroutine\SystemCall ) {
				try {
					$ret($task, $this);
				} catch (\Exception $ex) {
					$task->setException($ex);
					$this->schedule($task);
				}
				continue;
			}
			
			//再次调用(有异步IO的时候再次调度会和task里的callback重复多次)
			//if ( !$task->isFinished() ) {
			//	$this->schedule($task);
			//}
		}
	}
	
}