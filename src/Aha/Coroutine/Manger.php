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

class Manager {
	
	/**
	 * @brief 设置任务ID为下一次发送的值，并且再次调度这个任务
	 * @return \Aha\Coroutine\SystemCall
	 */
	public static function getTaskId() {
		return new \Aha\Coroutine\SystemCall(
			function ( \Aha\Coroutine\Task $task, \Aha\Coroutine\Scheduler $scheduler ) {
				$task->setSendValue($task->getTaskId());
				$scheduler->schedule($task);
			}
		);
	}

	/**
	 * @brief 系统调用向掉队投递一个任务 获取到任务ID
	 *		  设置任务ID为下一次发送的值，并且再次调度这个任务
	 * @param \Generator $coroutine
	 * @return \Aha\Coroutine\SystemCall
	 */
	public static function newTask(\Generator $coroutine) {
		return new \Aha\Coroutine\SystemCall(
			function ( \Aha\Coroutine\Task $task, \Aha\Coroutine\Scheduler $scheduler ) use ($coroutine) {
				$task->setSendvalue($scheduler->newTask($coroutine));
				$scheduler->schedule($task);
			}
		);
	}
	
	/**
	 * @brief 系统调用杀一个任务
	 * @param type $taskId
	 * @return \Aha\Coroutine\SystemCall
	 */
	public static function killTask($taskId) {
		return new \Aha\Coroutine\SystemCall(
			function( \Aha\Coroutine\Task $task, \Aha\Coroutine\Scheduler $scheduler ) use ($taskId) {
				$task->setSendvalue($scheduler->killTask($taskId));
				$scheduler->schedule($task);
			}
		);
	}
	
	/**
	 * @brief 封装一个返回值 
	 * @param type $value
	 * @return \Aha\Coroutine\RetVal
	 */
	public static function retval($value) {
		return new \Aha\Coroutine\RetVal($value);
	}
	
}