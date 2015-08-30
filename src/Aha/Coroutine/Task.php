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

class Task {
	
	/**
	 * @brief taskId
	 * @var type 
	 */
	protected $_taskId;
	
	/**
	 * @brief 协程
	 * @var type 
	 */
	protected $_coroutine;
	
	/**
	 * @brief 与调度器通信的值
	 * @var type 
	 */
	protected $_sendValue = null;
	
	/**
	 * @brief 是否第一次中断
	 * @var type 
	 */
	protected $_beforeFirstYield = true;
	
	/**
	 * @brief 子协程栈
	 * @var type 
	 */
	protected $_coroutineStack;
	
	/**
	 * @brief 协程栈异常
	 * @var type 
	 */
	protected $_exception = null;
	
	/**
	 * @brief 回调conent
	 * @var type 
	 */
	protected $_callbackResponse = null;


	/**
	 * @brief 任务初始化
	 * @param type $taskId
	 * @param \Generator $coroutine
	 */
	public function __construct($taskId, \Generator $coroutine) {
		$this->_taskId		= $taskId;
		$this->_coroutine	= $coroutine;
		$this->_coroutineStack = new \SplStack();
	}
	
	/**
	 * @brief 异常record
	 * @param type $exception
	 */
	public function setException($exception) {
		$this->_exception = $exception;
	}

	/**
	 * @brief 获取任务ID
	 * @return type
	 */
	public function getTaskId() {
		return $this->_taskId;
	}
	
	/**
	 * @brief 设置协程交互的数据	
	 * @param type $sendvalue
	 */
	public function setSendValue($sendvalue) {
		$this->_sendValue = $sendvalue;
	}
	
	/**
	 * @brief 协程栈是否还有后续
	 * @return type
	 */
	public function isFinished() {
		return !$this->_coroutine->valid();
	}
	
	/**
	 * @brief 获取协程
	 * @return type
	 */
	public function getCoroutine() {
		return $this->_coroutine;
	}

	/**
	 * @brief 协程堆栈调度
	 * @return type
	 */
	public function run(\Generator $generator) {
		for ( ; ; ) {
			try {
				//异常处理
				if ( null !== $this->_exception ) {
					$generator->throw($this->_exception);
					$this->_exception = null;
					continue;
				}
				//协程堆栈链的下一个元素
				$current = $generator->current();
				//协程堆栈链表的中断内嵌
				if ( $current instanceof \Generator ) {
					$this->_coroutineStack->push($generator);
					$generator = $current;
					continue;
				}
				
				//syscall中断
				if ( $current instanceof \Aha\Coroutine\SystemCall ) {
					return $current;
				}
				
				//retval中断
				$isReturnValue = $current instanceof \Aha\Coroutine\RetVal;
				if ( !$generator->valid() || $isReturnValue ) {
					if ( $this->_coroutineStack->isEmpty() ) {
						return;
					}
					
					$generator = $this->_coroutineStack->pop();
					$generator->send($isReturnValue ? $current->getvalue() : null);
				}
				
				//异步网络IO中断
				if ( $this->_ahaInterrupt($current) ) {
					return;
				}
				
				//当前协程堆栈元素可能有多次yeild 但是与父协程只有一次通信的机会 在通信前运行完
				$generator->send($current);
				while ( $generator->valid() ) {
					$current = $generator->current();
					$generator->send($current);
				}
				
				//协程栈是空的 已经调度完毕
				if ( $this->_coroutineStack->isEmpty() ) {
					return;
				}
				
				//把当前结果传给父协程栈
				$generator = $this->_coroutineStack->pop();
				$data = is_null($current) ? $this->_callbackResponse : $current;
				$generator->send($data);
				
			} catch (\Exception $ex) {
				echo "[Coroutine_Task_Run_Exception]" . $ex->getMessage() . PHP_EOL;
				if ( $this->_coroutineStack->isEmpty() ) {
					return;
				}
			}
		}
	}
	
	/**
	 * @brief 异步网络IO中断处理
	 * @param type $ahaAsyncIo
	 * @return boolean
	 */
	protected function _ahaInterrupt($ahaAsyncIo) {
		if ( $ahaAsyncIo instanceof \Aha\Client\Http ) {
			//TODO
			
			return true;
		} elseif ( $ahaAsyncIo instanceof \Aha\Storage\Db\Mysqli ) {
			//TODO
			
			return true;
		}
		
		return false;
	}
	
	//异步回调完成 继续协程栈运行
	public function queryDbCallback($result, $dbObj, $dbSock) {
		$data = compact('result', 'dbObj', 'dbSock');
		
		$generator = $this->_coroutineStack->pop();
		$generator->send($data);
		
		$this->run($generator);
	}
	
}