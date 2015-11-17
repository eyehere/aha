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

class Shared {
	
	//=============================================================
	//流控任务上限原子计数器
	protected static $_maxTaskNumAtomic = null;

	//当前任务的数量原子计数器table
	protected static $_objTable = null;
    
    //===================================================================
	//初始化原子计数器
	public static function initAtomic() {
		self::$_maxTaskNumAtomic		= new \swoole_atomic(0);
	}
	
	//获取流控原子计数器
	public static function &getMaxTaskNumAtomic() {
		return self::$_maxTaskNumAtomic;
	}
    
    //===================================================================

    //创建table实例
    public static function initTable() {
        self::$_objTable = new \swoole_table(64);
		self::$_objTable->column('taskNum', \swoole_table::TYPE_INT, 4);
		self::$_objTable->column('workerType', \swoole_table::TYPE_INT, 4);
		self::$_objTable->create();
    }
    
    //获取当前处理中任务计数器
	public static function &getCurrentTaskTable() {
		return self::$_objTable;
	}
	
	//往table写数据
	public static function setCurrentTaskTable($key, $value) {
		return self::$_objTable->set($key, $value);
	}
	
	//删除当前处理中任务计数器
	public static function delCurrentTaskTable($pid) {
		self::$_objTable->del($pid);
	}
	
	//获取当前进程的taskNum
	public static function getCurrentTaskNumByKey($key) {
		$worker  = self::$_objTable->get($key);
        $taskNum = 0;
        if ( isset($worker['taskNum']) ) {
            $taskNum = $worker['taskNum'];
        }
		if ( false === $worker || $taskNum < 0 || $taskNum > (2 << 16) ) {//保证table数据的安全
			self::$_objTable->set($key, array('taskNum'=>0, 'workerType'=>  Manager::WORKER_TYPE_DISTRIBUTE));
			return 0;
		}
		return $taskNum;
	}
	
	//计数增加
	public static function incr($key, $column, $incrby = 1) {
		return self::$_objTable->incr($key, $column, $incrby);
	}
	
	//计数减小
	public static function decr($key, $column, $incrby = 1) {
		$ret = self::$_objTable->decr($key, $column, $incrby);
        if ( false === $ret ) {
            return false;
        }
        return $this->getCurrentTaskNumByKey($key);
	}
	
}