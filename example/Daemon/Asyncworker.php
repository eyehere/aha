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
namespace Daemon;

class Asyncworker {
	
	/**
	 * @brief Aha实例
	 * @var type 
	 */
	protected $_objAha = null;
	
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
	public function start(\swoole_process $worker) {
		swoole_event_add($worker->pipe, function($pipe) use ($worker) {
			echo $worker->read() . PHP_EOL;
		});
		$this->worker();
	}
	
	/**
	 * @brief 子进程做事情
	 */
	public function worker() {
		$scheduler = $this->_objAha->getScheduler();
		swoole_timer_tick(5000, function() use ($scheduler){
			for ($i=0;$i<50;$i++) {
				$coroutine = $this->dbTest();;
				if ( $coroutine instanceof \Generator ) {
					$scheduler->newTask($coroutine);
					$scheduler->run();
				}
			}
		});
	}
	
	public function dbTest() {
		$ret = yield ( $this->dbTrans() );
		var_dump($ret);
	}

	public function dbTrans() {
		$config = $this->_objAha->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$trans = $conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				})
				->queue('friendsPlus','insert into friends set user_id=100000,friend_id=1000000');
		$ret = yield ( $trans );
		yield ($ret);
	}
	
}