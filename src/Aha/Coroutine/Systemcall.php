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

class SystemCall {
	
	/**
	 * @brief 回调函数
	 * @var type 
	 */
	protected $_callback = null;
	
	/**
	 * @brief 系统调用初始化
	 * @param \callable $callback
	 */
	public function __construct(\callable $callback) {
		$this->_callback = $callback;
	}
	
	/**
	 * @brief  系统调用(如杀掉某个任务)
	 *	要求调度器把正在调用额任务和自生传给这个函数
	 * @param \Aha\Coroutine\Task $task
	 * @param \Aha\Coroutine\Scheduler $schedule
	 * @return type
	 */
	public function __invoke(\Aha\Coroutine\Task $task, \Aha\Coroutine\Scheduler $schedule) {
		$callback  = $this->_callback;
		return $callback($task, $schedule);
	}
	
}